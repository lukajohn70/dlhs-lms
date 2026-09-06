<?php
session_start();
require_once 'sessionTime.php';

// Check if user is logged in and has required session variables
if (!isset($_SESSION['userLast_login']) || !isset($_SESSION['studentId']) || !isset($_SESSION['idOfTest'])) {
    header("Location: ../login.php");
    exit();
}

if ((time() - $_SESSION['userLast_login']) > $allottedTime) {	
    require_once 'unsetSessions.php';
    header("Location: logout.php");
    exit();
} else {
    require_once "../../db_connection/dlhs_db_connection.php";
	
    // Sanitize session variables
    $testId = mysqli_real_escape_string($connection, $_SESSION['idOfTest']);
    $studentId = mysqli_real_escape_string($connection, $_SESSION['studentId']);
    
    // Prepare and execute query
    $query = "SELECT * FROM exam_table WHERE testId = ? AND examineeUserId = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ss", $testId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // For debugging (remove in production)
    if ($connection->error) {
        die("Query failed: " . $connection->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Zamani | Admin login</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="fonts/Linearicons-Free-v1.0.0/icon-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="css/util.css">
	<link rel="stylesheet" type="text/css" href="css/main.css">
<!--===============================================================================================-->
<script src="jQuery3.3.1.js"></script>

</head>
<body>
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(images/zamaniPic1.png);">
					<span class="login100-form-title-1">
						Admin Login
					</span>
				</div>
				
				<form class="login100-form validate-form">
					<div class="message" style="color:red;" align="center"></div><div class="message1" style="color:green;" align="center"></div>
					<div class="wrap-input100 validate-input m-b-26" data-validate="Username is required">
						<span class="label-input100">Username</span>
						<input class="input100" type="text" name="adminUsername" placeholder="Enter username">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
						<span class="label-input100">Password</span>
						<input class="input100" type="password" name="adminPassword" placeholder="Enter password">
						<span class="focus-input100"></span>
					</div>

					<div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
							<input class="input-checkbox100" id="ckb1" type="checkbox" name="remember-me">
							<label class="label-checkbox100" for="ckb1">
								Remember me
							</label>
						</div>

						<div>
							<a href="#" class="txt1">
								Forgot Password?
							</a>
						</div>
					</div>

					<div class="container-login100-form-btn">
						<button type="submit" class="login100-form-btn" id="submit">
							Login
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
<!--===============================================================================================-->
	<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/bootstrap/js/popper.js"></script>
	<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="js/main.js"></script>
	<script src="loginAjax.js"></script>

</body>
</html>