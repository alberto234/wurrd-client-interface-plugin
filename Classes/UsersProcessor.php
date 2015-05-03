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

use Mibew\RequestProcessor\UsersProcessor as CoreUsersProcessor;

/**
 * Extends the Mibew core ThreadProcessor
 * 
 * TODO: This extension is needed to provide a method to get a new instance of the 
 * 		UsersProcessor, independent of the one created by the AbstractProcessor
 */
class UsersProcessor extends CoreUsersProcessor
{
    /**
     * Gets a new instance of the ThreadProcessor. The getInstance() method returns
	 * a singleton which makes it difficult to use a ThreadProcessor and a UsersProcessor
	 * within the same request. The NotificationController calls both when checking if a 
	 * particular client needs to be notified of new activity
     *
     * @return ThreadProcessor 	A new ThreadProcessor instance
     */
     public static function getNewInstance() {
     	return new static();
	 }

}


 