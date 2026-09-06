<?php
session_start();
	require_once 'sessionTime.php';
	error_reporting(0);
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['testId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$checkStudents = $_POST['checkedStudents'];
			
			include_once "../../scripts/test_workflow_helper.php";
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			$row = $result->fetch_assoc();
			dlhsEnsureTestTablesExist($connection, $row);
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
			
			//echo $checkStudents;
			$testStatus=0;
			$str_arr = explode (",", $checkStudents); 
			
			foreach($str_arr as $chk1)  
			{  
				//Getting the class id of the student.
				$query1 = "SELECT * FROM studentlogin WHERE studentId='$chk1'";
				$result1 = $connection->query($query1);
				$row1 = $result1->fetch_assoc();
				$studentClassId=$row1['classId'];
				$studentYearGroupId=$row1['yearGroupId'];
				$studentName=$row1['firstName'];
				$studentEmail=$row1['studentEmail'];
				
				
				$query2 = "SELECT * FROM `$examineesTableName` WHERE examineeUserId='$chk1'";
				$result2 = $connection->query($query2);
				if ($result2 && ($result2->num_rows)<1)
				{
					$query3 = "INSERT INTO `$examineesTableName`(testId, examineeUserId, studentClassId, studentYearGroupId, testStatus, questArray, remainingTime, totalToBeEarned, totalEarned, noOfQuestions, noCorrect, timeStartedTest, timeSubmittedTest) VALUES('{$testId}', '{$chk1}', '{$studentClassId}', '{$studentYearGroupId}', '{$testStatus}', '', 0, 0, 0, 0, 0, '', '')";
					$result3 = $connection->query($query3);				
				}
			}
			echo 1;
		}
	}
?>