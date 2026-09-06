<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	$staffId = $_SESSION['staffId'];
	$testId = $_POST['testId'];
	
	$return_arr = array();
	$query="SELECT * FROM tests WHERE invigilatorId='$staffId' AND testId='$testId'";
	$result = $connection->query($query);
	if (!$result || $result->num_rows < 1)
	{
		echo json_encode($return_arr);
		exit;
	}

	$row = $result->fetch_assoc();
	$testDate = $row['testDate'];
	$theStartHour = $row['startHour'];
	$theStartMinute= $row['startMinute'];
	$theIsAmOrPm= $row['amOrPm'];
	$examineesTableName=$row['examineesTableName'];
	
	$query1="SELECT * FROM `$examineesTableName`";
	$result1 = $connection->query($query1);
	if($result1 && ($result1->num_rows) > 0)
	{
		while($row1 = $result1->fetch_assoc())
		{
			$testId  = $row1['testId'];
			$subjectId  = $row['subject'];
			$yearGroupId  = $row['yearGroup'];
			$examineeUserId = $row1['examineeUserId'];
			$examineeClassId = $row1['studentClassId'];
			$examineeTeststatus = $row1['testStatus'];
			$remainingTime = round(($row1['remainingTime']/60), 2);
			$testDuration = $row['duration'];
			$testStatus = $row['status'];
			$timeStarted = $row1['timeStartedTest'];
			$timeSubmitted = $row1['timeSubmittedTest'];
						
			//Getting the subject name
			$getSubjectName="SELECT * FROM subjects WHERE subjectId='$subjectId'";
			$result2 = $connection->query($getSubjectName);
			$row2 = $result2->fetch_assoc();
			$subjectName = $row2['subjectName'];
			
			//Getting the year group name
			$getYearGroupName="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
			$result3 = $connection->query($getYearGroupName);
			$row3 = $result3->fetch_assoc();
			$yearGroupName = $row3['yearGroupName'];
			
			//Getting the examinee name
			$getExamineeName="SELECT * FROM studentlogin WHERE studentId='$examineeUserId'";
			$result4 = $connection->query($getExamineeName);
			$row4 = $result4->fetch_assoc();
			$examineeName = $row4['surname']." ".$row4['firstName']." ".$row4['middleName'];
			
			//Getting the examinee class name
			$getExamineeClassName="SELECT * FROM classes WHERE classId='$examineeClassId'";
			$result5 = $connection->query($getExamineeClassName);
			$row5 = $result5->fetch_assoc();
			$examineeClassName = $row5['className'];
			
			$classAndYearGroupName = $yearGroupName." ".$examineeClassName;
			
			$return_arr[] = array("examineeUserId" => $examineeUserId,
							"examineeName" => $examineeName,
							"classAndYearGroupName" => $classAndYearGroupName,
							"testStatus" => $testStatus,
							"examineeTeststatus" => $examineeTeststatus,
							"remainingTime" => $remainingTime,
							"timeStarted" => $timeStarted,
							"testDate" => $testDate,
							"duration" => $testDuration,
							"timeSubmitted" => $timeSubmitted);
		}
		echo json_encode($return_arr);
	}
	else
	{
		echo json_encode($return_arr);
	}
	
	
?>
