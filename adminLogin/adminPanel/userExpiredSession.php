<?php
	if(isset($_SESSION['adminLoggedIn']))
	{
		if((time() - $_SESSION['adminLast_login'])> 3600)
		{
			header("location:logout.php");
			exit;
		}
		
		if (isset($_SESSION['forcePasswordChange']) && (int) $_SESSION['forcePasswordChange'] === 1 && basename($_SERVER['PHP_SELF']) !== 'changePassword.php') {
			header("location:changePassword.php?force=1");
			exit;
		}
		else
		{
			$_SESSION['adminLast_login']=time();
		}
	}
?>