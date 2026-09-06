<?php
	if(isset($_SESSION['studentLoggedIn']))
	{
		if (isset($_SESSION['forcePasswordChange']) && (int) $_SESSION['forcePasswordChange'] === 1 && basename($_SERVER['PHP_SELF']) !== 'changePassword.php') {
			header("location:changePassword.php?force=1");
			exit;
		}
		if((time() - $_SESSION['studentLast_login'])> 1800)
		{
			header("location:logout.php");
			exit;
		}
		else
		{
			$_SESSION['studentLast_login']=time();
		}
	}
?>
