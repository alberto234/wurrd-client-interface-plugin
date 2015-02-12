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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This Controller handles requests concerning
 * the state of the server.
 * @author Eyong N <eyongn@scalior.com>		2/11/2015
 */
class ServerController extends AbstractController
{
    /**
     * Retrieves the server details that are available for 
	 * public consumption
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function simpleInfoAction(Request $request)
	{
		$httpStatus = Response::HTTP_OK;
		$arrayOut = array('message' => Constants::MSG_SUCCESS,
						  'mibewversion' => MIBEW_VERSION,
						  'interfaceversion' => Constants::WCI_VERSION,
						  'apiversion' => Constants::WCI_API_VERSION,
						  'authapiversion' => AccessManagerAPI::getAuthAPIPluginVersion());

		$response = new Response(json_encode($arrayOut),
								Response::HTTP_OK,
								array('content-type' => 'application/json'));
		return $response;
    }
}


 