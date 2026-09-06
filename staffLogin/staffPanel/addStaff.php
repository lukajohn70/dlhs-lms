<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['userLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/zamani_db_connection.php";
	
		//post for change of password
		if(isset($_POST['reset']))
		{    
			$staffName = mysqli_real_escape_string($connection, $_POST['staffName']);
			$gender = mysqli_real_escape_string($connection, $_POST['gender']);
			$username = mysqli_real_escape_string($connection, $_POST['username']);
			$password = mysqli_real_escape_string($connection, $_POST['password']);
			$status=1;
			
			$query = "SELECT * FROM stafflogin WHERE username='$username'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO stafflogin(staffName, username, password, gender, status) VALUES('{$staffName}', '{$username}', '{$password}', '{$gender}', '{$status}')";
				$result1 = $connection->query($query1);
				
				if ($result1)
				{	
					echo 1;
				}
				else
				{
					echo 2;
				}	
			}
		}
	}
?>