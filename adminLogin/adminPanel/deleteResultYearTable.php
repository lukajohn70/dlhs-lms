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
			
			//Getting the result table name of selected year
			$query = "SELECT * FROM results_table_names WHERE resultTableNameId='$resultYearId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$resultTableName = $row[2];
			
			//Dropping the result table name for the selected year
			$query0 = "DROP TABLE $resultTableName";
			$result0 = $connection->query($query0);
			if (!$result0) die($connection->error);
			
			if($result0)
			{
				//deleting a result table name
				$query1 = "DELETE FROM results_table_names WHERE resultTableNameId='$resultYearId'";
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
				echo 3;
			}
		}
		else
		{
			echo 0;
		}
	}	
	
?>