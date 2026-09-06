<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		
	
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
				$policyMessage = dlhsPasswordPolicyMessage($passwordRaw);
				if ($policyMessage !== '') {
					echo "3|".$policyMessage;
					exit;
				}
				include "../../db_connection/dlhs_db_connection.php";
				$password1 = mysqli_real_escape_string($connection, $passwordRaw);
				$staffId = $_SESSION['staffId'];
									
			$query = "UPDATE stafflogin SET password='$password1' WHERE staffId='$staffId'";
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
		else
		{
			echo 0;
		}
	}
?>
