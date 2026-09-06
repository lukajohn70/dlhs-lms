<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
dlhsEnsureTestsCustomTypeColumn($connection);
dlhsEnsureTestsMockPaperColumn($connection);

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
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

			$statusToCheckFor = 2;
			$checkIfAddedToTest = "SELECT * FROM `" . $examineesTableName . "` WHERE examineeUserId='{$studentId}' AND testStatus='{$statusToCheckFor}'";
			$result1 = $connection->query($checkIfAddedToTest);
			if (!$result1 || $result1->num_rows < 1) {
				continue;
			}

			$row1 = $result1->fetch_assoc();
			$subjectId = isset($row['subject']) ? (int) $row['subject'] : 0;
			$subjectName = '';
			$getSubjectName = "SELECT subjectName FROM subjects WHERE subjectId='{$subjectId}'";
			$result2 = $connection->query($getSubjectName);
			if ($result2 && $row2 = $result2->fetch_assoc()) {
				$subjectName = $row2['subjectName'];
			}

			$startMinute = (string) (isset($row['startMinute']) ? $row['startMinute'] : '');
			$startMinute1 = strlen($startMinute) > 1 ? $startMinute : "0" . $startMinute;
			$effectiveTestType = dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : '');

			$return_arr[] = array(
				"testId" => $testId,
				"testName" => isset($row['testName']) ? $row['testName'] : '',
				"testStatus" => isset($row['status']) ? (int) $row['status'] : 0,
				"examineeUserId" => $studentId,
				"testNameAndSubject" => (isset($row['testName']) ? $row['testName'] : '') . " - " . $subjectName,
				"testType" => $effectiveTestType,
				"testTypeLabel" => dlhsGetDisplayTestType($effectiveTestType, isset($row['customTestType']) ? $row['customTestType'] : '', isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''),
				"testDate" => isset($row['testDate']) ? $row['testDate'] : '',
				"startTime" => (isset($row['startHour']) ? $row['startHour'] : '') . ":" . $startMinute1 . " " . (isset($row['amOrPm']) ? $row['amOrPm'] : ''),
				"duration" => isset($row['duration']) ? $row['duration'] : 0,
				"timeStarted" => isset($row1['timeStartedTest']) ? $row1['timeStartedTest'] : '',
				"timeSubmitted" => isset($row1['timeSubmittedTest']) ? $row1['timeSubmittedTest'] : '',
				"examineeTestStatus" => isset($row1['testStatus']) ? (int) $row1['testStatus'] : 0,
				"reviewOption" => isset($row['reviewOption']) ? $row['reviewOption'] : 'No'
			);
		}
	}
}

dlhsSortTestsByTypeAndDate($return_arr);
echo json_encode($return_arr);
?>
