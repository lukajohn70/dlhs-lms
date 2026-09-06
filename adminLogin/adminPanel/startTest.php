<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{
		echo 0;
	}
	else
	{
		if(isset($_POST['testId']))
		{   
			date_default_timezone_set("Africa/Lagos");
			
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$row = dlhsFetchTestRowById($connection, $testId);
			if (!$row)
			{
				echo 2;
				exit;
			}

			// Still check if students and questions are added, but remove date/time gates
			$readiness = dlhsCheckTestCanStart($connection, $row);
			if (!$readiness['allowed'])
			{
				echo $readiness['code'] === 'students_required' ? 6 : 7;
				exit;
			}

			// Force Start
			$setStatusTo1 = 1;
			$query1 = "UPDATE tests SET status='$setStatusTo1' WHERE testId='$testId'";
			$result1 = $connection->query($query1);
			if($result1)
			{
				// Also mark ALL students present and started so they can enter immediately
				$examineesTable = $row['examineesTableName'] ?? '';
				if (!empty($examineesTable)) {
					$connection->query("UPDATE `$examineesTable` SET attendance=1, isStarted=1, isPaused=0 WHERE testId='$testId'");
				}
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
