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

namespace Wurrd\Mibew\Plugin\ClientInterface\Controller;

use Mibew\Controller\AbstractController;
use Mibew\Http\Exception;
use Mibew\RequestProcessor\ThreadProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\AuthenticationManager;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\PackageUtil;



 /**
  * Controller that handles thread interactions
  * 
  * This controller returns JSON encoded output. The output format can 
  * be abstracted such that there is an output factory that will return
  * the results in the requested format.
  */
class ThreadController extends AbstractController
{
    /**
     * Gets a list of active threads
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function updateMessagesAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$threads = $request->query->get('threads');
		$threadMessages = array();

		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);
				
				// In order to use as much of the core functionality as possible,
				// we call the ThreadProcessor to process the request. This involves
				// creating a MibewAPI package and passing that in a request to the
				// ThreadProcessor, then decoding the result from the Response object
				// that is returned by the processor. This could be made more
				// efficient if the RequestProcessor provides another method for 
				// processing functions other than the handleRequest() method.

				$requestedThreads = json_decode($threads, true);
		        $json_error_code = json_last_error();
		        if ($json_error_code != JSON_ERROR_NONE) {
		            // Not valid JSON
		            throw new Exception\HttpException(
									Response::HTTP_BAD_REQUEST,
									Constants::MSG_INVALID_JSON);
				}

				$mibewAPIRequests = array();
				$threadToToken = array();
				foreach($requestedThreads as $oneThread) {
					// TODO: We need to verify the parameters
					$tmpToken = md5(time() + $oneThread['token']);
					$arguments = array(
									'threadId' => $oneThread['threadid'],
									'token' => $oneThread['token'],
									'lastId' => $oneThread['lastid'],
									'user' => false, 		// False since this action is reached from an operator client
									'references' => array(),
									'return' => array(
										'messages' => 'messages',
										'lastId' => 'lastId',
									),
								);
					$functions[] = PackageUtil::makeFunction('updateMessages', $arguments);
					$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
					$threadToToken[$tmpToken] = $oneThread['threadid'];
				}
				
				$package = PackageUtil::encodePackage($mibewAPIRequests);

				// Store the package in a request where the processor expects it
				$request->request->set('data', $package);
				
				// Call the request processor
		        $processor = ThreadProcessor::getInstance();
		        $processor->setRouter($this->getRouter());
		        $processor->setAuthenticationManager($this->getAuthenticationManager());
		        $processor->setMailerFactory($this->getMailerFactory());
		        $processorResponse = $processor->handleRequest($request);
				
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
								$threadInfo['messages'] = $retFunction['arguments']['messages'];
								$threadInfo['lastid'] = $retFunction['arguments']['lastId'];
								$threadMessages[] = $threadInfo;
								// For this call we expect only one request, which holds the result.
								break;
							}
						}
					}
				}
			}

			$arrayOut['threadmessages'] = $threadMessages;
		} catch(Exception\HttpException $e) {
			$httpStatus = $e->getStatusCode();
			$message = $e->getMessage();
		}
		
		$arrayOut['message'] = $message;
		$response = new Response(json_encode($arrayOut),
								$httpStatus,
								array('content-type' => 'application/json'));
		return $response;
  
    }

}

