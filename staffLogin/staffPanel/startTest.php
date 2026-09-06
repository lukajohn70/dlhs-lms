<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		echo 0;
	}
	else
	{
		
		if(isset($_POST['testId']))
		{   
			date_default_timezone_set("Africa/Lagos");
			$dateAndTime = date('Y-m-d H:i:s');
			$finalDateAndTime = date('d-m-Y h:i A', strtotime($dateAndTime));
			$finalDate=date('Y-m-d');
			$time=date('H:i:s');
			$finalTime=date('h:i A', strtotime($time));
			
			$testId = $_POST['testId'];
			$row = dlhsFetchTestRowById($connection, $testId);
			if (!$row)
			{
				echo 2;
				exit;
			}

			$readiness = dlhsCheckTestCanStart($connection, $row);
			if (!$readiness['allowed'])
			{
				echo $readiness['code'] === 'students_required' ? 6 : 7;
				exit;
			}

			// Instant Start: bypass strict schedule gates since teacher explicitly triggered start
			$setStatusTo1 = 1;
			$query1 = "UPDATE tests SET status='$setStatusTo1', testDate='$finalDate' WHERE testId='$testId'";
			$result1 = $connection->query($query1);
			if (!$result1) die($connection->error);
			if($result1)
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
