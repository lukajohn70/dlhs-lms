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
		if(isset($_POST['testName']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$testName = mysqli_real_escape_string($connection, $_POST['testName']);
			$testDate = mysqli_real_escape_string($connection, $_POST['testDate']);
			$testDuration = mysqli_real_escape_string($connection, $_POST['testDuration']);
			$startHour = mysqli_real_escape_string($connection, $_POST['startHour']);
			$startMinute = mysqli_real_escape_string($connection, $_POST['startMinute']);
			$amOrPm = mysqli_real_escape_string($connection, $_POST['amOrPm']);
			$subjectId = mysqli_real_escape_string($connection, $_POST['subjectId']);
			$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
			$reviewOption = mysqli_real_escape_string($connection, $_POST['reviewOption']);
			$essayOption = mysqli_real_escape_string($connection, $_POST['essayOption']);
			$theEssayTime = mysqli_real_escape_string($connection, $_POST['theEssayTime']);
			
			if($startHour==12 && $amOrPm=="AM")
			{
				$startHour=0;
			}
			if($startMinute==60)
			{
				$startMinute=00;
			}
			
			$query = "UPDATE tests SET testName='$testName', testDate='$testDate', duration='$testDuration', startHour='$startHour', startMinute='$startMinute', amOrPm='$amOrPm', subject='$subjectId', yearGroup='$yearGroupId', reviewOption='$reviewOption', essayOption='$essayOption', essayTime='$theEssayTime' WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if ($result)
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