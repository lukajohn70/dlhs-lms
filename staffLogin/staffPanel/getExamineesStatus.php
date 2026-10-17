<?php
session_start();
	$staffId = isset($_SESSION['staffId']) ? (int)$_SESSION['staffId'] : 0;
	session_write_close();

	include "../../db_connection/dlhs_db_connection.php";
	
	$testId  = isset($_POST['testId']) ? mysqli_real_escape_string($connection, (string)$_POST['testId']) : '';
	$mode    = $_POST['mode'] ?? '';
	
	$return_arr = array();

	// Find the test
	$query = "SELECT t.* FROM tests t
	          WHERE t.testId='$testId'
	          AND (
	              t.staffId='$staffId'
	              OR t.invigilatorId='$staffId'
	              OR EXISTS (
	                  SELECT 1 FROM test_class_invigilators tci
	                  WHERE tci.testId = t.testId AND tci.invigilatorId='$staffId'
	              )
	          )";
	$result = $connection->query($query);
	
	if(!$result || $result->num_rows == 0)
	{
		echo json_encode($return_arr);
		exit;
	}

	include "../../scripts/test_workflow_helper.php";

	$row = $result->fetch_assoc();
	dlhsEnsureTestTablesExist($connection, $row);

	$globalTestStatus = (int)$row['status']; // Global test status (0:Locked, 1:Active, 2:Ended)
	$theDate = $row['testDate'];
	$theStartHour = $row['startHour'];
	$theStartMinute= $row['startMinute'];
	$theIsAmOrPm= $row['amOrPm'];
	$examineesTableName = $row['examineesTableName'];
	$answersTableName   = $row['answersTable'];

	$allowedClasses = [];
	$isGlobalInvigilator = false;
	if ($mode === 'invigilate') {
		$invQuery = $connection->query("SELECT classId FROM test_class_invigilators WHERE testId='$testId' AND invigilatorId='$staffId'");
		if ($invQuery && $invQuery->num_rows > 0) {
			while ($iRow = $invQuery->fetch_assoc()) {
				$allowedClasses[] = (int)$iRow['classId'];
			}
		} else {
			if ($row['invigilatorId'] == $staffId) {
				$isGlobalInvigilator = true;
			}
		}
	}
	
	$tableName = $row['tableName']; // questions table name
	$totalQuestions = 0;
	if (!empty($tableName)) {
		$tqRes = $connection->query("SELECT COUNT(*) as total FROM `$tableName` WHERE question != ''");
		if ($tqRes) {
			$totalQuestions = (int)$tqRes->fetch_assoc()['total'];
		}
	}

	$answersJoinSql = "";
	$answersSelectSql = "0 AS answeredCount";
	if (!empty($answersTableName)) {
		$answersSelectSql = "COALESCE(ans.answeredCount, 0) AS answeredCount";
		$answersJoinSql = "LEFT JOIN (
			SELECT userLoginId, COUNT(*) AS answeredCount
			FROM `$answersTableName`
			WHERE selectedOption != '0' AND selectedOption != ''
			GROUP BY userLoginId
		) ans ON ans.userLoginId = e.examineeUserId";
	}

	$query1 = "SELECT e.*,
	                  s.surname, s.firstName, s.middleName,
	                  c.className, y.yearGroupName,
	                  $answersSelectSql
	           FROM `$examineesTableName` e
	           LEFT JOIN studentlogin s ON s.studentId = e.examineeUserId
	           LEFT JOIN classes c ON c.classId = e.studentClassId
	           LEFT JOIN yeargroup y ON y.yearGroupId = c.classYearGroup
	           $answersJoinSql";
	$result1 = $connection->query($query1);
	
	if(!$result1)
	{
		echo json_encode($return_arr);
		exit;
	}

	$testDate = $theDate." (".$theStartHour.":".$theStartMinute." ".$theIsAmOrPm.")";
	$testDuration = $row['duration'];

	while($row1 = $result1->fetch_assoc())
	{
		$examineeUserId = $row1['examineeUserId'];
		$examineeClassId = (int)$row1['studentClassId'];

		if ($mode === 'invigilate' && !$isGlobalInvigilator && !in_array($examineeClassId, $allowedClasses)) {
			continue;
		}

		$examineeTeststatus = $row1['testStatus'];
		$remainingTime = round(($row1['remainingTime']/60), 2);
		$timeStarted = $row1['timeStartedTest'] ?? "";
		$timeSubmitted = $row1['timeSubmittedTest'] ?? "";
		
		$classAndYearGroupName = trim(($row1['yearGroupName'] ?? '') . ' ' . ($row1['className'] ?? ''));
		
		$examineeName = "Unknown Student";
		if (!empty($row1['surname']) || !empty($row1['firstName'])) {
			$examineeName = trim(($row1['surname'] ?? '') . " " . ($row1['firstName'] ?? '') . " " . ($row1['middleName'] ?? ''));
		}

		$answeredCount = (int)($row1['answeredCount'] ?? 0);
		
		$return_arr[] = array(
						"testId" => $testId,
						"examineeUserId" => $examineeUserId,
						"examineeName" => $examineeName,
						"classAndYearGroupName" => $classAndYearGroupName,
						"testDate" => $testDate,
						"duration" => $testDuration,
						"timeStarted" => $timeStarted,
						"timeSubmitted" => $timeSubmitted,
						"remainingTime" => $remainingTime,
						"examineeTeststatus" => $examineeTeststatus,
						"isPaused" => $row1['isPaused'] ?? 0,
						"isStarted" => $row1['isStarted'] ?? 0,
						"globalTestStatus" => $globalTestStatus,
						"answeredCount" => $answeredCount,
						"totalQuestions" => $totalQuestions);
	}
	
	echo json_encode($return_arr);
?>
