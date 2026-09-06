<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	
	$staffId = $_SESSION['staffId'];
	$return_arr = array();
	$query="SELECT DISTINCT t.* FROM tests t LEFT JOIN test_class_invigilators tci ON t.testId = tci.testId WHERE t.staffId='$staffId' OR t.invigilatorId='$staffId' OR tci.invigilatorId='$staffId' ORDER BY t.testId DESC";
	$result = $connection->query($query);
	
	if($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
		{
			$testId  = $row['testId'];
			$teacherId  = $row['staffId'];
			$testName  = $row['testName'];
			$testDate = $row['testDate'];
			$duration = $row['duration'];
			$startHour = $row['startHour'];
			$startMinute = $row['startMinute'];
			$isAmOrPm = $row['amOrPm'];
			$subjectId = $row['subject'];
            $yearGroupId = $row['yearGroup'];
			$status = $row['status'];
			$academicYearId = $row['academicYearId'];
			$reviewOption = $row['reviewOption'];
			$essayOption = $row['essayOption'];
			$essayTime = $row['essayTime'];
			$invigilatorId = $row['invigilatorId'];
			$setupSummary = dlhsBuildTestSetupSummary($connection, $row);
			
			//Getting the subject name
			$getSubjectName="SELECT * FROM subjects WHERE subjectId='$subjectId'";
			$result1 = $connection->query($getSubjectName);
			$row1 = $result1->fetch_array(MYSQLI_NUM);
			$subjectName = $row1[1];
			
			//Getting the teacher name
			$getTeacherName="SELECT * FROM stafflogin WHERE staffId='$teacherId'";
			$result2 = $connection->query($getTeacherName);
			$row2 = $result2->fetch_array(MYSQLI_NUM);
			$teacherName = $row2[1]." ".$row2[2]." ".$row2[3];
			
			//Getting the academicYearName
			$academicYearName="SELECT * FROM academic_year WHERE academicYearId='$academicYearId'";
			$result3 = $connection->query($academicYearName);
			$row3 = $result3->fetch_array(MYSQLI_NUM);
			$academicYearName = $row3[1];
			
			//Getting the yearGroupName
			$getYearGroupName="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
			$result4 = $connection->query($getYearGroupName);
			$row4 = $result4->fetch_array(MYSQLI_NUM);
			$yearGroupName = $row4[1];
			
			$invigilatorName = "";
			if($invigilatorId != 0)
			{
				//Getting the invigilator name
				$getInvigilatorName="SELECT * FROM stafflogin WHERE staffId='$invigilatorId'";
				$result5 = $connection->query($getInvigilatorName);
				$row5 = $result5->fetch_array(MYSQLI_NUM);
				$invigilatorName = $row5[1].' '.$row5[2].' '.$row5[3];
			}
		
			$return_arr[] = array("testId" => $testId,
						"testName" => $testName,
						"testDate" => $testDate,
						"duration" => $duration,
						"startHour" => $startHour,
						"startMinute" => $startMinute,
						"isAmOrPm" => $isAmOrPm,
						"subjectId" => $subjectId,
						"subjectName" => $subjectName,
						"yearGroupId" => $yearGroupId,
						"yearGroupName" => $yearGroupName,
						"testYear" => $academicYearName,
						"status" => $status,
						"theReviewOption" => $reviewOption,
						"essayOption" => $essayOption,
						"essayTime" => $essayTime,
						"testType" => dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : ''),
						"setupStatus" => $setupSummary['setupStatus'],
						"studentCount" => $setupSummary['studentCount'],
						"questionCount" => $setupSummary['objectiveQuestionCount'] + ($setupSummary['hasEssayQuestions'] ? 1 : 0),
						"status" => $status);
		}
		dlhsSortTestsByTypeAndDate($return_arr);
		echo json_encode($return_arr);
	}
	else
	{
		echo json_encode($return_arr);
	}
	
		
?>
