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
use Mibew\Thread;

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
}


 