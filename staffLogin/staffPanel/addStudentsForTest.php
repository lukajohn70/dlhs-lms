<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../scripts/test_workflow_helper.php";
	
		if(isset($_POST['testId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$checkStudents = $_POST['checkedStudents'];
			
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			$row = $result->fetch_assoc();
			$examineesTableName=$row['examineesTableName'];
			$testTime=$row['startHour'].":".$row['startMinute']." ".$row['amOrPm'];
			$testName= $row['testName'];
			$subjectId= $row['subject'];
			
			$date= $row['testDate'];
			$dayOfWeek = date("l", strtotime($date));
			$day = date("d", strtotime($date));
			$month= date("F", strtotime($date));
			$year=date("yy", strtotime($date));
			$testDate="$dayOfWeek $day $month, $year";
			
			//Getting the subject name
			$query4 = "SELECT * FROM subjects WHERE subjectId='$subjectId'";
			$result4 = $connection->query($query4);
			$row4 = $result4->fetch_assoc();
			$subjectName = $row4['subjectName'];
			
			// Basic validation
			if (empty($examineesTableName)) {
				file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Missing examineesTableName for testId=$testId\n", FILE_APPEND);
				echo 2; // examinees table name not set
				exit;
			}

			// Ensure the per-test examinees table exists — create it if missing
			$checkTable = $connection->query("SHOW TABLES LIKE '{$examineesTableName}'");
			if (!$checkTable || $checkTable->num_rows < 1) {
				// Attempt to auto-create the table rather than failing
				dlhsEnsureTestTablesExist($connection, $row);
				// Re-check after creation attempt
				$checkTable2 = $connection->query("SHOW TABLES LIKE '{$examineesTableName}'");
				if (!$checkTable2 || $checkTable2->num_rows < 1) {
					file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Examinees table could not be created: {$examineesTableName} for testId={$testId}\n", FILE_APPEND);
					echo 3; // examinees table still missing after creation attempt
					exit;
				}
			}

			$testStatus=0;
			$str_arr = explode (",", $checkStudents);
			$insertFailed = false;
			$missingStudents = array();
			
			foreach($str_arr as $chk1)
			{
				   //Getting the class id of the student.
				   $query1 = "SELECT * FROM studentlogin WHERE studentId='$chk1'";
				   $result1 = $connection->query($query1);
				   if (!$result1 || $result1->num_rows < 1) {
					   $missingStudents[] = $chk1;
					   continue;
				   }
				   $row1 = $result1->fetch_assoc();
				   $studentClassId=$row1['classId'];
				   $studentYearGroupId=$row1['yearGroupId'];
				   $studentName=$row1['firstName'];
				   $studentEmail=$row1['studentEmail'];

				   // Always delete essay_answers for this student/test before (re)adding
				   $connection->query("DELETE FROM essay_answers WHERE testId='$testId' AND studentId='$chk1'");

				   // Reset essayStartTime session for this student if possible
				   if (isset($_SESSION['studentId']) && $_SESSION['studentId'] == $chk1 && isset($_SESSION['testId']) && $_SESSION['testId'] == $testId) {
					   unset($_SESSION['essayStartTime']);
				   }

				   $query2 = "SELECT * FROM `{$examineesTableName}` WHERE examineeUserId='$chk1'";
				   $result2 = $connection->query($query2);
				   if (($result2->num_rows)<1)
				   {
					   $query3 = "INSERT INTO `{$examineesTableName}`(testId, examineeUserId, studentClassId, studentYearGroupId, testStatus, questArray, remainingTime, totalToBeEarned, totalEarned, noOfQuestions, noCorrect, timeStartedTest, timeSubmittedTest) VALUES('{$testId}', '{$chk1}', '{$studentClassId}', '{$studentYearGroupId}', '{$testStatus}', '', 0, 0, 0, 0, 0, '', '')";
					   $result3 = $connection->query($query3);
					   if (!$result3) {
						   $insertFailed = true;
						   file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - INSERT failed for student {$chk1} into {$examineesTableName}. SQL Error: " . $connection->error . "\n", FILE_APPEND);
					   }
				   }
			}

			if ($insertFailed) {
				echo 5; // insertion failure
				file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Some inserts failed for testId={$testId}\n", FILE_APPEND);
			}
			else if (!empty($missingStudents)) {
				echo 4; // some students missing in studentlogin
				file_put_contents(__DIR__ . "/../../logs/addStudentsForTest_debug.log", date('Y-m-d H:i:s') . " - Missing student records: " . implode(',', $missingStudents) . " for testId={$testId}\n", FILE_APPEND);
			}
			else {
				echo 1; // success
			}
		}
	}
?>