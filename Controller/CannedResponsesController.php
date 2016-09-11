<?php
/*
 * This file is a part of Wurrd ClientInterface Plugin.
 *
 * Copyright 2016 Eyong N <eyongn@scalior.com>.
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
use Wurrd\Mibew\Plugin\ClientInterface\Classes\CannedResponsesUtil;

/**
 * This Controller handles requests concerning
 * canned responses.
 * @author Eyong N <eyongn@scalior.com>		09/03/2016
 */
class CannedResponsesController extends AbstractController
{

    /**
	/**
	 * Retrieves the canned responses for groups that this operator belongs to.
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function getCannedResponsesAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$message = Constants::MSG_SUCCESS;
		$arrayOut = array();
		$accessToken = $request->attributes->get("accesstoken");
		
		try {
			if (AccessManagerAPI::isAuthorized($accessToken)) {
				// returns id, locale, groupid, title, response
				$arrayOut['cannedresponses'] = CannedResponsesUtil::getCannedResponses($accessToken);
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


 