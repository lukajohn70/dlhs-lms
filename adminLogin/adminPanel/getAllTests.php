<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
error_reporting(0);
	
	function getStatus($testId, $status, $testName)
	{
		if($status == 0)
		{
			//$clickToTart =  "clickToStart($testId, $testName)";
			return "Yet to start - <i class='fa fa-hourglass-start' aria-hidden='true' style='color:blue; cursor:pointer;' title='Click to start test' onclick='$clickToTart'></i>";
		}
		elseif($status == 1)
		{
			return 'In progress - <i class="fa fa-hourglass-end" aria-hidden="true" style="color:green; cursor:pointer;" title="Click to end test" onclick="clickToEnd('.$testId.')"></i>';
		}
		elseif($status == 2)
		{
			return 'Ended - <i class="fa fa-refresh" aria-hidden="true" style="color:red; cursor:pointer;" title="Click to reschedule test" onclick="rescheduleTest('.$testId.')"></i>';
		}
	}
	
	$return_arr = array();
	$query="SELECT * FROM tests";
	$result = $connection->query($query);
	
	while($row = $result->fetch_array(MYSQLI_NUM))
	{
		$testId  = $row[0];
		$teacherId  = $row[1];
		$testName  = $row[2];
		$testDate = $row[3];
		$duration = $row[4];
		$startHour = $row[5];
		$startMinute = $row[6];
		$isAmOrPm = $row[7];
		$subjectId = $row[8];
		$yearGroupId = $row[9];
		$status = $row[11];
		$reviewOption = $row[17];
		$academicYearId = $row[16];
		$essayOption = $row[18];
		$essayTime = $row[19];
					
		//Getting the subject name
		$getSubjectName="SELECT * FROM subjects WHERE subjectId='$subjectId'";
		$result1 = $connection->query($getSubjectName);
		$row1 = $result1->fetch_array(MYSQLI_NUM);
		
		//Getting the teacher name
		$getYearGroupName="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
		$result2 = $connection->query($getYearGroupName);
		$row2 = $result2->fetch_array(MYSQLI_NUM);
		
		//Getting the academicYearName
		$academicYearName="SELECT * FROM academic_year WHERE academicYearId='$academicYearId'";
		$result3 = $connection->query($academicYearName);
		$row3 = $result3->fetch_array(MYSQLI_NUM);
		
		//Getting the teacher name
		$getTeacherName="SELECT * FROM stafflogin WHERE staffId='$teacherId'";
		$result4 = $connection->query($getTeacherName);
		$row4 = $result4->fetch_array(MYSQLI_NUM);
		$teacherName = ($row4) ? $row4[1]." ".$row4[2]." ".$row4[3] : "Unknown Teacher";
		
		//$getTheStatus = getStatus($testId, $status, $testName);
		
		$return_arr[] = array("testId" => $testId,
						"testName" => $testName,
						"testDate" => $testDate,
						"duration" => $duration,
						"startHour" => $startHour,
						"startMinute" => $startMinute,
						"isAmOrPm" => $isAmOrPm,
						"subjectId" => $subjectId,
						"subjectName" => ($row1 ? $row1[1] : "Unknown Subject"),
						"yearGroupId" => $yearGroupId,
						"yearGroupName" => ($row2 ? $row2[1] : "Unknown Year Group"),
						"testYear" => ($row3 ? $row3[1] : ""),
						"reviewOption" => $reviewOption,
						"teacherName" => $teacherName,
						"essayOption" => $essayOption,
						"essayTime" => $essayTime,
						"status" => $status);
	}
	echo json_encode($return_arr);
?>
