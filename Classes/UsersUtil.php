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

use Mibew\Controller\AbstractController;
use Mibew\Http\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\AuthenticationManager;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\PackageUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\UsersProcessor;


/**
 * This is a utility class that backs the UsersController
 * 
 * @author Eyong N <eyongn@scalior.com>		4/28/2015
 */
class UsersUtil
{
    /**
     * Gets a list of active threads for a given user
     *
     * @param Request $request Incoming request.
     * @return Array An array of containing the requested information for the active threads.
     */
    public static function getActiveThreads($operator, $threadRevision, $authenticationMgr, $returnArray)
	{
		$arrayOut = null;

		// In order to use as much of the core functionality as possible,
		// we call the UserProcessor to process the request. This involves
		// creating a MibewAPI package and passing that in a request to the
		// UserProcessor, then decoding the result from the Response object
		// that is returned by the processor. This could be made more
		// efficient if the RequestProcessor provides another method for 
		// processing functions other than the handleRequest() method.

		$mibewAPIRequests = array();

		// Prepare a request for the updateThreads function
		$functions = array();
		$updateThreadsToken = md5(time() + 1000); 	// 1000 is just a random number
		$arguments = array(
						'revision' => $threadRevision,
						'agentId' => $operator['operatorid'],
						'references' => array(),
						'return' => $returnArray,
					);
		$functions[] = PackageUtil::makeFunction('updateThreads', $arguments);
		$mibewAPIRequests[] = PackageUtil::makeRequest($updateThreadsToken, $functions);
		
		// We also need to make a request for "update" which sends a notify_operator_alive call
		$functions = array();
		$updateToken = md5(time() + 2000); 	// 2000 is just a random number
		$arguments = array(
						'agentId' => $operator['operatorid'],
						'references' => array(),
						'return' => array(),
					);
		$functions[] = PackageUtil::makeFunction('update', $arguments);
		$mibewAPIRequests[] = PackageUtil::makeRequest($updateToken, $functions);
				
		$package = PackageUtil::encodePackage($mibewAPIRequests);

		// Create a new request and store the package in it where the processor expects it.
		$processorRequest = Request::create('.', 'POST');
		$processorRequest->request->set('data', $package);
		
		// Call the request processor
        $processor = UsersProcessor::getNewInstance();
        $processor->setAuthenticationManager($authenticationMgr);
        $processorResponse = $processor->handleRequest($processorRequest);
		
		// Unpack the response and format it in a way that the Wurrd client expects it.
		$content = $processorResponse->getContent();
		$decodedRequests = PackageUtil::getRequestsFromPackage($content);
		
		$threads = null;
		$lastRevision = 0;
		foreach ($decodedRequests as $retRequest) {
			if ($retRequest['token'] == $updateThreadsToken) {
				foreach ($retRequest['functions'] as $retFunction) {
					if ($retFunction['function'] == 'result') {
						$lastRevision = $retFunction['arguments']['lastRevision'];
						if (array_key_exists('threads', $returnArray)) {
							$threads = $retFunction['arguments']['threads'];
						}
						
						// For this call we expect only one request, which holds the result.
						break;
					}
				}
			}
			if ($lastRevision != 0) {
				break;
			}
		}

		$arrayOut = array();		
		$arrayOut['lastrevision'] = $lastRevision;
		if (array_key_exists('threads', $returnArray)) {
			$arrayOut['threads'] = $threads;
		}
		
		return $arrayOut;
	}
}

