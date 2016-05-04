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

use Mibew\Settings;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This is a utility class that backs the ServerController
 * 
 * @author Eyong N <eyongn@scalior.com>		2/11/2015
 */
class ServerUtil
{
	/**
     * Retrieves detailed server information available only after
	 * authentication
	 * 
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in Constants.php are.
	 * 
	 * @return array|bool  An array with the server details or false if a failure
	 */
	 public static function getDetailedInfo($args) {

        $accessToken = $args[Constants::ACCESSTOKEN_KEY];
		
		if (AccessManagerAPI::isAuthorized($accessToken)) {
			return array('mibewversion' => MIBEW_VERSION,
						 'interfaceversion' => Constants::WCI_VERSION,
						 'apiversion' => Constants::WCI_API_VERSION,
						 'authapiversion' => AccessManagerAPI::getAuthAPIPluginVersion(),
						 'installationid' => Settings::get(Constants::WCI_INSTALLATION_ID_KEY),
						 'name' => Settings::get('title'),
						 'logourl' => Settings::get('logo'),
						 'usepost' => ServerUtil::usePost(),
					);
		} else {
			// This shouldn't get here as an exception will be thrown if access is not valid
			return false;
		}
	 }

	/**
	 * Determine if we should use POST for all 'input' requests
	 */
	public static function usePost() {
		$configs = load_system_configs();
		if (!empty($configs['plugins']) &&
			!empty($configs['plugins']['Wurrd:ClientInterface'])) {
			return filter_var($configs['plugins']['Wurrd:ClientInterface']['use_http_post'], 
								FILTER_VALIDATE_BOOLEAN);
		}
		
		return false;
	}
	

}


 