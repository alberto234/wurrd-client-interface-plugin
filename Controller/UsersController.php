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
use Wurrd\Mibew\Plugin\ClientInterface\Classes\UrlGeneratorUtil;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\AuthenticationManager;
use Wurrd\Mibew\Plugin\ClientInterface\Classes\UsersUtil;



 /**
  * Controller that handles users interactions
  * 
  * This controller returns JSON encoded output. The output format can 
  * be abstracted such that there is an output factory that will return
  * the results in the requested format.
  */
class UsersController extends AbstractController
{
    /**
     * Gets a list of active threads
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function updateThreadsAction(Request $request)
	{
		// Initialize the UrlGeneratorUtil singleton
		UrlGeneratorUtil::constructInstance($request, $this);

		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		$clientRevision = $request->attributes->get("clientrevision");
		
		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				$authorization = Authorization::loadByAccessToken($accessToken);
				$operator = operator_by_id($authorization->operatorid);
	
				$authenticationMgr = new AuthenticationManager();
				$authenticationMgr->loginOperator($operator, false);
				$this->setAuthenticationManager($authenticationMgr);
				
				
				$activeThreads = UsersUtil::getActiveThreads($operator,
																$clientRevision, 
																$authenticationMgr,
																array('threads' => 'threads',
																	  'lastRevision' => 'lastRevision',
																));
				if ($activeThreads != null) {
					$arrayOut = $activeThreads;
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

}

