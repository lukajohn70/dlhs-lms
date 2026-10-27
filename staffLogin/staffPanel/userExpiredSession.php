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

		$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
			|| (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
			|| (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false && isset($_POST['uploadType']));
		
		if(!isset($_SESSION['staffLast_login']) || (time() - $_SESSION['staffLast_login']) > 3600)
		{
			if ($isAjax) {
				http_response_code(401);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'sessionExpired' => true, 'message' => 'Your session has expired. Please log in again.']);
				exit;
			}
			header("location:logout.php");
			exit;
		}
		else
		{
			$_SESSION['staffLast_login']=time();
		}
?>
