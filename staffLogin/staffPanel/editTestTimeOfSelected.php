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
			$timeValue = ($_POST['timeValue'])*60;
			$timeAction = $_POST['timeAction'];
			$checkStudents = $_POST['checkedStudents'];
			
			$str_arr = explode (",", $checkStudents);
			
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
			$examineesTableName = $row[14];
			$testStatus = $row[11];
			
			if($testStatus == 1)
			{
				$flagToCheckIfANegativeExisted = 0;
				foreach($str_arr as $chk1)  
				{
					//Getting the remaining time to check if subtracting the sent time value would result to a negative number
					$query1 = "SELECT * FROM $examineesTableName WHERE examineeUserId='$chk1'";
					$result1 = $connection->query($query1);
					if (!$result1) die($connection->error);
					$row1 = $result1->fetch_array(MYSQLI_NUM);
					$examineesRemainingTime = $row1[7];
					
					if (($operatorToUse == "-") && (($examineesRemainingTime - $timeValue) < 0))
					{
						$flagToCheckIfANegativeExisted = 1;
					}
					else
					{
						$testStatusInProgress = 1;
						$query1 = "UPDATE $examineesTableName SET remainingTime=remainingTime$operatorToUse$timeValue WHERE examineeUserId='$chk1' AND testStatus='$testStatusInProgress'";
						$result1 = $connection->query($query1);
						if (!$result1) die($connection->error);
					}
				}
				if($flagToCheckIfANegativeExisted == 0)	//if all of the subtractions of the sent in time from an examinees time gives a time in seconds >= 0
				{
					echo 1;
				}
				elseif($flagToCheckIfANegativeExisted == 1)	//if at least the subtraction of the sent in time from an examinees time gives a time in seconds less than 0
				{
					echo 3;
				}
				
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