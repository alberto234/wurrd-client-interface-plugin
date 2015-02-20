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

use Mibew\API as MibewAPI;
use Mibew\Http\Exception;
use Symfony\Component\HttpFoundation\Response;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This is a utility class that converts Wurrd requests and
 * responses to Mibew API packages and back
 * 
 * @author Eyong N <eyongn@scalior.com>		2/16/2015
 */
class PackageUtil
{
	/**
     * Returns a package formatted for Mibew API requests 
	 * 
	 * @param array		$requests	Requests enclosed in the package
	 * @return array  An array representing the request
	 * 
	 * @author Eyong N 	<eyongn@scalior.com>	2/16/15
	 */
	 public static function encodePackage($requests) 
	 {
		$package = array(
				'signature' => '',
				'proto' => Constants::SUPPORTED_MIBEW_PROTOCOL,
				'async' => true,
				'requests' => $requests,
			);
		return urlencode(json_encode($package));
	 }	
	 
	/**
     * Returns an array that represents a Mibew API function and its arguments 
	 * 
	 * @param string	$functionName	The name of the function
	 * @param array $args - An array containing the arguments of the function
	 * 
	 * @return array  An array representing the function
	 * 
	 * @author Eyong N 	<eyongn@scalior.com>	2/16/15
	 */
	 public static function makeFunction($functionName, $arguments)
	 {
	 	return array(
	 				'function' => $functionName,
	 				'arguments' => $arguments,
	 			);
	 }


	/**
     * Returns an array that represents a Mibew API request and its functions 
	 * 
	 * @param string	$token		The token associated to the request
	 * @param array 	$functions  An array containing the functions
	 * 
	 * @return array  An array representing the request
	 * 
	 * @author Eyong N 	<eyongn@scalior.com>	2/16/15
	 */
	 public static function makeRequest($token, $functions)
	 {
	 	return array(
	 				'token' => $token,
	 				'functions' => $functions,
	 			);
	 }

	/**
     * Returns an array of Mibew API request from a package.
	 * This is used to extract the results from a response
	 * 
	 * @param Object	JSON encoded package
	 * @return array  An array representing the request
	 * @throws \Mibew\Http\Exception\HttpException
	 * 
	 * @author Eyong N 	<eyongn@scalior.com>	2/16/15
	 */
	 public static function getRequestsFromPackage($package)
	 {
	 	$decoded_package = json_decode(urldecode($package), true);
        $json_error_code = json_last_error();
        if ($json_error_code != JSON_ERROR_NONE) {
            // Not valid JSON
            throw new Exception\HttpException(Response::HTTP_INTERNAL_SERVER_ERROR,
                "Package has invalid json structure. JSON error code is '" . $json_error_code . "'");
		}
				
		
		// Check protocol.
		if ($decoded_package['proto'] != Constants::SUPPORTED_MIBEW_PROTOCOL) { 
            throw new Exception\HttpException(Response::HTTP_VERSION_NOT_SUPPORTED,
                "Wurrd Client Interface doesn't support Mibew Core Protocol '" . MibewAPI::PROTOCOL_VERSION . 
                "'. Expects Protocol '" . Constants::SUPPORTED_MIBEW_PROTOCOL . "'");
		}

		// Check signature -- Ignore for now until we understand how it works
		return $decoded_package['requests'];
	 }
	 
}


 