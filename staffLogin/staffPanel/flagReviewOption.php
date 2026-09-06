<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
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
			$newReviewOption = mysqli_real_escape_string($connection, $_POST['newReviewOption']);
				
			$query = "UPDATE tests SET reviewOption='$newReviewOption' WHERE testId='$testId'";
			$result = $connection->query($query);
					
			if ($result)
			{	
				echo 1;
			}
			else
			{
				echo 2;
			}
		}
	}
?>