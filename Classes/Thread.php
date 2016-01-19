<?php
/*
 * This file is a part of Wurrd ClientInterface Plugin.
 *
 * Copyright 2015 Eyong N <eyongn@scalior.com>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Wurrd\Mibew\Plugin\ClientInterface\Classes;

use Mibew\Database;
use Mibew\EventDispatcher\EventDispatcher;
use Mibew\EventDispatcher\Events;
use Mibew\Settings;
use Mibew\Thread as CoreThread;

/**
 * Extends the Mibew core Thread
 * 
 * TODO: This extension is needed to override the getMessages() method
 * 		 so that it returns more details about messages
 *  	 The recommendation is for this enhancement to be 
 * 		 implemented in the core Thread itself
 */
class Thread extends CoreThread
{
    /**
     * Load messages from database corresponding to the thread those ID's more
     * than $lastid
     *
     * @param boolean $is_user Boolean TRUE if messages loads for user
     *   and boolean FALSE if they loads for operator.
     * @param int $lastid ID of the last loaded message.
     * @return array Array of messages. Every message is associative array with
     *   following keys:
     *    - 'id': int, message id;
     *    - 'kind': int, message kind, see Thread::KIND_* for details;
     *    - 'created': int, unix timestamp when message was created;
     *    - 'name': string, name of sender;
     *    - 'message': string, message text;
     *    - 'plugin': string, name of the plugin which sent the message or an
     *      empty string if message was not sent by a plugin.
     *    - 'data' array, arbitrary data attached to the message
	 *
	 * 	Added by this override.
	 *	  - 'agentid': The id of the agent who posted the message
	 * 
     * @see Thread::postMessage()
     */
    public function getMessages($is_user, &$last_id)
    {
        $db = Database::getInstance();

        // Load messages
        $query = "SELECT messageid AS id, ikind AS kind, dtmcreated AS created, "
                . " tname AS name, tmessage AS message, plugin, data, agentid "
            . "FROM {message} "
            . "WHERE threadid = :threadid AND messageid > :lastid "
                . ($is_user ? "AND ikind <> " . self::KIND_FOR_AGENT : "")
            . " ORDER BY messageid";

        $messages = $db->query(
            $query,
            array(
                ':threadid' => $this->id,
                ':lastid' => $last_id,
            ),
            array('return_rows' => Database::RETURN_ALL_ROWS)
        );

        foreach ($messages as $key => $msg) {
            // Process data attached to the message
            if (!empty($messages[$key]['data'])) {
                $messages[$key]['data'] = unserialize(
                    $messages[$key]['data']
                );
            } else {
                $messages[$key]['data'] = array();
            }
        }

        // Trigger the "alter" event
        $args = array(
            'messages' => $messages,
            'thread' => $this,
        );
        EventDispatcher::getInstance()->triggerEvent(Events::THREAD_GET_MESSAGES_ALTER, $args);
        $altered_messages = $args['messages'];

        // Get ID of the last message
        foreach ($altered_messages as $msg) {
            if ($msg['id'] > $last_id) {
                $last_id = $msg['id'];
            }
        }

        return $altered_messages;
    }


    /**
     * Load thread from database
     *
     * @param int $id ID of the thread to load
     * @return boolean|Thread Returns an object of the Thread class or boolean
     *   false on failure
     */
    public static function ovLoad($id, $last_token = null)
    {
        // Check $id
        if (empty($id)) {
            return false;
        }

        // Load thread
        $thread_info = Database::getInstance()->query(
            "SELECT * FROM {thread} WHERE threadid = :threadid",
            array(':threadid' => $id),
            array('return_rows' => Database::RETURN_ONE_ROW)
        );

        // There is no thread with such id in database
        if (!$thread_info) {
            return;
        }

        // Create new empty thread and populate it with the values from database
        $thread = new self();
        $thread->populateFromDbFields($thread_info);

        // Check last token
        if (!is_null($last_token)) {
            if ($thread->lastToken != $last_token) {
                return false;
            }
        }

        return $thread;
    }

    /**
     * Ping the thread by operator.
     *
     * Updates ping time for conversation members and sends messages about
     * connection problems.
     *
     * @param boolean $is_typing Indicates if operator is typing a
     *   message. null indicates that the typing status should not be updated
     */
    public function operatorPing($is_typing)
    {
        // Indicates if revision ID of the thread should be updated on save.
        // Update revision leads to rerender thread in threads list at client
        // side. Do it on every ping is too costly.
        $update_revision = false;
        // Last ping time of other side
        $last_ping_other_side = 0;
        // Update last ping time
        $last_ping_other_side = $this->lastPingUser;
        $this->lastPingAgent = time();
		
		// Update agent typing state only if parameter is not null
		if (!is_null($is_typing)) {
	        $this->agentTyping = $is_typing ? "1" : "0";
		}

        // Check if other side of the conversation have connection problems
        if ($last_ping_other_side > 0 && abs(time() - $last_ping_other_side) > Settings::get('connection_timeout')) {
            // Connection problems detected
            // Update user's last ping time
            $this->lastPingUser = 0;

            // And send a message to operator.
            if ($this->state == self::STATE_CHATTING) {
                $message_to_post = getlocal(
                    'Visitor closed chat window',
                    null,
                    $this->locale,
                    true
                );
                $this->postMessage(
                    self::KIND_FOR_AGENT,
                    $message_to_post,
                    array('created' => $last_ping_other_side + Settings::get('connection_timeout'))
                );
            }
        }

        $this->save(false);
    }


}


 