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
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\OperatorUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\ServerUtil;

 /**
  * Controller that handles operator interactions
  * 
  * This controller returns JSON encoded output. The output format can 
  * be abstracted such that there is an output factory that will return
  * the results in the requested format.
  */
class OperatorController extends AbstractController
{
    /**
     * Authorizes the user of a client to the system,
	 * and returns access and refresh tokens.
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function loginAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();

		$requestPayload = $request->getContent();
		$authRequest = json_decode($requestPayload, true);
        $json_error_code = json_last_error();
        if ($json_error_code != JSON_ERROR_NONE) {
            // Not valid JSON
            $message = Constants::MSG_INVALID_JSON;
			$arrayOut['requestpayload'] = $requestPayload;
            $httpStatus = Response::HTTP_BAD_REQUEST;
		} else {
			try {
				// We need to validate the client id here.
				$clientID = $authRequest[Constants::CLIENTID_KEY];
				if ($clientID != 'AABBB-CCCCC-DEFGHIJ') {
					throw new Exception\AccessDeniedException(Constants::MSG_INVALID_CLIENTID);
				}

				$authorization = AccessManagerAPI::requestAccess($authRequest);
				$authArray = array(
							'accesstoken' => $authorization->accesstoken,
							'accessexpire' => $authorization->dtmaccessexpires,
							'accesscreated' => $authorization->dtmaccesscreated,
							'refreshtoken' => $authorization->refreshtoken,
							'refreshexpire' => $authorization->dtmrefreshexpires,
							'refreshcreated' => $authorization->dtmrefreshcreated,
							);
							
				$operatorArray = OperatorUtil::getInfo(array('accesstoken' => $authorization->accesstoken),
														true);
				$this->fixAvatarURL($operatorArray);				
				$serverInfoArray = ServerUtil::getDetailedInfo(array('accesstoken' => $authorization->accesstoken),
																true);
				
				$arrayOut['authorization'] = $authArray;
				$arrayOut['operator'] = $operatorArray;
				$arrayOut['server'] = $serverInfoArray;
			} catch(Exception\HttpException $e) {
				$httpStatus = $e->getStatusCode();
				$message = $e->getMessage();
			}
		}

		$arrayOut['message'] = $message;
		$response = new Response(json_encode($arrayOut),
								$httpStatus,
								array('content-type' => 'application/json'));
		return $response;
  
    }


    /**
     * Refresh the access token - Used to refresh an expired token
     * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function refreshAccessAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		
		$accessToken = $request->attributes->get("accesstoken");
		$refreshToken = $request->attributes->get("refreshtoken");
		try {
			$authorization = AccessManagerAPI::refreshAccess($accessToken, $refreshToken);
			$arrayOut = array(
						'accesstoken' => $authorization->accesstoken,
						'accessexpire' => $authorization->dtmaccessexpires,
						'accesscreated' => $authorization->dtmaccesscreated,
						'refreshtoken' => $authorization->refreshtoken,
						'refreshexpire' => $authorization->dtmrefreshexpires,
						'refreshcreated' => $authorization->dtmrefreshcreated,
						);
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
     * Log the operator out of the system.
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function logoutAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		
		$accessToken = $request->attributes->get("accesstoken");
		$deviceuuid = $request->attributes->get("deviceuuid");
		try {
			if (AccessManagerAPI::dropAccess($accessToken, $deviceuuid)) {
				// Do nothing, $message has already been set
			}
		} catch(Exception\HttpException $e) {
			$httpStatus = $e->getStatusCode();
			$message = $e->getMessage();
		}
		
		$response = new Response(json_encode(array('accesstoken' => $accessToken,
													'message' => $message)),
								$httpStatus,
								array('content-type' => 'application/json'));
		return $response;
    }

    /**
     * Retrieves the operator's details
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function detailInfoAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		
		$accessToken = $request->attributes->get("accesstoken");

		try {
			$arrayOut = OperatorUtil::getInfo(array(Constants::ACCESSTOKEN_KEY => $accessToken));
			$this->fixAvatarURL($arrayOut);
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
     * Fix the operator avatar url by getting the URL from the relative path.
	 * 
     * @param $operatorArray	An array that contains the element 'avatarurl'
     * @return void 	The input array is changed if needed 
     */
	private function fixAvatarURL(&$operatorArray) {
		if ($operatorArray['avatarurl'] != null && strlen($operatorArray['avatarurl']) > 0) {
			$operatorArray['avatarurl'] = $this->asset($operatorArray['avatarurl']);
		}
	}
}

