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
use Wurrd\Mibew\Plugin\ClientInterface\Classes\UsersUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\ThreadUtil;

/**
 * This Controller handles requests from a notification server
 * @author Eyong N <eyongn@scalior.com>		4/28/2015
 */
class NotificationController extends AbstractController
{
    /**
     * Retrieves the server details that are available for 
	 * public consumption
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function checkForUpdatesAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$clientRevision = $request->attributes->get("clientrevision");
		
		try {
			$activeThreads = UsersUtil::getActiveThreads($request, array(
																'lastRevision' => 'lastRevision',
																));
			if ($activeThreads != null) {
				$arrayOut['lastthreadrevision'] = $activeThreads['lastrevision'];
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
     * Retrieves the server details that are available for 
	 * public consumption
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function bulkCheckForUpdatesAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$notificationArray = array();

		try {
			$clientArray = json_decode($request->getContent(), true);
	        $json_error_code = json_last_error();
	        if ($json_error_code != JSON_ERROR_NONE) {
	            // Not valid JSON
	            throw new Exception\HttpException(
								Response::HTTP_BAD_REQUEST,
								Constants::MSG_INVALID_JSON);
			}

			foreach($clientArray as $client) {
				$clientNotification = array();
				$clientNotification['accesstoken'] = $client['accesstoken'];

				// We want to isolate each client and return an appropriate status message for the client
				// so we are surrounding each in a try-catch block
				try {
					$threadListUpdates = $this->checkForThreadListUpdate($client);
					if ($threadListUpdates != null) {
						$clientNotification['threadrevision'] = $threadListUpdates['lastrevision'];
						
						$activeThreadsUpdates = $this->checkForActiveThreadUpdates($client);
						
						if ($activeThreadsUpdates != null) {
							$clientNotification['activethreads'] = $activeThreadsUpdates['threadmessages'];
							$clientNotification['message'] = Constants::MSG_SUCCESS;
						}
					}
				} catch(Exception\HttpException $e) {
					$clientNotification['message'] = $e->getMessage();
				}
				
				$notificationArray[] = $clientNotification;
			}
				
			$arrayOut['notificationlist'] = $notificationArray;	
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


	private function checkForThreadListUpdate($client) {
		if (AccessManagerAPI::isAuthorized($client['accesstoken'])) {
			$authorization = Authorization::loadByAccessToken($client['accesstoken']);
			$operator = operator_by_id($authorization->operatorid);

			$authenticationMgr = new AuthenticationManager();
			$authenticationMgr->loginOperator($operator, false);
			$this->setAuthenticationManager($authenticationMgr);
			
			
			return UsersUtil::getActiveThreads($operator,
												$client['threadrevision'], 
												$authenticationMgr,
												array('lastRevision' => 'lastRevision'));
		}
	} 


	private function checkForActiveThreadUpdates($client) {
		if (AccessManagerAPI::isAuthorized($client['accesstoken'])) {
			$authorization = Authorization::loadByAccessToken($client['accesstoken']);
			$operator = operator_by_id($authorization->operatorid);

			$authenticationMgr = new AuthenticationManager();
			$authenticationMgr->loginOperator($operator, false);
			$this->setAuthenticationManager($authenticationMgr);

			return ThreadUtil::updateMessages($authenticationMgr,
												$client['activethreads'], 
												array('lastId' => 'lastId'));
		}
	} 
}


 