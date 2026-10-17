<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/essay_timer_helper.php";

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
if ($studentId > 0) {
	$refreshRes = $connection->query("SELECT yearGroupId FROM studentlogin WHERE studentId = {$studentId}");
	if ($refreshRes && $refreshRow = $refreshRes->fetch_assoc()) {
		$_SESSION['studentYearGroup'] = $refreshRow['yearGroupId'];
	}
}
$studentYearGroupId = isset($_SESSION['studentYearGroup']) ? (int) $_SESSION['studentYearGroup'] : 0;

// Release session lock early since we only read from session
session_write_close();

$return_arr = array();

if ($studentId > 0 && $studentYearGroupId > 0) {
	$query = "SELECT * FROM tests WHERE yearGroup='{$studentYearGroupId}' AND (isArchived = 0 OR isArchived IS NULL)";
	$result = $connection->query($query);

	if ($result && $result->num_rows > 0) {
		$tests = array();
		$byTable = array();

		while ($row = $result->fetch_assoc()) {
			$tests[] = $row;
			$examineesTableName = isset($row['examineesTableName']) ? trim($row['examineesTableName']) : '';
			if ($examineesTableName !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $examineesTableName)) {
				$byTable[$examineesTableName][] = $row;
			}
		}

		// Batch-fetch examinee records per unique examinee table (single query per table instead of per test)
		$examineeMap = array();
		$examineeByTable = array();

		foreach ($byTable as $tbl => $tblRows) {
			$checkRes = $connection->query("SELECT * FROM `{$tbl}` WHERE examineeUserId = '{$studentId}'");
			if ($checkRes) {
				while ($eRow = $checkRes->fetch_assoc()) {
					if (isset($eRow['testId']) && (int) $eRow['testId'] > 0) {
						$examineeMap[(int) $eRow['testId']] = $eRow;
					} else {
						$examineeByTable[$tbl] = $eRow;
					}
				}
			}
		}

		// Filter to enrolled tests and collect IDs for batch metadata lookups
		$enrolledTests = array();
		$enrolledTestIds = array();
		$subjectIds = array();
		$yearGroupIds = array();
		$classIds = array();

		foreach ($tests as $row) {
			$testId = (int) $row['testId'];
			$tbl = isset($row['examineesTableName']) ? trim($row['examineesTableName']) : '';
			if ($tbl === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
				continue;
			}

			$examineeRow = isset($examineeMap[$testId]) ? $examineeMap[$testId] : (isset($examineeByTable[$tbl]) ? $examineeByTable[$tbl] : null);
			if (!$examineeRow) {
				continue;
			}

			$enrolledTests[] = array('test' => $row, 'examinee' => $examineeRow);
			$enrolledTestIds[] = $testId;

			if (!empty($row['subject'])) {
				$subjectIds[(int) $row['subject']] = true;
			}
			if (!empty($row['yearGroup'])) {
				$yearGroupIds[(int) $row['yearGroup']] = true;
			}
			if (!empty($examineeRow['studentClassId'])) {
				$classIds[(int) $examineeRow['studentClassId']] = true;
			}
		}

		if (!empty($enrolledTests)) {
			// Batch lookup: Subjects
			$subjectMap = array();
			if (!empty($subjectIds)) {
				$sIds = implode(',', array_keys($subjectIds));
				$sRes = $connection->query("SELECT subjectId, subjectName FROM subjects WHERE subjectId IN ({$sIds})");
				if ($sRes) {
					while ($sRow = $sRes->fetch_assoc()) {
						$subjectMap[(int) $sRow['subjectId']] = $sRow['subjectName'];
					}
				}
			}

			// Batch lookup: Year groups
			$yearGroupMap = array();
			if (!empty($yearGroupIds)) {
				$yIds = implode(',', array_keys($yearGroupIds));
				$yRes = $connection->query("SELECT yearGroupId, yearGroupName FROM yeargroup WHERE yearGroupId IN ({$yIds})");
				if ($yRes) {
					while ($yRow = $yRes->fetch_assoc()) {
						$yearGroupMap[(int) $yRow['yearGroupId']] = $yRow['yearGroupName'];
					}
				}
			}

			// Batch lookup: Classes
			$classMap = array();
			if (!empty($classIds)) {
				$cIds = implode(',', array_keys($classIds));
				$cRes = $connection->query("SELECT classId, className FROM classes WHERE classId IN ({$cIds})");
				if ($cRes) {
					while ($cRow = $cRes->fetch_assoc()) {
						$classMap[(int) $cRow['classId']] = $cRow['className'];
					}
				}
			}

			// Batch lookup: Essay existence
			$testIdsList = implode(',', $enrolledTestIds);
			$hasEssayMap = array();
			$eqRes = $connection->query("SELECT DISTINCT testId FROM essay_questions WHERE testId IN ({$testIdsList})");
			if ($eqRes) {
				while ($eqRow = $eqRes->fetch_assoc()) {
					$hasEssayMap[(int) $eqRow['testId']] = true;
				}
			}

			// Batch lookup: Essay submissions
			$essaySubmittedMap = array();
			$eaRes = $connection->query("SELECT testId, isSubmitted FROM essay_attempts WHERE studentId = {$studentId} AND testId IN ({$testIdsList})");
			if ($eaRes) {
				while ($eaRow = $eaRes->fetch_assoc()) {
					if (!empty($eaRow['isSubmitted'])) {
						$essaySubmittedMap[(int) $eaRow['testId']] = true;
					}
				}
			}

			// Fallback check on essay_answers for any not found in essay_attempts
			$unresolvedEssayTestIds = array();
			foreach ($enrolledTestIds as $tId) {
				if (!isset($essaySubmittedMap[$tId])) {
					$unresolvedEssayTestIds[] = $tId;
				}
			}
			if (!empty($unresolvedEssayTestIds)) {
				$uList = implode(',', $unresolvedEssayTestIds);
				$tableCheck = $connection->query("SHOW TABLES LIKE 'essay_answers'");
				if ($tableCheck && $tableCheck->num_rows > 0) {
					$ansRes = $connection->query("SELECT DISTINCT testId FROM essay_answers WHERE studentId = {$studentId} AND testId IN ({$uList}) AND TRIM(COALESCE(essayAnswer, '')) <> ''");
					if ($ansRes) {
						while ($ansRow = $ansRes->fetch_assoc()) {
							$essaySubmittedMap[(int) $ansRow['testId']] = true;
						}
					}
				}
			}

			// Build final response with zero queries inside the loop
			foreach ($enrolledTests as $item) {
				$row = $item['test'];
				$row1 = $item['examinee'];
				$testId = (int) $row['testId'];
				$studentClassId = isset($row1['studentClassId']) ? (int) $row1['studentClassId'] : 0;
				$subjectId = isset($row['subject']) ? (int) $row['subject'] : 0;
				$yearGroupId = isset($row['yearGroup']) ? (int) $row['yearGroup'] : 0;

				$subjectName = isset($subjectMap[$subjectId]) ? $subjectMap[$subjectId] : '';
				$yearGroupName = isset($yearGroupMap[$yearGroupId]) ? $yearGroupMap[$yearGroupId] : '';
				$className = isset($classMap[$studentClassId]) ? $classMap[$studentClassId] : '';

				$startMinute = (string) (isset($row['startMinute']) ? $row['startMinute'] : '');
				$startMinute1 = strlen($startMinute) > 1 ? $startMinute : "0" . $startMinute;
				$effectiveTestType = dlhsGetEffectiveTestType(isset($row['testType']) ? $row['testType'] : '');
				$essayExists = isset($hasEssayMap[$testId]) || (isset($row['essayOption']) && $row['essayOption'] === 'Yes');
				$essaySubmitted = $essayExists && !empty($essaySubmittedMap[$testId]);

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
}

dlhsSortTestsByTypeAndDate($return_arr);
echo json_encode($return_arr);
?>
