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

use Mibew\RequestProcessor\ThreadProcessor as CoreThreadProcessor;
use Mibew\RequestProcessor\Exception\ThreadProcessorException;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\Thread;

/**
 * Extends the Mibew core ThreadProcessor
 * 
 * TODO: This extension is needed to override the apiPost() method
 * 		 so that it returns the postedId for every message that is 
 * 		 posted. The recommendation is for this enhancement to be 
 * 		 implemented in the core ThreadProcessor itself
 */
class ThreadProcessor extends CoreThreadProcessor
{
    /**
     * Post message to thread. API function
     *
     * @param array $args Associative array of arguments. It must contains the
     *   following keys:
     *    - 'threadId': Id of the thread related to chat window
     *    - 'token': last thread token
     *    - 'user': TRUE if window used by user and FALSE otherwise
     *    - 'message': posted message
     * @throws ThreadProcessorException
     */
    protected function apiPost($args)
    {
        // Load thread
        $thread = self::getThread($args['threadId'], $args['token']);

        // Check variables
        self::checkParams($args, array('user', 'message'));

        // Get operator's array
        if (!$args['user']) {
            $operator = $this->checkOperator();
            // Operators can post messages only to own threads.
            $can_post = ($operator['operatorid'] == $thread->agentId);
        } else {
            // Users can post messages only when a thread is open.
            $can_post = $thread->state != Thread::STATE_CLOSED;
        }

        if (!$can_post) {
            throw new ThreadProcessorException(
                "Cannot send",
                ThreadProcessorException::ERROR_CANNOT_SEND
            );
        }

        // Set fields
        $kind = $args['user'] ? Thread::KIND_USER : Thread::KIND_AGENT;
        if ($args['user']) {
            $msg_options = array('name' => $thread->userName);
        } else {
            $msg_options = array(
                'name' => $thread->agentName,
                'operator_id' => $operator['operatorid'],
            );
        }

        // Post message
        $posted_id = $thread->postMessage($kind, $args['message'], $msg_options);

        // Update shownMessageId
        if ($args['user'] && $thread->shownMessageId == 0) {
            $thread->shownMessageId = $posted_id;
            $thread->save();
        }
		
		
		// Everything in this method up to this point is from mibew-2.0.0-beta.2
		return array('postedId' => $posted_id);
    }


    /**
     * Send new messages to window. API function
	 * -- Overriding this so that we can use our Thread subclass.
     *
     * @param array $args Associative array of arguments. It must contains the
     *   following keys:
     *    - 'threadId': Id of the thread related to chat window
     *    - 'token': last thread token
     *    - 'user': TRUE if window used by user and FALSE otherwise
     *    - 'lastId': last sent message id
     */
    protected function apiUpdateMessages($args)
    {
        // Load thread
        $thread = $this->getWurrdThread($args['threadId'], $args['token']);

        // Check variables
        self::checkParams($args, array('user', 'lastId'));

        // Check access
        if (!$args['user']) {
            $operator = $this->checkOperator();

			// Check for reassign here. Normally this is done by the apiUpdate() method. 
			$thread->checkForReassign($operator);		
        }

        // Send new messages
        $last_message_id = $args['lastId'];
        $messages = array_map(
            'sanitize_message',
            $thread->getMessages($args['user'], $last_message_id)
        );


		// Ping the thread here. Normally this is done by the apiUpdate() method. 
		// However this method will be called when checking for updates for multiple threads
		// at the same time in the background process when the operator is not actively engaged in this thread.
		// At this time the operator can't indicate that they are typing or not.
		// It is also not satisfactory to say the operator is not typing because the background operation could be
		// triggered even when the operator is actively chatting.
		$thread->operatorPing(null);
		
        return array(
            'messages' => $messages,
            'lastId' => $last_message_id,
        );
    }

    
    /**
     * Loads thread by id and token and checks if thread loaded
     *
     * @param int $thread_id Id of the thread
     * @param int $last_token Last token of the thread
     * @return \Mibew\Thread
     * @throws \Mibew\RequestProcessor\ThreadProcessorException
     */
    public function getWurrdThread($thread_id, $last_token)
    {
        // Load thread
        $thread = Thread::ovLoad($thread_id, $last_token);
        // Check thread
        if (!$thread) {
            throw new ThreadProcessorException(
                'Wrong thread',
                ThreadProcessorException::ERROR_WRONG_THREAD
            );
        }

        // Return thread
        return $thread;
    }

    /**
     * Gets a new instance of the ThreadProcessor. The getInstance() method returns
	 * a singleton which makes it difficult to use a ThreadProcessor and a UsersProcessor
	 * within the same request. The NotificationController calls both when checking if a 
	 * particular client needs to be notified of new activity
     *
     * @return ThreadProcessor 	A new ThreadProcessor instance
     */
     public static function getNewInstance() {
     	return new static();
	 }
}


 