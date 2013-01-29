<?php
 /*
  * Zolid AJAX Chat v0.1.0
  * http://zolidweb.com
  * 
  * Copyright (c) 2010 Mark Eliasen
  * Licensed under the MIT licenses.
  * http://www.opensource.org/licenses/mit-license.php
  */

class Chat
{
    // Add any words you wish to filter out to this array.
    protected $profanity = array('Badword1', 'Badword2', 'badword3');
    
    // This is the chatrooms which the user can choose from.
    protected $rooms = array('General', 'Help', 'Off Topic');
    
    // This will hold the PDO object.
    protected $sql;
    
    // This is the connection details for the database where you store your messages.
    private $config = array(
                            'type' => 'mysql',
                            'host' => 'localhost',
                            'port' => '3306',
                            'database' => '',
                            'user' => '',
                            'password' => '',
                            'charset' => 'utf8',
                        );
    
    public function __construct()
    {
        // Try to connect to the SQL database
        try{
			$sql = new PDO(
				$this->config['type'] . ':' .
                'host=' . $this->config['host'] .
                ';port=' . $this->config['port'] .
                ';dbname=' . $this->config['database'] .
                ';charset=' . $this->config['charset'],
                $this->config['user'],
                $this->config['password'],
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8"',
					PDO::ATTR_EMULATE_PREPARES => false
                )
            );
			
            $this->sql = $sql;
        }
		catch(PDOException $pe)
        {
            die($this->lang['core']['classes']['core']['mysql_error']);
		}
        
        
        session_start();
        if( empty($_SESSION['chat']) )
        {
            $_SESSION['chat'] = array(
                                    'username' => uniqid('user_'),
                                    'filter' => true,
                                    'last_message' => 0, // Used with the anti-spam.
                                    'last_check' => 0, // Used with the anti-spam.
                                    'latest_id' => 0, // Used once messages have been found to make sure we do not miss any new once next time.
                                    'latest_time' => 0, // Used initially to retrive messages from the time the user enters the chat.
                                    'room' => $this->rooms[0]
                                );
        }

