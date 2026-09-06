<!DOCTYPE html>
<html lang="en">
<head>
	<title>Student login | Deeper Life High School</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/jpg" href="../adminLogin/images/dlhslogo2.jpg"/>
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
		<link rel="stylesheet" type="text/css" href="../adminLogin/css/role-login.css">
	<style>
		:root {
			--role-primary: #1dbf5f;
			--role-primary-dark: #0e472a;
			--role-accent: #0f9f8f;
			--role-bg-light: #f8fffb;
			--role-border: #dcf1e6;
		}
	</style>
	</head>
	<body style="background: #eef3f9;">
		<main class="role-login-shell" style="--role-bg: url('/dlhs/adminLogin/images/dlhslogo3.jpg');">
			<section class="role-login-brand">
				<div>
					<img src="../adminLogin/images/dlhslogo.png" class="role-login-logo" alt="DLHS logo">
					<p class="role-login-kicker">Student access</p>
					<h1>DLHS Student Portal</h1>
					<p>Access tests, assignments, results, and learning activities through a clear and secure student workspace.</p>
				</div>
				<div class="role-login-foot">Powered by <a href="https://jlm.com.ng" target="_blank" rel="noopener">JLM</a></div>
			</section>
			<section class="role-login-panel">
				<form class="login100-form validate-form role-login-card">
					<p class="role-login-kicker">Welcome back</p>
					<h2>Student Login</h2>
					<p class="lead-copy">Sign in with your student email and password to continue.</p>
					<center><div class="message1" style="color:red;" align="center"></div><div class="message2" style="color:green;" align="center"></div></center>
					<div class="wrap-input100 validate-input" data-validate="Username is required">
						<span class="label-input100">Email</span>
						<input class="input100" type="text" name="studentUsername" placeholder="Enter email">
						<span class="focus-input100"></span>
					</div>
					<div class="wrap-input100 validate-input" data-validate="Password is required">
						<span class="label-input100">Password</span>
						<div style="position:relative;display:flex;align-items:center;">
							<input class="input100" type="password" name="studentPassword" id="studentPassword" placeholder="Enter password" style="padding-right:45px;flex:1;">
							<div style="position:absolute;right:15px;display:flex;align-items:center;">
								<button type="button" onclick="dlhsTogglePwd()" title="Show/hide password" style="background:none;border:none;cursor:pointer;color:#aaa;font-size:15px;padding:2px;">
									<i class="fa fa-eye" id="togglePwdIcon"></i>
								</button>
							</div>
						</div>
						<span class="focus-input100"></span>
					</div>
					<div class="role-login-actions">
						<a href="../">Return to home page</a>
						<button type="submit" class="login100-form-btn" id="submit">Login</button>
					</div>
					<script>
					function dlhsTogglePwd() {
						var f = document.getElementById('studentPassword');
						var ic = document.getElementById('togglePwdIcon');
						if (f.type === 'password') { f.type = 'text'; ic.className = 'fa fa-eye-slash'; }
						else { f.type = 'password'; ic.className = 'fa fa-eye'; }
					}
					</script>
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
