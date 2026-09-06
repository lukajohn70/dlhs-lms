<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		if(isset($_POST['resultYearId']))
		{   
			$resultYearId = mysqli_real_escape_string($connection, $_POST['resultYearId']);
			$termLockStatus = mysqli_real_escape_string($connection, $_POST['termLockStatus']);
			$termNumToSendToChangeStatus = mysqli_real_escape_string($connection, $_POST['termNumToSendToChangeStatus']);
			
			//getting term's column to edit
			$termColumnToEdit = "";
			if($termNumToSendToChangeStatus == 1)
			{
				$termColumnToEdit = "term1LockStatus";
			}
			elseif($termNumToSendToChangeStatus == 2)
			{
				$termColumnToEdit = "term2LockStatus";
			}
			elseif($termNumToSendToChangeStatus == 3)
			{
				$termColumnToEdit = "term3LockStatus";
			}
			
			
			$query1 = "UPDATE results_table_names SET $termColumnToEdit='$termLockStatus' WHERE resultTableNameId='$resultYearId'";
			$result1 = $connection->query($query1);
			if (!$result1) die($connection->error);
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