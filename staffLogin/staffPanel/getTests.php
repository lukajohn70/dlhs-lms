<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	dlhsEnsureTestsCustomTypeColumn($connection);
	dlhsEnsureTestsMockPaperColumn($connection);
	
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
	$staffId = $_SESSION['staffId'];
	$selectedTestType = dlhsNormalizeTestType(isset($_GET['testType']) ? $_GET['testType'] : '');
	$return_arr = array();
	$query="SELECT * FROM tests WHERE staffId='$staffId'";
	$result = $connection->query($query);
	
	while($row = $result->fetch_assoc())
	{
		$effectiveTestType = dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : '');
		if ($selectedTestType !== '' && $effectiveTestType !== $selectedTestType)
		{
			continue;
		}

		$testId  = $row['testId'];
		$testName  = $row['testName'];
		$testDate = $row['testDate'];
		$duration = $row['duration'];
		$startHour = $row['startHour'];
		$startMinute = (string) $row['startMinute'];
		$isAmOrPm = $row['amOrPm'];
		$subjectId = $row['subject'];
		$yearGroupId = $row['yearGroup'];
		$status = $row['status'];
		$reviewOption = $row['reviewOption'];
		$academicYearId = $row['academicYearId'];
		$essayOption = $row['essayOption'];
		$essayTime = $row['essayTime'];
		$setupSummary = dlhsBuildTestSetupSummary($connection, $row);
					
		//Getting the subject name
		$getSubjectName="SELECT * FROM subjects WHERE subjectId='$subjectId'";
		$result1 = $connection->query($getSubjectName);
		$row1 = $result1->fetch_array(MYSQLI_NUM);
		
		//Getting the teacher name
		$getTeacherName="SELECT * FROM yeargroup WHERE yearGroupId='$yearGroupId'";
		$result2 = $connection->query($getTeacherName);
		$row2 = $result2->fetch_array(MYSQLI_NUM);
		
		//Getting the academicYearName
		$academicYearName="SELECT * FROM academic_year WHERE academicYearId='$academicYearId'";
		$result3 = $connection->query($academicYearName);
		$row3 = $result3->fetch_array(MYSQLI_NUM);
		
		//$getTheStatus = getStatus($testId, $status, $testName);
		
		$return_arr[] = array("testId" => $testId,
						"testName" => $testName,
						"testDate" => $testDate,
						"duration" => $duration,
						"startHour" => $startHour,
						"startMinute" => $startMinute,
						"isAmOrPm" => $isAmOrPm,
						"subjectId" => $subjectId,
						"subjectName" => $row1[1],
						"yearGroupId" => $yearGroupId,
						"yearGroupName" => $row2[1],
						"testYear" => $row3[1],
						"reviewOption" => $reviewOption,
						"essayOption" => $essayOption,
						"essayTime" => $essayTime,
						"academicSession" => isset($row['academicSession']) ? $row['academicSession'] : '',
						"randomizeQuestions" => isset($row['randomizeQuestions']) ? $row['randomizeQuestions'] : 'No',
						"randomizeOptions" => isset($row['randomizeOptions']) ? $row['randomizeOptions'] : 'No',
						"testType" => $effectiveTestType,
						"testTypeLabel" => dlhsGetDisplayTestType($effectiveTestType, isset($row['customTestType']) ? $row['customTestType'] : '', isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''),
						"customTestType" => isset($row['customTestType']) ? $row['customTestType'] : '',
						"mockPaperLabel" => isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : '',
						"setupStatus" => $setupSummary['setupStatus'],
						"studentCount" => $setupSummary['studentCount'],
						"questionCount" => $setupSummary['objectiveQuestionCount'] + ($setupSummary['hasEssayQuestions'] ? 1 : 0),
						"status" => $status);
	}
	dlhsSortTestsByTypeAndDate($return_arr);
	echo json_encode($return_arr);
?>
