<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['testId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$studentId = mysqli_real_escape_string($connection, $_POST['studentId']);
						
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$examineesTableName = $row[14];
			$answersTableName = $row[16];
			
			$query1 = "DELETE FROM $answersTableName WHERE userLoginId='$studentId'";
			$result1 = $connection->query($query1);
			
			$query1 = "DELETE FROM $examineesTableName WHERE examineeUserId='$studentId'";
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
		else
		{
			echo 0;
		}	
	}	
	
?>