<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['studentLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
	
		//post for change of password
		if(isset($_POST['password1']))
		{    
			require_once "../../scripts/password_policy.php";
			
			$passwordRaw = isset($_POST['password1']) ? (string) $_POST['password1'] : '';
			$password2Raw = isset($_POST['password2']) ? (string) $_POST['password2'] : '';
			if ($passwordRaw !== $password2Raw) {
				echo "3|Passwords do not match.";
				exit;
			}
			$policyMessage = dlhsPasswordPolicyMessage($passwordRaw, 'student');
			if ($policyMessage !== '') {
				echo "3|".$policyMessage;
				exit;
			}
			$password1 = mysqli_real_escape_string($connection, $passwordRaw);
			$studentId = $_SESSION['studentId'];
									
			$query = "UPDATE studentlogin SET password='$password1' WHERE studentId='$studentId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if ($result)
			{
				$_SESSION['forcePasswordChange'] = 0;
				echo 1;
			}
			else
			{		
				echo 2;
			}
		}
	}
?>
