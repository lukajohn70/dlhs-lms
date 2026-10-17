<?php
session_start();
require_once 'sessionTime.php';
if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
	require_once 'unsetSessions.php';
	echo 0;
} else {
	session_write_close();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";

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

		// Basic validation
		if (empty($examineesTableName)) {
			file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Missing examineesTableName for testId=$testId\n", FILE_APPEND);
			echo 2; // examinees table name not set
			exit;
		}

		// Ensure the per-test examinees table exists
		$checkTable = $connection->query("SHOW TABLES LIKE '{$examineesTableName}'");
		if (!$checkTable || $checkTable->num_rows < 1) {
			dlhsEnsureTestTablesExist($connection, $row);
			$checkTable2 = $connection->query("SHOW TABLES LIKE '{$examineesTableName}'");
			if (!$checkTable2 || $checkTable2->num_rows < 1) {
				file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Examinees table could not be created: {$examineesTableName} for testId={$testId}\n", FILE_APPEND);
				echo 3; // examinees table still missing after creation attempt
				exit;
			}
		}

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
		$missingStudents = array();
		$insertFailed = false;

		// 1. Batch fetch student class & year group metadata (1 query)
		$queryStudents = "SELECT studentId, classId, yearGroupId FROM studentlogin WHERE studentId IN ({$idList})";
		$resultStudents = $connection->query($queryStudents);
		$studentMap = array();
		if ($resultStudents) {
			while ($sRow = $resultStudents->fetch_assoc()) {
				$studentMap[(int)$sRow['studentId']] = $sRow;
			}
		}

		// 2. Batch delete old essay answers for this test/student set (1 query)
		$connection->query("DELETE FROM essay_answers WHERE testId='{$testId}' AND studentId IN ({$idList})");

		// 3. Batch check existing examinees in this test (1 query)
		$queryExisting = "SELECT examineeUserId FROM `{$examineesTableName}` WHERE examineeUserId IN ({$idList})";
		$resultExisting = $connection->query($queryExisting);
		$existingMap = array();
		if ($resultExisting) {
			while ($eRow = $resultExisting->fetch_assoc()) {
				$existingMap[(int)$eRow['examineeUserId']] = true;
			}
		}

		// 4. Build batch multi-row INSERT for non-existing students (1 query)
		$insertRows = array();
		foreach ($studentIds as $sId) {
			if (!isset($studentMap[$sId])) {
				$missingStudents[] = $sId;
				continue;
			}
			if (isset($existingMap[$sId])) {
				continue; // already enrolled
			}
			$cId = (int)$studentMap[$sId]['classId'];
			$yId = (int)$studentMap[$sId]['yearGroupId'];
			$insertRows[] = "('{$testId}', '{$sId}', '{$cId}', '{$yId}', '{$testStatus}', '', 0, 0, 0, 0, 0, '', '')";
		}

		if (!empty($insertRows)) {
			$insertSql = "INSERT INTO `{$examineesTableName}`(testId, examineeUserId, studentClassId, studentYearGroupId, testStatus, questArray, remainingTime, totalToBeEarned, totalEarned, noOfQuestions, noCorrect, timeStartedTest, timeSubmittedTest) VALUES " . implode(',', $insertRows);
			$resInsert = $connection->query($insertSql);
			if (!$resInsert) {
				$insertFailed = true;
				file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Batch INSERT failed for testId={$testId}. SQL Error: " . $connection->error . "\n", FILE_APPEND);
			}
		}

		if ($insertFailed) {
			echo 5; // insertion failure
			file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Some inserts failed for testId={$testId}\n", FILE_APPEND);
		} else if (!empty($missingStudents)) {
			echo 4; // some students missing in studentlogin
			file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Missing student records: " . implode(',', $missingStudents) . " for testId={$testId}\n", FILE_APPEND);
		} else {
			echo 1; // success
		}
	}
}
?>