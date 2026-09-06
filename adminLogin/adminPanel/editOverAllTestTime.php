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
		if(isset($_POST['testId']))
		{   
			$testId = $_POST['testId'];
			$timeValue = $_POST['timeValue'];
			$timeAction = $_POST['timeAction'];
			
			$operatorToUse="";	//This is used to determine if the duration is to be added or subtracted
			if($timeAction=="add")
			{
				$operatorToUse="+";
			}
			elseif($timeAction=="subtract")
			{
				$operatorToUse="-";
			}
			
			//Getting the duraation to check if subtraction would be negative
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$duration = $row[4];
			if (($operatorToUse == "-") && (($duration - $timeValue) < 0))
			{
					echo 3;
			}
			else
			{
				$query1 = "UPDATE tests SET duration=duration$operatorToUse$timeValue WHERE testId='$testId'";
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
		}
		else
		{
			echo 0;
		}
	}
	
?>