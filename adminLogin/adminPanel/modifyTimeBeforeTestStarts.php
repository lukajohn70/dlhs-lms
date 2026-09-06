<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'dlhs_db_connection.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
	
		//post for change of password
		if(isset($_POST['testId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			
			//Getting the name of the examinees table name
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$testStatus = $row[11];
			
			if($testStatus == 0)	//If test has not started
			{
				echo 1;
			}
			elseif($testStatus == 1)
			{
				echo 2;
			}
			elseif($testStatus == 2)
			{
				echo 3;
			}
		}
		else
		{
			echo 0;
		}
	}
?>