        $this->setRoom();
        $this->loadNewMessages();
        $this->sendMessage();
    }
    
    /**
     * Value sanitation. Sanitize input and output with ease using one of the sanitation types below.
     * 
     * @param  string $data the string/value you wish to sanitize
     * @param  string $type the type of sanitation you wish to use.
     * @return string       the sanitized string
     */
    public function sanitize($data, $type = '')
    {
		## Use the HTML Purifier, as it help remove malicious scripts and code. ##
		##       HTML Purifier 4.4.0 - Standards Compliant HTML Filtering       ##
		require_once('htmlpurifier/HTMLPurifier.standalone.php');

		$purifier = new HTMLPurifier();
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Core.Encoding', 'UTF-8');
        
        // If no type if selected, it will simply run it through the HTML purifier only.
		switch($type){
            // Remove HTML tags (can have issues with invalid tags, keep that in mind!)
			case 'purestring':
				$data = strip_tags( $data );
				break;
			
            // Only allow a-z (H & L case)
			case 'atoz':
				$data = preg_replace( '/[^a-zA-Z]+/', '',  $data );
				break;
			
            // Integers only - Remove any non 0-9 and use Intval() to make sure it is an integer which comes out.
			case 'integer':
				$data = intval( preg_replace( '/[^0-9]+/', '',  $data ) );
				break;
		}
		
        /* HTML purifier to help prevent XSS in case anything slipped through. */
        $data = $purifier->purify( $data );

		return $data;
	}
    
    Protected function profanityFilter($message)
    {
        return preg_replace('('.implode('|', $this->profanity).')i', '[Censured]', $message);
    }
    
    public function timeSince($unix)
    {
		$min = 60;
		$hour = 3600;
		$day = 86400;
		
		$diff = time() - $unix;
		$diff2 = $diff;

		$days = floor($diff / $day);
		$days = floor($diff / $day);
		$diff = $diff - ($day * $days);
		$hours = floor($diff / $hour);
		$diff = $diff - ($hour * $hours);
		$minutes = floor($diff / $min);
		$diff = $diff - ($min * $minutes);
		$seconds = $diff;
		
		$m = ( $minutes == 1 ? ' minute' : ' minutes' );
		$h = ( $hours == 1 ? ' hour' : ' hours' );
		$d = ( $days == 1 ? ' day' : ' days' );

		if( $diff2 < 60 )
        {
			$timest = $diff . ' seconds ago.';
        }
        else
        {
			if( $minutes >= 1 )
            {
				$timest = $minutes . $m . ' ago';
			}
            
			if( $hours >= 1 )
            {
				$timest = $hours . $h . ' ago';
			}
            
			if( $days >= 1 )
            {
				$timest = $days . $d . ' ago';
			}
            
			if( !isset($timest) )
            {
				$timest = '';
			}
		}

		if( $timest == '' )
        {
			$timest = 'just a second ago.';
		}
		
		return $timest;
	}
    
    private function setRoom()
    {
        if( !empty($_POST['setroom']) )
        {
            $room = $this->sanitize($_POST['setroom'], 'purestring');
            if( in_array($room, $this->rooms) )
            {
                $_SESSION['chat']['room'] = $room;
                $this->loadNewMessages( true ); 
            }
        }
    }
    
    private function loadNewMessages( $reload = false )
    {
        if( $reload || !empty($_POST['load']) && $_POST['load'] )
        {
            // If this is the first time the user quries for messages, we need to add the time to the session, so we know from when to look
            if( empty($_SESSION['chat']['latest_time']) )
            {
                $_SESSION['chat']['latest_time'] = time();
            }

            // Prepare to sql statement, and if no messages has been found, we check using the current time as it will only fetch messages from when the user joined.
            $stmt = $this->sql->prepare('SELECT 
                                                *
                                            FROM 
                                                chat
                                            WHERE
                                                room = :room
                                            AND
                                                ' . 
                                                ( 
                                                    ( $reload || !empty($_POST['all']) && $_POST['all'] == 'true' ) || empty($_SESSION['chat']['latest_id']) 
                                                    ?
                                                        ' time > :checkval ' 
                                                    : 
                                                        ' id > :checkval '
                                                )
                                            );
            
            $checkval = ( ( $reload || !empty($_POST['all']) && $_POST['all'] == 'true' ) || empty($_SESSION['chat']['latest_id']) ? $_SESSION['chat']['latest_time']  : $_SESSION['chat']['latest_id'] );
            
            $stmt->bindValue(':room', $_SESSION['chat']['room'], PDO::PARAM_STR);
            $stmt->bindValue(':checkval', $checkval, PDO::PARAM_INT);
            $stmt->execute();

            // Create the messages array which we will send back to the user.
            $messages = array(
                            'totalnew' => 0,
                            'status' => false,
                            'room' => $_SESSION['chat']['room']
                        );
            $c = 0;

            // If there are any new messages, get 'em!
            if( $stmt->rowCount() > 0 )
            {
                while( $row = $stmt->fetch(PDO::FETCH_ASSOC) )
                {
                    if( $_SESSION['chat']['filter'] && count($this->profanity) > 0 )
                    {
                        $row['message'] = preg_replace( '(' . implode( '|', $this->profanity ) . ')i', '[Censured]', $row['message'] );
                    }

                    $messages[] = array(
                                    'user' => $row['by'],
                                    'msg' => $row['message'],
                                    'time' => date( 'H:i:s', $row['time'] ),
                                    'highlight' => ($row['by'] != $_SESSION['chat']['username'] ? 'label-info' : 'label-success')
                                );
                    $_SESSION['chat']['latest_id'] = $row['id'];
                    $c++;
                }

                $messages['status'] = true;
            }

            $stmt->closeCursor();

            $messages['totalnew'] = $c;
            $_SESSION['chat']['last_check'] = time();

            echo json_encode($messages);
            exit;
        }
    }
    
    public function sendMessage()
    {
        if( ( !empty($_POST['new']) && $_POST['new'] ) || !empty($_POST['message']) )
        {
            $message = $this->sanitize( $_POST['message'], 'purestring');

            $stmt = $this->sql->prepare('INSERT INTO chat(`by`, `message`, `time`, `room`) VALUES(:byuser, :message, :time, :room)');
            $stmt->bindValue(':byuser', $this->sanitize( $_SESSION['chat']['username'], 'purestring' ), PDO::PARAM_STR);
            $stmt->bindValue(':message', $message, PDO::PARAM_STR);
            $stmt->bindValue(':time', time(), PDO::PARAM_INT);
            $stmt->bindValue(':room', $_SESSION['chat']['room'], PDO::PARAM_STR);

            $stmt->execute();

            $stmt->closeCursor();

            $_SESSION['chat']['last_message'] = time();

            echo json_encode( array(
                                    'status'=>true,
                                    'user' => $_SESSION['chat']['username'],
                                    'msg' => $message,
                                    'time' => date( 'H:i:s', time() ),
                                    'highlight' => 'label-success'
                                ) 
                            );
        }   
    }
}

$chat = new Chat();