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
 
 
function showForm() {
?>
	<h3>Enter an operator username and password then click to begin test </h3>
	<form action="" method="post">
	  Server URL: <input type="text" name="mibewurl" size="100" placeholder="http://wurrdapp.com/mibew1"><br /><br />
	  Username: <input type="text" name="username"><br /><br />
	  Password: <input type="password" name="password"><br /><br />
	  <i>First run with this unchecked</i><br />
	  Force use of POST: <input type="checkbox" name="usepost" value="true"><br /><br />
		<input type="submit" value="Submit">
	</form>
<?php
}

function getServerInfo($mibewURL) {
	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $mibewURL . "/index.php/wurrd/clientinterface/serverinfo",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	  ),
	));
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	if ($err) {
	  echo "Error getting server info! cURL Error #:" . $err . "<br />";
		return null;
	} else {
		$results = json_decode($response, true);
		$message = $results['message'];
		if ("Success" != $message) {
			echo "Error getting server info! Returned message: " . $message . "<br />";
			return null;
		} else if (!isset($results['apiversion'])) {
			echo "Error getting server info! Missing apiversion <br />";
			return null;
		} else {
			return $results;
		}
	}
}
function testLogin($mibewURL, $username, $password, $deviceUUID) {
	$usePost = isset($_POST['usepost']) && $_POST['usepost'] == "true" ? true : false;
	
	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $mibewURL . "/index.php/wurrd/clientinterface/operator/login",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => $usePost ? "POST" : "PUT",
	  CURLOPT_POSTFIELDS => "{\n  \"username\": \"" . $username . "\",\n  \"password\": \"" . $password . 
	  				"\",\n  \"clientid\": \"AABBB-CCCCC-DEFGHIJ\",\n  \"deviceuuid\": \"". $deviceUUID . "\",\n  ".
	  				"\"type\": \"web\",\n  \"devicename\": \"php test script\",\n  \"platform\": \"desktop\",\n  ". 
	  				"\"os\": \"desktop os\",\n  \"osversion\": \"1.0.0.0\"\n}\n",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "content-type: application/json"
	  ),
	
	
	));
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	if ($err) {
	  echo "Error loging in! cURL Error #:" . $err . "<br />";
		return null;
	} else {
		$results = json_decode($response, true);
		$message = $results['message'];
		if ("Success" != $message) {
			echo "Error loging in! Returned message: " . $message . "<br />";
			return null;
		} else if (!isset($results['authorization'])) {
			echo "Error loging in! No authorization<br />";
			return null;
		} else {
			return $results;
		}
	}
}



function testCheckForUpdates($mibewURL, $accessToken) {
	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $mibewURL . "/index.php/wurrd/clientinterface/notification/bulkcheckforupdates",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => "[{\"accesstoken\":\"" . $accessToken . "\",\"threadrevision\":0,\"activethreads\":[]}]",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "content-type: application/json",
	  ),
	));
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	if ($err) {
	  echo "Error testing check for updates! cURL Error #:" . $err . "<br />";
		return null;
	} else {
		$results = json_decode($response, true);
		$message = $results['message'];
		if ("Success" != $message) {
			echo "Error testing check for updates! Returned message: " . $message . "<br />";
			return null;
		} else  if (!isset($results['notificationlist'])) {
			echo "Error testing check for updates! Missing notification list<br />";
			return null;
		} else {
			return $results;
		}
	}
}


function testLogout($mibewURL, $accessToken, $deviceUUID) {
	$usePost = isset($_POST['usepost']) && $_POST['usepost'] == "true" ? true : false;
	
	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $mibewURL . "/index.php/wurrd/clientinterface/operator/logout/" . $accessToken . "/" . $deviceUUID,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => $usePost ? "POST" : "DELETE",
	  CURLOPT_POSTFIELDS => "",
	  CURLOPT_HTTPHEADER => array(
	    "cache-control: no-cache",
	    "content-type: application/json",
	  ),
	));
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	if ($err) {
	  echo "Error testing logout! cURL Error #:" . $err . "<br />";
		return null;
	} else {
		$results = json_decode($response, true);
		$message = $results['message'];
		if ("Success" != $message) {
			echo "Error testing logout! Returned message: " . $message . "<br />";
			return null;
		} else {
			return $results;
		}
	}
}

function endTest($error = false) {
	echo "<h3>Testing complete</h3>";
	echo "Click the back button to run another test<br/>";
	if ($error) {
		die();
	}
}

?>

<!--
	HTML begins here. The this is where the form to be displayed is generated.

-->


<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">

  <title>Wurrd for Mibew basic test</title>
  <meta name="description" content="Basic testing for the Wurrd plugins for Mibew">
  <meta name="author" content="Scalior">
  
  <!--[if lt IE 9]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
</head>
<body>
<?php 
$mibewURL = isset($_POST['mibewurl']) ? $_POST['mibewurl'] : null;
$username = isset($_POST['username']) ? $_POST['username'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;
$deviceUUID = "aaa-uuu-isdi-php";
if (isset($mibewURL) && count($mibewURL) > 0 && 
	isset($password) && count($password) > 0 &&
	isset($username) && count($username) > 0) {
?>
<h3>Testing in progress...</h3>
<?php
	

	// First get server version
	$serverInfo = getServerInfo($mibewURL);
	if ($serverInfo == null) {
		echo "Failed to get server info. Stopping tests here<br />";
		endTest(true);
	}
	
	echo "Chat server: " . $mibewURL . "<br />";	
	echo "API Version: " . $serverInfo['apiversion'] . "<br />";	
	echo "Use POST flag from chat server: " . ($serverInfo['usepost'] ? "true" : "false") . "<br /></br />";	
		
	echo "Operator: " . $username . "<br /><br />";
		
	echo "Start login testing...<br />";
	$loginResults = testLogin($mibewURL, $username, $password, $deviceUUID);
	
	if ($loginResults == null) {
		echo "Failed to login. Stopping tests here<br />";
		endTest(true);
	}
	
	$accessToken = $loginResults['authorization']['accesstoken'];
	
	if (count($accessToken) == 0) {
		echo "Failed to login. No access token.  Stopping tests here<br />";
		endTest(true);
	}
	echo "Login success<br /><br />";
	
	// Check for updates
	echo "Start check for updates testing...<br />";
	$checkForUpdatesResults = testCheckForUpdates($mibewURL, $accessToken);	

	if ($checkForUpdatesResults == null) {
		echo "Failed to check for updates. Stopping tests here<br />";
		endTest(true);
	}
	echo "Check for updates success<br /><br />";

	// Logout
	echo "Start logout testing...<br />";
	$logoutResults = testLogout($mibewURL, $accessToken, $deviceUUID);	

	if ($logoutResults == null) {
		echo "Failed to logout. Stopping tests here<br />";
		die();
	}
	echo "Logout success<br /><br />";
	endTest(false);
	
} else {
	showForm();
}









?>

&nbsp;
</body>
</html>
