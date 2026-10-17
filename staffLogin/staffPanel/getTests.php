<?php
session_start();
	$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
	session_write_close();

	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	
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

	$selectedTestType = dlhsNormalizeTestType(isset($_GET['testType']) ? $_GET['testType'] : '');
	$selectedSession = isset($_GET['academicSession']) ? trim((string)$_GET['academicSession']) : '';
	$return_arr = array();
	$whereSession = '';
	if ($selectedSession !== '') {
		$escSession = mysqli_real_escape_string($connection, $selectedSession);
		$whereSession = " AND t.academicSession = '$escSession'";
	}
	$query = "SELECT t.*, s.subjectName AS joinedSubjectName, yg.yearGroupName AS joinedYearGroupName, ay.academicYear AS joinedAcademicYear
	          FROM tests t
	          LEFT JOIN subjects s ON s.subjectId = t.subject
	          LEFT JOIN yeargroup yg ON yg.yearGroupId = t.yearGroup
	          LEFT JOIN academic_year ay ON ay.academicYearId = t.academicYearId
	          WHERE t.staffId = '$staffId'" . $whereSession;
	$result = $connection->query($query);
	
	while($result && ($row = $result->fetch_assoc()))
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
		
		$return_arr[] = array("testId" => $testId,
						"testName" => $testName,
						"testDate" => $testDate,
						"duration" => $duration,
						"startHour" => $startHour,
						"startMinute" => $startMinute,
						"isAmOrPm" => $isAmOrPm,
						"subjectId" => $subjectId,
						"subjectName" => $row['joinedSubjectName'] ?? '',
						"yearGroupId" => $yearGroupId,
						"yearGroupName" => $row['joinedYearGroupName'] ?? '',
						"testYear" => $row['joinedAcademicYear'] ?? '',
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
