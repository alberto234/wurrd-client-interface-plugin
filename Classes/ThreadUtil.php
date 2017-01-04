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

use Mibew\Database;
use Mibew\Plugin\PluginManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is a utility class that backs the ThreadController
 * 
 * @author Eyong N <eyongn@scalior.com>		4/30/2015
 */
class ThreadUtil
{
    /**
     * Gets a list of messages for the thread
     *
     * @param Request $request Incoming request.
     * @return Response Rendered page content.
     */
    public static function updateMessages($authenticationMgr, $requestThreads, $returnArray)
	{
		if (count($requestThreads) == 0) {
			return null;
		}
		
		$arrayOut = null;
		$threadMessages = array();

		// In order to use as much of the core functionality as possible,
		// we call the ThreadProcessor to process the request. This involves
		// creating a MibewAPI package and passing that in a request to the
		// ThreadProcessor, then decoding the result from the Response object
		// that is returned by the processor. This could be made more
		// efficient if the RequestProcessor provides another method for 
		// processing functions other than the handleRequest() method.

		$mibewAPIRequests = array();
		$threadToToken = array();
		foreach($requestThreads as $oneThread) {
			$functions = array();
			// TODO: We need to verify the parameters
			$tmpToken = md5(time() + $oneThread['token']);
			$arguments = array(
							'threadId' => $oneThread['threadid'],
							'token' => $oneThread['token'],
							'lastId' => $oneThread['lastid'],
							'user' => false, 		// False since this action is reached from an operator client
							'references' => array(),
							'return' => $returnArray,
						);
			$functions[] = PackageUtil::makeFunction('updateMessages', $arguments);
			$mibewAPIRequests[] = PackageUtil::makeRequest($tmpToken, $functions);
			$threadToToken[$tmpToken] = $oneThread['threadid'];
		}
		
		$package = PackageUtil::encodePackage($mibewAPIRequests);

		// Create a new request and store the package in it where the processor expects it.
		$processorRequest = Request::create('.', 'POST');
		$processorRequest->request->set('data', $package);
		
		// Call the request processor
        $processor = ThreadProcessor::getNewInstance();
        $processor->setAuthenticationManager($authenticationMgr);
        $processorResponse = $processor->handleRequest($processorRequest);
		
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
						$threadInfo['lastid'] = $retFunction['arguments']['lastId'];
						
						if (array_key_exists('messages', $returnArray)) {
							$threadInfo['messages'] = $retFunction['arguments']['messages'];
						}
						
						$threadMessages[] = $threadInfo;
						// Once we get the result, we move onto the next request
						break;
					}
				}
			}
		}
		
		$arrayOut['threadmessages'] = $threadMessages;
		return $arrayOut;
    }

	/**
	 * Add locale and groupId to threads
	 */
	public static function decorateThreads(&$threads) {
		if (is_null($threads) || count($threads) == 0) {
			return $threads;
		}

		$threadids = "";
		$first = true;
		foreach ($threads as $thread) {
			if ($first) {
				$first = false;
			} else {
				$threadids .= ", ";
			}
			$threadids .= $thread['id'];
		}
		

		// Can't pass the threadids in a prepared statement because of the following issue:
		// http://stackoverflow.com/questions/1586587/pdo-binding-values-for-mysql-in-statement
		// $values = array(':threadids' => $threadids);
		$values = array();

	    $db = Database::getInstance();
		$extendedThreadInfo = $db->query(
			("SELECT threadid as id, locale, groupid, referer, useragent " .
			 "FROM {thread} " .
			 "WHERE threadid IN ($threadids) "),
    		$values,
			array('return_rows' => Database::RETURN_ALL_ROWS)
		);


		// If the GeoIP address plugin is installed (and enabled)
		// we will use it to get geo information
		$geoIPPlugin = null;
		$pluginManager = PluginManager::getInstance();
		if ($pluginManager->hasPlugin('Mibew:GeoIp')) {
			$geoIPPlugin = $pluginManager->getPlugin('Mibew:GeoIp');
			// We could also do version checks if necessary
		}

		foreach ($threads as &$anotherThread) {
			foreach ($extendedThreadInfo as $key => $oneExtendedInfo) {
				if ($anotherThread['id'] == $oneExtendedInfo['id']) {
					$anotherThread['locale'] = $oneExtendedInfo['locale'];
					$anotherThread['groupid'] = (int)$oneExtendedInfo['groupid'];
					$anotherThread['referrer'] = $oneExtendedInfo['referer'];
					$anotherThread['fullUserAgent'] = $oneExtendedInfo['useragent'];

					if ($geoIPPlugin != null) {
						// An IP string can contain more than one IP adress. For example it
						// can be something like this: "x.x.x.x (x.x.x.x)". Thus we need to
						// extract all IPS from the string and use the last one.

						// Question (EN 2017-01-03): Can this handle IPv6 addresses? The better question to ask
						// is whether the MaxMind library can handle IPv6 addresses.
						$count = preg_match_all(
							"/(?:(?:[0-9]{1,3}\.){3}[0-9]{1,3})/",
							$anotherThread['remote'],
							$matches
						);
						if ($count > 0) {
							try {
								$ip = end($matches[0]);
								$info = $geoIPPlugin->getGeoInfo($ip, get_current_locale());
								$anotherThread['countryName'] = $info['country_name'] ?: '';
								$anotherThread['countryCode'] = $info['country_code'] ? strtolower($info['country_code']) : '';
								$anotherThread['city'] = $info['city'] ?: '';
								$anotherThread['latitude'] = $info['latitude'];
								$anotherThread['longitude'] = $info['longitude'];

								if (strlen($anotherThread['countryCode']) > 0) {
									$urlGenerator = UrlGeneratorUtil::getInstance();
									if ($urlGenerator != null) {
										$anotherThread['flagUrl'] = UrlGeneratorUtil::getInstance()->fullURL('index.php/wurrd/clientinterface/assets/images/flags/' . $anotherThread['countryCode'] . '.png');
									}
								}
							} catch (\InvalidArgumentException $exception) {
								// Do nothing
							}
						}

					}
					unset($extendedThreadInfo[$key]);
					break;
				}
			}
		}
		return $threads;
	}
}

