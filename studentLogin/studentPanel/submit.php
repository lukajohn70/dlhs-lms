<?php
session_start();
	if (!isset($_SESSION['studentId']))
	{
		header('location:logout.php');
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
        require_once "answer_grading_helper.php";
		require_once "../../scripts/essay_timer_helper.php";
				
		//Getting test Result table name from test table
		$testId = $_SESSION['testId'];
		$studentId = $_SESSION['studentId'];
		$query = "SELECT * FROM tests WHERE testId='$testId'";
		$result = $connection->query($query);
		$row = $result->fetch_array(MYSQLI_NUM);
		$testTestedTable=$row[13];
		
		//date_default_timezone_set("Africa/Lagos");	//setting default time zone.
		$timeSubmittedTest=date('h:i A');
		$setStatusTo=2;		
		//Updating candidate's test status to completed
		$query1 = "UPDATE $testTestedTable SET testStatus ='$setStatusTo', timeSubmittedTest='$timeSubmittedTest' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
		$result1 = $connection->query($query1);
		
		//getting the total mark to be earned for the test
		$questionsTableName=$row[12];
        $answersTableName=$row[15];

        dlhsRepairAnswerMetadata($connection, $answersTableName, $questionsTableName, (int) $studentId, (int) $testId);

		$query2 = "SELECT SUM(markForQuestion) AS theSum FROM $questionsTableName";
		$result2 = $connection->query($query2);
		$row2 = $result2->fetch_assoc();
		$totalToBeEarned=$row2['theSum'];
		
		//getting the total mark earned for the test
		$query3 = "SELECT SUM(markForQuestion) AS totalEarned FROM $answersTableName WHERE selectedOption=correctOption AND userLoginId='$studentId' AND testId='$testId'";
		$result3 = $connection->query($query3);
		$row3 = $result3->fetch_assoc();
		$totalEarned=$row3['totalEarned'] ?? 0;
		
		//getting the total number of questions from the questions table
		$query4 = "SELECT * FROM $questionsTableName";
		$result4 = $connection->query($query4);
		$totalQuestions = $result4->num_rows;
		
		//getting the total number of questions score correctly
		$query5 = "SELECT * FROM $answersTableName WHERE selectedOption=correctOption AND userLoginId='$studentId' AND testId='$testId'";
		$result5 = $connection->query($query5);
		$numberCorrect = $result5->num_rows;
		
		//updating candidate's row with score and questions answered/not answered.
		$query6 = "UPDATE $testTestedTable SET totalToBeEarned ='$totalToBeEarned', totalEarned='$totalEarned', noOfQuestions='$totalQuestions', noCorrect='$numberCorrect' WHERE testId ='$testId' AND examineeUserId ='$studentId'";
		$result6 = $connection->query($query6);

		unset($_SESSION['pages']);
		unset($_SESSION['setNext']);
		unset($_SESSION['questionsIdsArray']);
		unset($_SESSION['nextQuestIdToSelect']);
		unset($_SESSION['nextIndexId']);
		unset($_SESSION['prevIndexId']);
		unset($_SESSION['questTableName']);
		unset($_SESSION['totalQuestions']);
		unset($_SESSION['answersTableName']);
		header("location:result/");
		
	}
?>
