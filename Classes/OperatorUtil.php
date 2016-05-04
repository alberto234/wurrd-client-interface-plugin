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
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This is a utility class that backs the OperatorController
 * 
 * @author Eyong N <eyongn@scalior.com>		2/12/2015
 */
class OperatorUtil
{
	/**
     * Retrieves operator information
	 * 
	 * @param array $args - An array containing the arguments needed for the
	 * 					    access token to be generated. The arguments are
	 * 						defined in Constants.php are.
	 * 
	 * @return array|bool  An array with the server details or false if a failure
	 */
	 public static function getInfo($args, $skipChecks = false) {

        $accessToken = $args[Constants::ACCESSTOKEN_KEY];
		
		if ($skipChecks || AccessManagerAPI::isAuthorized($accessToken)) {
			$authorization = Authorization::loadByAccessToken($accessToken);
			$operator = operator_by_id($authorization->operatorid);
			
			return array('operatorid' => $operator['operatorid'],
						 'username' => $operator['vclogin'],
						 'email' => $operator['vcemail'],
						 'commonname' => $operator['vccommonname'],
						 'localename' => $operator['vclocalename'],
						 'avatarurl' => $operator['vcavatar'],
					);
		} else {
			// This shouldn't get here as an exception will be thrown if access is not valid
			return false;
		}
	 }
}


 