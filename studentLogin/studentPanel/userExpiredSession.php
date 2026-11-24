<?php
date_default_timezone_set('Africa/Lagos');

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
	|| (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
	|| (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false && isset($_POST['requestId']));

if (isset($_SESSION['studentLoggedIn']) && $_SESSION['studentLoggedIn'] === "yes") {
	if (isset($_SESSION['forcePasswordChange']) && (int) $_SESSION['forcePasswordChange'] === 1 && basename($_SERVER['PHP_SELF']) !== 'changePassword.php') {
		if ($isAjax) {
			http_response_code(403);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => false,
				'forcePasswordChange' => true,
				'message' => 'You must change your default password before you can use this feature. Please visit the Change Password page.'
			]);
			exit;
		}
		header("location:changePassword.php?force=1");
		exit;
	}
	if (!isset($_SESSION['studentLast_login']) || (time() - $_SESSION['studentLast_login']) > 3600) {
		if ($isAjax) {
			http_response_code(401);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode([
				'success' => false,
				'sessionExpired' => true,
				'message' => 'Your session has expired. Please refresh the page and log in again.'
			]);
			exit;
		}
		header("location:logout.php");
		exit;
	} else {
		$_SESSION['studentLast_login'] = time();
	}
}
?>
