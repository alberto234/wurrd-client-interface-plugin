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

namespace Wurrd\Mibew\Plugin\ClientInterface\Classes;

use Mibew\Asset\Generator\UrlGeneratorInterface;
use Mibew\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is a utility for URL generation
 * 
 */
class UrlGeneratorUtil
{


	private static $instance = null;
	private $request;
	private $controller;

	public static function constructInstance(Request $request, AbstractController $controller) {
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self($request, $controller);
		}
		return self::$instance;
	}

	public static function getInstance() {
		return self::$instance;
	}

	/**
	 * Generates the full URL based on the input URL including the scheme and host
	 *
	 * @param Request $request - The Symfony request object
	 * @param string $urPath - The url path to be resolved
	 *
	 * @return array|bool  An array with the server details or false if a failure
	 */
	public function fullURL($urlPath) {
		$fullURL = $urlPath;
		if (!is_null($this->request) && !is_null($this->controller)) {
			$fullURL = $this->controller->asset($urlPath, UrlGeneratorInterface::ABSOLUTE_PATH);

			// Fix the url if necessary
			if (strpos($fullURL, '://') === false) {
				// For now we assume that if the scheme wasn't provided then
				// the URL is relative from the root of the domain.
				$fullURL = $this->request->getSchemeAndHttpHost() . $fullURL;
			}
		}

		return $fullURL;
	}


	private function __construct(Request $request, AbstractController $controller) {
		$this->request = $request;
		$this->controller = $controller;
	}
}

