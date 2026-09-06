<?php
session_start();
	require_once 'sessionTime.php';
	if (!isset($_SESSION['studentLast_login']) || (time() - $_SESSION['studentLast_login']) > $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../scripts/essay_timer_helper.php";
	
		if(isset($_POST['testId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$studentId = $_SESSION['studentId'];
			$requestedSection = isset($_POST['section']) ? strtolower(trim((string) $_POST['section'])) : 'objective';
			if ($requestedSection !== 'essay') {
				$requestedSection = 'objective';
			}
			
			//Getting the status of the test
			$getTestStatus = "SELECT * FROM tests WHERE testId='$testId'";
			$result1 = $connection->query($getTestStatus);
			$row1 = $result1 ? $result1->fetch_assoc() : null;

			// Debug logging
			$logFile = __DIR__ . '/../../logs/startExam_debug.log';
			$logMsg = date('Y-m-d H:i:s') . " | startExam called | studentId=" . intval($studentId) . " | testId=" . intval($testId) . "\n";
			
			if (!$row1) {
				file_put_contents($logFile, $logMsg . "  -> test not found\n", FILE_APPEND);
				echo 2; 
				exit;
			}

			// Determine examinees table name and test status
			$testStatus      = (int)($row1['status'] ?? 0);
			$testedTableName = $row1['examineesTableName'] ?? '';

			if (empty($testedTableName)) {
				file_put_contents($logFile, $logMsg . "  -> no examinees table configured\n", FILE_APPEND);
				echo 2;
				exit;
			}

			// Get examinee row
			$getTestedTestStatus = "SELECT * FROM `$testedTableName` WHERE examineeUserId='$studentId'";
			$result2 = $connection->query($getTestedTestStatus);

			if ($result2 && ($result2->num_rows > 0)) {
				$row2assoc = $result2->fetch_assoc();
				$testStatusOfTested = isset($row2assoc['testStatus']) ? (int)$row2assoc['testStatus'] : 0;
				$attendance         = isset($row2assoc['attendance']) ? (int)$row2assoc['attendance'] : 0;
				$isStarted          = isset($row2assoc['isStarted']) ? (int)$row2assoc['isStarted'] : 0;
				$isPaused           = isset($row2assoc['isPaused']) ? (int)$row2assoc['isPaused'] : 0;

				$logLine = "  -> student state: testStatus=$testStatusOfTested, attendance=$attendance, isStarted=$isStarted, isPaused=$isPaused, globalStatus=$testStatus\n";
				file_put_contents($logFile, $logMsg . $logLine, FILE_APPEND);

				// If already submitted, always block
				if ($testStatusOfTested == 2) {
					echo 5;
					exit;
				}

				// ROBUST CHECK: Can the student enter?
				// Either Global Start (testStatus == 1) AND marked present
				// OR Individual Start (isStarted == 1)
				
				$canEnter = false;
				if ($isStarted == 1) {
					$canEnter = true;
				} elseif ($testStatus == 1 && $attendance == 1) {
					$canEnter = true;
				}

				if ($canEnter) {
					if ($isPaused == 1) {
						echo 8; // Optional: Custom code for "Paused"
						exit;
					}

					// Essay check
					$essayExists    = dlhsTestHasEssay($connection, (int) $testId);
					$essaySubmitted = $essayExists ? dlhsHasSubmittedEssay($connection, (int) $testId, (int) $studentId) : false;

					if ($requestedSection === 'objective' && $essayExists && !$essaySubmitted && $testStatusOfTested === 0) {
						echo 6; // essay required first
						exit;
					}

					// Let them in
					$_SESSION['idOfTest'] = $testId;
					$_SESSION['testId']   = $testId;
					
					echo ($testStatusOfTested == 1) ? 4 : 1; // 4=Continue, 1=Start
				} else {
					// Cannot enter
					if ($testStatus == 1 && $attendance == 0) {
						echo 7; // Global start but student not marked present
					} else {
						echo 2; // Global test not enabled
					}
				}
			} else {
				// No examinee row found
				file_put_contents($logFile, $logMsg . "  -> student not added to test\n", FILE_APPEND);
				echo 3; 
			}
		}
		else
		{
			echo 0;
		}
	}
?>
