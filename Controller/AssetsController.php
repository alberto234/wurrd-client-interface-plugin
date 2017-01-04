<?php
/*
 * This file is a part of Wurrd ClientInterface Plugin.
 *
 * Copyright 2017 Eyong N <eyongn@scalior.com>.
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
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This Controller handles requests concerning
 * assets.
 * @author Eyong N <eyongn@scalior.com>		2017-01-03
 */
class AssetsController extends AbstractController
{

    /**
	/**
	 * Retrieves the canned responses for groups that this operator belongs to.
	 * 
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public function getFlagAction(Request $request)
	{
		$httpStatus = Response::HTTP_NOT_FOUND;
		$message = Constants::MSG_ASSET_NOT_FOUND;;
		$arrayOut = array();
		$countryFlag = $request->attributes->get("countryflag");

		try {
			if (isset($countryFlag) && strlen($countryFlag) > 0) {
				$flagDir = dirname(dirname(__FILE__)) . '/images/flags/';
				$flagFile = $flagDir . $countryFlag;
				$response = new BinaryFileResponse($flagFile);
				return $response;
			}

		} catch (FileNotFoundException $exception) {
			// Defaults already set
		}


		// If we get here we had an error
		$arrayOut['message'] = $message;
		$response = new Response(json_encode($arrayOut),
								$httpStatus,
								array('content-type' => 'application/json'));
		return $response;
    }
}


 