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

use Symfony\Component\HttpFoundation\Request;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\AuthenticationManager;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\PackageUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\ThreadProcessor;

/**
 * This is a utility class that backs the ThreadController
 * 
 * @author Eyong N <eyongn@scalior.com>		4/30/2015
 */
class ThreadUtil
{
    /**
     * Gets a list of messages for the thread
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public static function updateMessages($authenticationMgr, $requestThreads, $returnArray)
	{
		$arrayOut = null;
		$threadMessages = array();

		// In order to use as much of the core functionality as possible,
		// we call the ThreadProcessor to process the request. This involves
		// creating a MibewAPI package and passing that in a request to the
		// ThreadProcessor, then decoding the result from the Response object
		// that is returned by the processor. This could be made more
		// efficient if the RequestProcessor provides another method for 
		// processing functions other than the handleRequest() method.

		$mibewAPIRequests = array();
		$threadToToken = array();
		foreach($requestThreads as $oneThread) {
			$functions = array();
			// TODO: We need to verify the parameters
			$tmpToken = md5(time() + $oneThread['token']);
			$arguments = array(
							'threadId' => $oneThread['threadid'],
							'token' => $oneThread['token'],
							'lastId' => $oneThread['lastid'],
							'user' => false, 		// False since this action is reached from an operator client
							'references' => array(),
							'return' => $returnArray,
						);
			$functions[] = PackageUtil::makeFunction('updateMessages', $arguments);
			$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
			$threadToToken[$tmpToken] = $oneThread['threadid'];
		}
		
		$package = PackageUtil::encodePackage($mibewAPIRequests);

		// Create a new request and store the package in it where the processor expects it.
		$processorRequest = Request::create('.', 'POST');
		$processorRequest->request->set('data', $package);
		
		// Call the request processor
        $processor = ThreadProcessor::getNewInstance();
        $processor->setAuthenticationManager($authenticationMgr);
        $processorResponse = $processor->handleRequest($processorRequest);
		
		// Unpack the response and format it in a way that the Wurrd client expects it.
		$content = $processorResponse->getContent();
		$decodedRequests = PackageUtil::getRequestsFromPackage($content);
		
		$threadMessages = array();
		$lastRevision = 0;
		foreach ($decodedRequests as $retRequest) {
			if (array_key_exists($retRequest['token'], $threadToToken)) {
				foreach ($retRequest['functions'] as $retFunction) {
					if ($retFunction['function'] == 'result') {
						$threadInfo['threadid'] = $threadToToken[$retRequest['token']];
						$threadInfo['lastid'] = $retFunction['arguments']['lastId'];
						
						if (array_key_exists('messages', $returnArray)) {
							$threadInfo['messages'] = $retFunction['arguments']['messages'];
						}
						
						$threadMessages[] = $threadInfo;
						// Once we get the result, we move onto the next request
						break;
					}
				}
			}
		}
		
		$arrayOut['threadmessages'] = $threadMessages;
		return $arrayOut;
    }
}

