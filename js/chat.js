 /*!
  * Zolid AJAX Chat v0.1.0
  * http://zolidweb.com
  * 
  * Copyright (c) 2010 Mark Eliasen
  * Licensed under the MIT licenses.
  * http://www.opensource.org/licenses/mit-license.php
  */

(function ($) {
    'use strict';

    $.fn.extend({
        obChat: function (options) {
            var settings = {
                chatBox: '',
                msgForm: '',
                msgMax: 50,
                debug: false,
                showLoader: true,
                updateRate: 5000,
                queryurl: ''
            },
                mc = 0,
                timer = null,
                ntfc = null,
                chatRoom = null;

            options =  $.extend(settings, options);

            function debug(msg) {
                if (options.debug && typeof console === "object") {
                    console.log(msg);
                }
            }

            function showLoader() {
                if ($('.chatloader').length) {
                    $('.chatloader').show();
                }
            }

            function hideLoader() {
                if ($('.chatloader').is(':visible')) {
                    $('.chatloader').fadeOut();
                }
            }

            function stopChat() {
                debug('Chat Stopped.');
                clearInterval(timer);
            }

            function scrollDown(element, duration) {
                if (element.length) {
                    if (typeof duration === 'undefined') {
                        duration = 1000;
                    }
                    element.animate({scrollTop: element[0].scrollHeight}, duration);
                }
            }

            function addMessages(data, clear, ghost) {
                if (clear !== 'undefined' && clear) {
                    $(options.chatBox).html('');
                }

                if (ghost !== 'undefined' && ghost) {
                    $(options.chatBox).append('<div class="well well-small ghostmessage">[' + data.time + '] <span class="label ' + data.highlight + '">' + data.user + '</span>: ' + data.msg + '</div>');
                    scrollDown($(options.chatBox));
                } else {
                    $.each(data, function (i, msg) {
                        $('.ghostmessage').remove();

                        if (typeof msg.user !== 'undefined') {
                            mc += 1;
                            $(options.chatBox).append('<div class="well well-small">[' + msg.time + '] <span class="label ' + msg.highlight + '">' + msg.user + '</span>: ' + msg.msg + '</div>');

                            if (mc >= data.totalnew) {
                                if (options.msgMax > 0 && $(options.chatBox).children("div").length > options.msgMax) {
                                    $(options.chatBox).children("div").slice(-($(options.chatBox).children("div").length - options.msgMax)).fadeOut(400).delay(400).remove();
                                }

                                scrollDown($(options.chatBox));
                            }
                        }
                    });
                }
            }

            function loadMessages(all, clear) {
                showLoader();
                debug('[loadMessages] Querying server for messages..');

                mc = 0;
                if (typeof all === 'undefined') {
                    all = 'false';
                }
                if (typeof clear === 'undefined') {
                    clear = false;
                }

                debug('[LoadMessages] Clear chat? - ' + clear);

                $.ajax({
                    url: options.queryurl,
                    type: 'POST',
                    data: 'load=true&all=' + all,
                    dataType: "json",
                    success: function (reply) {
                        debug('[loadMessages] Response received:');
                        debug(reply);

                        $('#chatroom a[data-room="' + reply.room + '"]').find('i').addClass('icon-chevron-right');
                        $('#curchatroom').html(reply.room);

                        if (reply.status) {
                            addMessages(reply, all);
                        } else if (clear) {
                            $(options.chatBox).html('');
                        }

                        hideLoader();
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        debug('[loadMessages] Error: ' + xhr.responseText);
                        stopChat();
                    }
                });
            }

            function init() {
                debug('Chat Initiated.');
                timer = setInterval(loadMessages, options.updateRate);
            }

            $(document).ready(function () {
                $(options.msgForm + ' button[type=\'submit\']').click(function (e) {
                    var send = $(this);
                    if ($(options.msgForm + ' input[name=\'message\']').val() === '') {
                        debug('Cannot send empty messages');
                        return false;
                    }

                    send.attr('disabled', true);

                    $.ajax({
                        url: options.queryurl,
                        type: 'POST',
                        data: $(options.msgForm).serialize() + '&new=true',
                        dataType: "json",
                        success: function (reply) {
                            addMessages(reply, false, true); //add ghost message until chat loads
                            $(options.msgForm + ' input[name=\'message\']').val('');
                            debug('message sent');
                            send.attr('disabled', false);
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            debug(xhr.responseText);
                            debug('error sending message');
                            send.attr('disabled', false);
                        }
                    });
                    e.preventDefault();
                    return false;
                });

                debug('Live Chat initiated.');
                loadMessages(true);
                init();

                /* 
                    Chat Settings 
                */
                $('#chatroom li a').click(function (e) {
                    if ($(this).attr('data-room') === 'undefined') {
                        return false;
                    }

                    if (chatRoom === $(this).attr('data-room')) {
                        return false;
                    }

                    stopChat();
                    chatRoom = $(this).attr('data-room');

                    var button = $(this);
                    $.ajax({
                        url: options.queryurl,
                        type: 'POST',
                        data: 'setroom=' + $(this).attr('data-room'),
                        dataType: "json",
                        success: function (reply) {
                            debug('[SetSettings] Response received:');
                            debug(reply);
                            $('#chatroom li a i.icon-chevron-right').removeClass('icon-chevron-right');
                            button.children('i').addClass('icon-chevron-right');

                            if (reply.status) {
                                addMessages(reply, true);
                            } else {
                                $(options.chatBox).html('');
                            }

                            $('#curchatroom').html(reply.room);

                            init();
                        },
                        error: function (xhr, ajaxOptions, thrownError) {
                            debug(xhr.responseText);
                            debug('error sending message');
                        }
                    });
                });
            });
        }
    });
}(jQuery));