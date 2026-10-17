<?php
session_start();
require_once 'sessionTime.php';
error_reporting(0);
if ((time() - $_SESSION['adminLast_login']) > $allottedTime) {
	require_once 'unsetSessions.php';
	echo 0;
} else {
	session_write_close();
	include "../../db_connection/dlhs_db_connection.php";
	include_once "../../scripts/test_workflow_helper.php";

	if (isset($_POST['testId'])) {
		$testId = (int)$_POST['testId'];
		$checkStudents = $_POST['checkedStudents'] ?? '';

		$query = "SELECT * FROM tests WHERE testId='$testId'";
		$result = $connection->query($query);
		$row = $result ? $result->fetch_assoc() : null;
		if (!$row) {
			echo 0;
			exit;
		}

		dlhsEnsureTestTablesExist($connection, $row);
		$examineesTableName = $row['examineesTableName'];
		$testTime = $row['startHour'] . ":" . $row['startMinute'] . " " . $row['amOrPm'];
		$testName = $row['testName'];
		$subjectId = $row['subject'];

		$date = $row['testDate'];
		$dayOfWeek = date("l", strtotime($date));
		$day = date("d", strtotime($date));
		$month = date("F", strtotime($date));
		$year = date("Y", strtotime($date));
		$testDate = "$dayOfWeek $day $month, $year";

		$testStatus = 0;
		$rawArr = explode(",", (string)$checkStudents);
		$studentIds = array();
		foreach ($rawArr as $chk) {
			$cleanId = (int)trim($chk);
			if ($cleanId > 0) {
				$studentIds[$cleanId] = $cleanId;
			}
		}

		if (empty($studentIds)) {
			echo 1;
			exit;
		}

		$idList = implode(',', $studentIds);

		// 1. Batch fetch student metadata (1 query)
		$queryStudents = "SELECT studentId, classId, yearGroupId FROM studentlogin WHERE studentId IN ({$idList})";
		$resultStudents = $connection->query($queryStudents);
		$studentMap = array();
		if ($resultStudents) {
			while ($sRow = $resultStudents->fetch_assoc()) {
				$studentMap[(int)$sRow['studentId']] = $sRow;
			}
		}

		// 2. Batch check existing examinees in this test (1 query)
		$queryExisting = "SELECT examineeUserId FROM `{$examineesTableName}` WHERE examineeUserId IN ({$idList})";
		$resultExisting = $connection->query($queryExisting);
		$existingMap = array();
		if ($resultExisting) {
			while ($eRow = $resultExisting->fetch_assoc()) {
				$existingMap[(int)$eRow['examineeUserId']] = true;
			}
		}

		// 3. Multi-row batch INSERT (1 query)
		$insertRows = array();
		foreach ($studentIds as $sId) {
			if (!isset($studentMap[$sId]) || isset($existingMap[$sId])) {
				continue;
			}
			$cId = (int)$studentMap[$sId]['classId'];
			$yId = (int)$studentMap[$sId]['yearGroupId'];
			$insertRows[] = "('{$testId}', '{$sId}', '{$cId}', '{$yId}', '{$testStatus}', '', 0, 0, 0, 0, 0, '', '')";
		}

		if (!empty($insertRows)) {
			$insertSql = "INSERT INTO `{$examineesTableName}`(testId, examineeUserId, studentClassId, studentYearGroupId, testStatus, questArray, remainingTime, totalToBeEarned, totalEarned, noOfQuestions, noCorrect, timeStartedTest, timeSubmittedTest) VALUES " . implode(',', $insertRows);
			$connection->query($insertSql);
		}

		echo 1;
	}
}
?>