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
			$testId = $_POST['testId'];
			$examineeUserId = $_POST['examineeUserId'];
			
			
			//Getting the duraation to check if subtraction would be negative
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$duration = $row[4];
			$examineesTableName = $row[14];
			$testStatus = $row[11];
			
			if($testStatus == 1)
			{
				$flagExamineeTestStatusTo1 = 1;
				$query1 = "UPDATE $examineesTableName SET testStatus='$flagExamineeTestStatusTo1', timeSubmittedTest='' WHERE examineeUserId='$examineeUserId'";
				$result1 = $connection->query($query1);
				if (!$result1) die($connection->error);
				if($result1)	//if flag of the examinee's test status was flagged from 2 back to 1
				{
					echo 1;
				}
				else			//if flag of the examinee's test status was not successful
				{
					echo 2;
				}
				
			}
			else
			{
					echo 3;		//if the test is not a test in progress
			}
		}
		else
		{
			echo 0;
		}
	}
	
?>