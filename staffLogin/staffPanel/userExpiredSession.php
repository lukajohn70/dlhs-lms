<?php
	date_default_timezone_set('Africa/Lagos'); //set choice timezone
	
		if (isset($_SESSION['forcePasswordChange']) && (int) $_SESSION['forcePasswordChange'] === 1 && basename($_SERVER['PHP_SELF']) !== 'changePassword.php') {
			$redirectUrl = isset($_SESSION['adminLoggedIn']) ? "../../adminLogin/adminPanel/changePassword.php?force=1" : "changePassword.php?force=1";
			header("location:$redirectUrl");
			exit;
		}

	// If an admin is accessing staff pages, bypass staff session timeout
		if (isset($_SESSION['adminLoggedIn'])) {
			return;
		}
		
		if(!isset($_SESSION['staffLast_login']) || (time() - $_SESSION['staffLast_login']) > 3600)
		{
			header("location:logout.php");
			exit;
		}
		else
		{
			$_SESSION['staffLast_login']=time();
		}
?>
