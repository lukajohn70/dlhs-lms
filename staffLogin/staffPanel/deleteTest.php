<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		echo 0;
	}
	else
	{
	
		if(isset($_POST['testId']))
		{   
			$testId=$_POST['testId'];
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				$row = $result->fetch_assoc();
				$questionsTableName=$row['tableName'];
				$examineesTableName=$row['examineesTableName'];
				$answersTableName=$row['answersTable'];
				
				//Deleting the question table name
				$query1 = "DROP TABLE IF EXISTS `$questionsTableName`";
				$result1 = $connection->query($query1);
				
				//Deleting the examinees table
				$query2 = "DROP TABLE IF EXISTS `$examineesTableName`";
				$result2 = $connection->query($query2);
				
				//Deleting the answers table of the test
				$query4 = "DROP TABLE IF EXISTS `$answersTableName`";
				$result4 = $connection->query($query4);
				
				
				$query3 = "DELETE FROM tests WHERE testId='$testId'";
				$result3 = $connection->query($query3);
				if (!$result3) die($connection->error);
				if ($result3)
				{
					echo 1;
				}
				else
				{
					echo 2;
				}
			}
		}
		else
		{
			echo 0;
		}
	}
	
?>