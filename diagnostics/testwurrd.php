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
 
 
 
?>


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
if (isset($_POST['username']) && isset($_POST['password'])) {
	
} else {
?>
<h3>Enter an operator username and password then click to begin test </h3>
<form action="" method="post">
  Username: <input type="text" name="username"><br /><br />
  Password: <input type="password" name="password"><br /><br />
	<input type="submit" value="Submit">
</form>
<?php
}
?>

&nbsp;
</body>
</html>

