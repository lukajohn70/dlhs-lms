<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
	
	$idToSend = mysqli_real_escape_string($connection, $_POST['idToSend']);
	
	
	$return_arr = array();
	$query="SELECT * FROM tests WHERE status='$idToSend' ORDER BY testId DESC";
	$result = $connection->query($query);
		
	while($row = $result->fetch_array(MYSQLI_NUM))
	{
		$testId  = $row[0];
		$testName  = $row[2];
		$subjectId  = $row[8];
		$yearGroupId = $row[9];
		$testDate = $row[3];
		$startHour = $row[5];
		$startMinute = $row[6];
		$isAmOrPm = $row[7];
		$duration = $row[4].' minutes';
		$teacherId = $row[1];
		
		$startMinuteToDisplay = "";
		if(strlen($startMinute) == 1)
		{
			$startMinuteToDisplay = "0".$startMinute;
		}
		else
		{
			$startMinuteToDisplay = $startMinute;
		}
		$startTime = $startHour.':'.$startMinuteToDisplay.' '.$isAmOrPm;

		//Getting the subjectName
		$getSubjectName="SELECT * FROM subjects WHERE subjectId='$subjectId'";
		$result1 = $connection->query($getSubjectName);
		$row1 = $result1->fetch_array(MYSQLI_NUM);
		$subjectName = $row1[1];

		//Getting the yearGroup name
		$getYearGroupName="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
		$result2 = $connection->query($getYearGroupName);
		$row2 = $result2->fetch_array(MYSQLI_NUM);
		$yearGroupName = $row2[1];

		//Getting the teacher's name
		$getTeacherName="SELECT * FROM stafflogin WHERE staffId='$teacherId'";
		$result3 = $connection->query($getTeacherName);
		$row3 = $result3->fetch_array(MYSQLI_NUM);
		$teacherName = $row3[1]." ".$row3[2]." ".$row3[3];
									
		$return_arr[] = array("testId" => $testId,
							"testName" => $testName,
							"subjectName" => $subjectName,
							"yearGroupName" => $yearGroupName,
							"testDate" => $testDate,
							"startTime" => $startTime,
							"duration" => $duration,
							"teacherName" => $teacherName);
	}
	echo json_encode($return_arr);
?>
