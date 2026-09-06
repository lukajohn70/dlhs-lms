<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	$staffId = $_SESSION['staffId'] ?? 0;
	$testId  = $_POST['testId'] ?? '';
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
	$examineesTableName=$row['examineesTableName'];
	
	$query1="SELECT * FROM `$examineesTableName`";
	$result1 = $connection->query($query1);
	
	if(!$result1)
	{
		echo json_encode($return_arr);
		exit;
	}

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

	if(($result1->num_rows) > 0)
	{
		while($row1 = $result1->fetch_assoc())
		{
			$examineeUserId = $row1['examineeUserId'];
			$examineeClassId = (int)$row1['studentClassId'];

			if ($mode === 'invigilate' && !$isGlobalInvigilator && !in_array($examineeClassId, $allowedClasses)) {
				continue;
			}

			$examineeTeststatus = $row1['testStatus'];
			$testDate = $theDate." (".$theStartHour.":".$theStartMinute." ".$theIsAmOrPm.")";
			$remainingTime = round(($row1['remainingTime']/60), 2);
			$testDuration = $row['duration'];
			$timeStarted = $row1['timeStartedTest'] ?? "";
			$timeSubmitted = $row1['timeSubmittedTest'] ?? "";
			
			// Simple class/year lookup (cached in local scope if needed, but for now direct)
			$classAndYearGroupName = "";
			$getCN = $connection->query("SELECT c.className, y.yearGroupName FROM classes c JOIN yeargroup y ON c.classYearGroup = y.yearGroupId WHERE c.classId='$examineeClassId'");
			if ($getCN && $cnRow = $getCN->fetch_assoc()) {
				$classAndYearGroupName = $cnRow['yearGroupName']." ".$cnRow['className'];
			}
			
			//Getting the examinee name
			$examineeName = "Unknown Student";
			$getExamineeName="SELECT surname, firstName, middleName FROM studentlogin WHERE studentId='$examineeUserId'";
			$result4 = $connection->query($getExamineeName);
			if($result4 && $result4->num_rows > 0)
			{
				$row4 = $result4->fetch_assoc();
				$examineeName = $row4['surname']." ".$row4['firstName']." ".$row4['middleName'];
			}

			$answeredCount = 0;
			$answersTableName = $row['answersTable'];
			if (!empty($answersTableName)) {
				$ansRes = $connection->query("SELECT COUNT(*) as answered FROM `$answersTableName` WHERE userLoginId='$examineeUserId' AND selectedOption != '0' AND selectedOption != ''");
				if ($ansRes) {
					$answeredCount = (int)$ansRes->fetch_assoc()['answered'];
				}
			}
			
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
	}
	
	echo json_encode($return_arr);
?>
