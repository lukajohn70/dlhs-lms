<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/essay_timer_helper.php";
dlhsEnsureTestsCustomTypeColumn($connection);
dlhsEnsureTestsMockPaperColumn($connection);

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
if ($studentId > 0) {
	$refreshRes = $connection->query("SELECT yearGroupId FROM studentlogin WHERE studentId = {$studentId}");
	if ($refreshRes && $refreshRow = $refreshRes->fetch_assoc()) {
		$_SESSION['studentYearGroup'] = $refreshRow['yearGroupId'];
	}
}
$studentYearGroupId = isset($_SESSION['studentYearGroup']) ? (int) $_SESSION['studentYearGroup'] : 0;

$return_arr = array();

if ($studentId > 0 && $studentYearGroupId > 0) {
	$query = "SELECT * FROM tests WHERE yearGroup='{$studentYearGroupId}'";
	$result = $connection->query($query);

	if ($result) {
		while ($row = $result->fetch_assoc()) {
			$testId = (int) $row['testId'];
			$examineesTableName = isset($row['examineesTableName']) ? $row['examineesTableName'] : '';
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $examineesTableName)) {
				continue;
			}

			$checkIfAddedToTest = "SELECT * FROM `" . $examineesTableName . "` WHERE examineeUserId='{$studentId}'";
			$result1 = $connection->query($checkIfAddedToTest);
			if (!$result1 || $result1->num_rows < 1) {
				continue;
			}

			$row1 = $result1->fetch_assoc();
			$studentClassId = isset($row1['studentClassId']) ? $row1['studentClassId'] : 0;
			$subjectId = isset($row['subject']) ? (int) $row['subject'] : 0;
			$yearGroupId = isset($row['yearGroup']) ? (int) $row['yearGroup'] : 0;

			$subjectName = '';
			$getSubjectName = "SELECT subjectName FROM subjects WHERE subjectId='{$subjectId}'";
			$result2 = $connection->query($getSubjectName);
			if ($result2 && $row2 = $result2->fetch_assoc()) {
				$subjectName = $row2['subjectName'];
			}

			$yearGroupName = '';
			$getStudentYearGroupName = "SELECT yearGroupName FROM yeargroup WHERE yearGroupId='{$yearGroupId}'";
			$result3 = $connection->query($getStudentYearGroupName);
			if ($result3 && $row3 = $result3->fetch_assoc()) {
				$yearGroupName = $row3['yearGroupName'];
			}

			$className = '';
			$getStudentClassName = "SELECT className FROM classes WHERE classId='{$studentClassId}'";
			$result4 = $connection->query($getStudentClassName);
			if ($result4 && $row4 = $result4->fetch_assoc()) {
				$className = $row4['className'];
			}

			$startMinute = (string) (isset($row['startMinute']) ? $row['startMinute'] : '');
			$startMinute1 = strlen($startMinute) > 1 ? $startMinute : "0" . $startMinute;
			$effectiveTestType = dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : '');
			$essayExists = dlhsTestHasEssay($connection, $testId) || (isset($row['essayOption']) && $row['essayOption'] === 'Yes');
			$essaySubmitted = $essayExists ? dlhsHasSubmittedEssay($connection, $testId, $studentId) : false;

			$return_arr[] = array(
				"testId" => $testId,
				"testName" => isset($row['testName']) ? $row['testName'] : '',
				"testNameAndSubject" => (isset($row['testName']) ? $row['testName'] : '') . " - (" . $subjectName . ")",
				"testType" => $effectiveTestType,
				"testTypeLabel" => dlhsGetDisplayTestType($effectiveTestType, isset($row['customTestType']) ? $row['customTestType'] : '', isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''),
				"theClass" => trim($yearGroupName . " " . $className),
				"testStatus" => isset($row['status']) ? (int) $row['status'] : 0,
				"testDate" => isset($row['testDate']) ? $row['testDate'] : '',
				"isToday" => (isset($row['testDate']) && $row['testDate'] === date('Y-m-d')),
				"startTime" => (isset($row['startHour']) ? $row['startHour'] : '') . ":" . $startMinute1 . " " . (isset($row['amOrPm']) ? $row['amOrPm'] : ''),
				"duration" => (isset($row['duration']) ? $row['duration'] : '0') . " minutes",
				"examineeTestStatus" => isset($row1['testStatus']) ? (int) $row1['testStatus'] : 0,
				"hasEssay" => $essayExists,
				"essaySubmitted" => $essaySubmitted
			);
		}
	}
}

dlhsSortTestsByTypeAndDate($return_arr);
echo json_encode($return_arr);
?>
