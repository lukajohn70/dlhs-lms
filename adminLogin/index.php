<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin login | Deeper Life High School</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/jpg" href="images/dlhslogo2.jpg"/>
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
		<link rel="stylesheet" type="text/css" href="css/role-login.css">
	<style>
		:root {
			--role-primary: #bf1d1d;
			--role-primary-dark: #470e0e;
			--role-accent: #bf7a1d;
			--role-bg-light: #fff8f8;
			--role-border: #f1dce6;
		}
	</style>
	</head>
	<body style="background: #eef3f9;">
		<main class="role-login-shell" style="--role-bg: url('/dlhs/adminLogin/images/dlhslogo3.jpg');">
			<section class="role-login-brand">
				<div>
					<img src="images/dlhslogo.png" class="role-login-logo" alt="DLHS logo">
					<p class="role-login-kicker">Administrative access</p>
					<h1>DLHS Admin Portal</h1>
					<p>Manage school setup, academic records, assessment workflows, staff access, and reporting from one secure workspace.</p>
				</div>
				<div class="role-login-foot">Powered by <a href="https://jlm.com.ng" target="_blank" rel="noopener">JLM</a></div>
			</section>
			<section class="role-login-panel">
				<form class="login100-form validate-form role-login-card">
					<p class="role-login-kicker">Welcome back</p>
					<h2>Admin Login</h2>
					<p class="lead-copy">Sign in with your administrator credentials to continue.</p>
					<div class="message" style="color:red;" align="center"></div><div class="message1" style="color:green;" align="center"></div>
					<div class="wrap-input100 validate-input" data-validate="Username is required">
						<span class="label-input100">Username</span>
						<input class="input100" type="text" name="adminUsername" placeholder="Enter username">
						<span class="focus-input100"></span>
					</div>
					<div class="wrap-input100 validate-input" data-validate="Password is required">
						<span class="label-input100">Password</span>
						<input class="input100" type="password" name="adminPassword" placeholder="Enter password">
						<span class="focus-input100"></span>
					</div>
					<div class="role-login-actions">
						<a href="../">Return to home page</a>
						<button type="submit" class="login100-form-btn" id="submit">Login</button>
					</div>
				</form>
			</section>
		</main>
	
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
