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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\AuthenticationManager;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\PackageUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\Thread;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\ThreadProcessor;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\ThreadUtil;



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
     * Gets a list of messages for the thread
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

		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);
				
				$requestedThreads = json_decode($threads, true);
		        $json_error_code = json_last_error();
		        if ($json_error_code != JSON_ERROR_NONE) {
		            // Not valid JSON
		            throw new Exception\HttpException(
									Response::HTTP_BAD_REQUEST,
									Constants::MSG_INVALID_JSON);
				}

				
				$arrayOut = ThreadUtil::updateMessages($authenticationMgr, 
														$requestedThreads, 
														array('messages' => 'messages',
															  'lastId' => 'lastId'));
			}
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


    /**
     * Starts a chat session
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function startChatAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$threadId = $request->attributes->getInt('threadid');
		$token = $request->attributes->get('token');
		
		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);
		
				
		        // Check if the thread can be loaded.
		        $thread = Thread::load($threadId);
		        if (!$thread || !isset($thread->lastToken)) {
		            throw new Exception\HttpException(Response::HTTP_BAD_REQUEST, 
		            									Constants::MSG_WRONG_THREAD);
		        }

		        $view_only = ($request->query->get('viewonly') == 'true');
		        $force_take = ($request->query->get('force') == 'true');
		
		        $try_take_over = !$view_only
		            && $thread->state == Thread::STATE_CHATTING
		            && $operator['operatorid'] != $thread->agentId;
		        if ($try_take_over) {
		            if (!is_capable(CAN_TAKEOVER, $operator)) {
						throw new Exception\AccessDeniedException(Constants::MSG_CANNOT_TAKEOVER);
		            }
		
		            if ($force_take == false) {
		                // Fall into the catch block to process this otherwise successful
		                // request, requiring the operator to confirm takeover.
			            throw new Exception\HttpException(Response::HTTP_ACCEPTED, 
			            									Constants::MSG_CONFIRM_TAKEOVER);
		            }
		        }
		
		        if (!$view_only) {
		            if (!$thread->take($operator)) {
						throw new Exception\AccessDeniedException(Constants::MSG_CANNOT_TAKE_THREAD);
		            }
		        } elseif (!is_capable(CAN_VIEWTHREADS, $operator)) {
					throw new Exception\AccessDeniedException(Constants::MSG_CANNOT_VIEW_THREADS);
		        }
		    }
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

    /**
     * Posts a message to a chat session
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function postAMessageAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$threadId = $request->attributes->getInt('threadid');
		$token = $request->attributes->get('token');
		$postedId = null;

		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);

				$data = json_decode($request->getContent(), true);
				$chatMessage = $data['message'];
				$clientMessageId = $data['clientmessageid'];
								
				// In order to use as much of the core functionality as possible,
				// we call the ThreadProcessor to process the request. This involves
				// creating a MibewAPI package and passing that in a request to the
				// ThreadProcessor, then decoding the result from the Response object
				// that is returned by the processor. This could be made more
				// efficient if the RequestProcessor provides another method for 
				// processing functions other than the handleRequest() method.

				$tmpToken = md5(time());
				$arguments = array(
								'threadId' => $threadId,
								'token' => $token,
								'message' => $chatMessage,
								'user' => false,
								'references' => array(),
								'return' => array(
									'postedId' => 'postedId',
								),
							);
				$functions[] = PackageUtil::makeFunction('post', $arguments);
				$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
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
				
				foreach ($decodedRequests as $retRequest) {
					if ($retRequest['token'] == $tmpToken) {
						foreach ($retRequest['functions'] as $retFunction) {
							if ($retFunction['function'] == 'result') {
								$postedId = $retFunction['arguments']['postedId'];
								
								// For this call we expect only one request, which holds the result.
								break;
							}
						}
					}
					if ($postedId != null) {
						break;
					}
				}
			}

			$arrayOut['clientmessageid'] = $clientMessageId;
			$arrayOut['servermessageid'] = $postedId;
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

    /**
     * Posts messages to potentially multiple chat sessions
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
	 * 
	 * An example of the format of messages is
	 * {
     */
    public function postMessagesAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$postedConfirmations = array();
		$threadConfirmations = array();

		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);

				$data = json_decode($request->getContent(), true);
				$threadMessages = $data['threadmessages'];
				
				$mibewAPIRequests = array();
				$reqTokenToClientMsgId = array();
				$reqTokenToThreadId = array();
				$i = 0;
				foreach($threadMessages as $threadMessage) {
					$threadId = $threadMessage['threadid'];
					$threadConfirmations[$threadId] = array();
					$token = $threadMessage['token'];
					$postedMessages = $threadMessage['messages'];
					foreach($postedMessages as $clientMessage) {
						$functions = array();
						$reqToken = md5(time() + $i++);
						$chatMessage = $clientMessage['message'];
						$clientMessageId = $clientMessage['clientmessageid'];
						
						
						// Create requests for each call.
						$arguments = array(
										'threadId' => $threadId,
										'token' => $token,
										'message' => $chatMessage,
										'user' => false,
										'references' => array(),
										'return' => array(
											'postedId' => 'postedId',
										),
									);
									
						$functions[] = PackageUtil::makeFunction('post', $arguments);
						$mibewAPIRequests[] = PackageUtil::makeRequest($reqToken, $functions);
						$reqTokenToClientMsgId[$reqToken] = $clientMessageId;
						$reqTokenToThreadId[$reqToken] = $threadId;
					}
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

				foreach ($decodedRequests as $retRequest) {
					if (array_key_exists($retRequest['token'], $reqTokenToClientMsgId)) {
						foreach ($retRequest['functions'] as $retFunction) {
							if ($retFunction['function'] == 'result') {
								$confirmation['clientmessageid'] = $reqTokenToClientMsgId[$retRequest['token']];
								$confirmation['servermessageid'] =  $retFunction['arguments']['postedId'];
								$postedConfirmations[] = $confirmation;
								$threadConfirmations[$reqTokenToThreadId[$retRequest['token']]][] = $confirmation;
								// Once we get the result, we move onto the next request
								break;
							}
						}
					}
				}
			}

			// Create the output from the $threadConfirmations array
			$output = array();
			foreach($threadConfirmations as $threadId => $confirmations) {
				$output[] = array('threadid' => $threadId,
								  'confirmations' => $confirmations);
			}
			$arrayOut['threadconfirmations'] = $output;
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

    /**
     * Close the chat session
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function closeChatAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$threadId = $request->attributes->getInt('threadid');
		$token = $request->attributes->get('token');
		$closed = null;
		
		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);
		
				$tmpToken = md5(time());
				$arguments = array(
								'threadId' => $threadId,
								'token' => $token,
								'user' => false,
								'references' => array(),
								'return' => array(
									'closed' => 'closed',
								),
							);
							
				$functions[] = PackageUtil::makeFunction('close', $arguments);
				$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
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
				
				foreach ($decodedRequests as $retRequest) {
					if ($retRequest['token'] == $tmpToken) {
						foreach ($retRequest['functions'] as $retFunction) {
							if ($retFunction['function'] == 'result') {
								$closed = $retFunction['arguments']['closed'];
								
								// For this call we expect only one request, which holds the result.
								break;
							}
						}
					}
					if ($closed != null) {
						break;
					}
				}
			}

			$arrayOut['closed'] = $closed;
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


    /**
     * Ping a chat session
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function pingAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$threadId = $request->attributes->getInt('threadid');
		$token = $request->attributes->get('token');
		$typed = filter_var($request->attributes->get('typed'), FILTER_VALIDATE_BOOLEAN);
		
		// Response variables
		$userTyping = null;
		$canPost = null;
		$threadState = null;
		$threadAgentId = null;
		
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

				$tmpToken = md5(time());
				$arguments = array(
								'threadId' => $threadId,
								'token' => $token,
								'user' => false,
								'typed' => $typed,
								'references' => array(),
								'return' => array(
						            'typing' => 'typing',
						            'canPost' => 'canPost',
						            'threadState' => 'threadState',
						            'threadAgentId' => 'threadAgentId',
								),
							);
				$functions[] = PackageUtil::makeFunction('update', $arguments);
				$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
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
				
				foreach ($decodedRequests as $retRequest) {
					if ($retRequest['token'] == $tmpToken) {
						foreach ($retRequest['functions'] as $retFunction) {
							if ($retFunction['function'] == 'result') {
								$userTyping = $retFunction['arguments']['typing'];
								$threadState = $retFunction['arguments']['threadState'];
								$threadAgentId = $retFunction['arguments']['threadAgentId'];
								$canPost = $retFunction['arguments']['canPost'];
								
								// For this call we expect only one request, which holds the result.
								break;
							}
						}
					}
					if ($userTyping != null) {
						break;
					}
				}
			}

			$arrayOut['usertyping'] = $userTyping;
			$arrayOut['threadstate'] = $threadState;
			$arrayOut['threadagentid'] = $threadAgentId;
			$arrayOut['canpost'] = $canPost;
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

