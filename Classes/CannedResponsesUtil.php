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

namespace Wurrd\Mibew\Plugin\ClientInterface\Classes;

use Mibew\Database;
use Wurrd\Mibew\Plugin\AuthAPI\Classes\AccessManagerAPI;
use Wurrd\Mibew\Plugin\AuthAPI\Model\Authorization;
use Wurrd\Mibew\Plugin\ClientInterface\Constants;

/**
 * This is a utility class that backs the CannedResponsesController
 * 
 * @author Eyong N <eyongn@scalior.com>		09/03/2016
 */
class CannedResponsesUtil
{
	/**
	 * Retrieves the canned responses for groups that this operator belongs to.
	 * 
	 * @param String $accessToken - The access token
	 * 
	 * @return array  An array containing the canned responses,
	 * 					or an empty array if none is available 
	 */
	 public static function getCannedResponses($accessToken) {

		// We assume that the $accessToken is valid
		$authorization = Authorization::loadByAccessToken($accessToken);
		if (is_null($authorization)) {
			return array();
		}
		
		return CannedResponsesUtil::loadAllCannedResponses($authorization->operatorid);
	 }


	/**
	 * Get an array of all canned responses for this operator.
	 */
	private static function loadAllCannedResponses($operatorId) {
		$cannedResponses = array();
	    $db = Database::getInstance();
		$values = array(':operatorid' => $operatorId);
		
		$cannedResponses = $db->query(
			("SELECT cm.id, cm.locale, cm.groupid, cm.vctitle as title, cm.vcvalue as response " .
			 "FROM {cannedmessage} cm " .
			 "WHERE cm.groupid IS NULL OR cm.groupid = 0 OR cm.groupid IN ( " .
				"SELECT otg.groupid FROM {operatortoopgroup} otg " .
    			"WHERE otg.operatorid = :operatorid) "),
    		$values,
			array('return_rows' => Database::RETURN_ALL_ROWS)
		);
		
		return $cannedResponses;
	}

}


 