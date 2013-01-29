<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Zolid AJAX Chat</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Global CSS -->
        <link href="css/bootstrap.min.css?v=222" rel="stylesheet" type="text/css">

        <!-- Chat Css -->
        <link href="css/chat.css?v=010" rel="stylesheet" type="text/css">
    
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
	</head>
	<body>
    
		<div class="container">
        
            <div class="row">
            
                <div class="span12 panel">
                
                    <div class="panel-header">
                        <i class="icon32-mailbox"></i>
                        <span>Online Breeder Live Chat [<span id="curchatroom">Loading..</span>]</span>
                        <div class="chatloader pull-right">
                            <img src="img/chatloader.gif" alt="">
                            Loading.. 
                        </div>
                    </div>
                    
                    <div class="panel-body">
                        <!-- The actual Chat -->
                        <div id="chat_conversation"></div>
                        <div>
                            <form id="chat_newmsg" class="row-fluid form-inline" action="#" onsubmit="return false;">
                                <input name="message" type="text" class="span9" placeholder="Your message..">
                                <button type="submit" class="btn btn-small btn-success">Send Message</button>

                                <!-- Chat Room Selection -->
                                <div class="dropdown pull-right">
                                    <a class="btn dropdown-toggle" data-toggle="dropdown"><i class="icon-comment"></i> Chat Room</a>
                                    <ul id="chatroom" class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
                                        <li><a tabindex="-1" href="#" data-room="General"><i></i> General</a></li>
                                        <li><a tabindex="-1" href="#" data-room="Help"><i></i> Help</a></li>
                                        <li><a tabindex="-1" href="#" data-room="Off Topic"><i></i> Off Topic</a></li>
                                    </ul>
                                </div>
                                <!-- End of Chat Room Selection -->

                            </form>
                        </div>
                        <!-- End of the chat -->
                    </div>
                    
                </div>
                
            </div>
            
        </div>
        
        <!-- jQuery -->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
        
        <!-- Twitter Bootstrap -->
        <script type="text/javascript" src="js/bootstrap.min.js?v=220"></script>
        
        <!-- The chat -->
        <script type="text/javascript" src="js/chat.js?v=010"></script>
        <script type="text/javascript">
        $(document).ready(function(){
            $(document).obChat({
                chatBox: '#chat_conversation', // The ID of the chatbox where the messages will be shown
                msgForm: '#chat_newmsg', // The ID of the form for new chat messages
                msgMax: 0, // The max messages to display in the chat at any time, 0 = no limit.
                showLoader: true, // Show the "loader" when the script it querying for new messages or chaning room.
                debug: true, // Show debugging information in the console
                updateRate: 10000, // (milliseconds) How often the script will check for new messages. I recommend 7500 or more (7.5 sec).
                queryurl: 'chat/livechat.processor.php' // The url to the chat query php script.
            });
        });
        </script>

    </body>
</html>