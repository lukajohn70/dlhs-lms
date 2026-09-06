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
		require_once "../../scripts/question_authoring_helper.php";
	
		if(isset($_POST['questionId']))
		{    
			$testId = mysqli_real_escape_string($connection, $_POST['testId']);
			$questionId = mysqli_real_escape_string($connection, $_POST['questionId']);
			$staffId = $_SESSION['staffId'];
						
			$query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			$row = $result->fetch_array(MYSQLI_NUM);
			$questionsTableName = $row[13];
			$examineesTableName = $row[14];
			
			//Checking the examinees table to find out if at least one examinee has started or ended the test this question belongs to.
			$testStatus1 = 1;
			$testStatus2 = 2;
			$query1 = "SELECT * FROM $examineesTableName WHERE testStatus='$testStatus1' OR testStatus='$testStatus2'";
			$result1 = $connection->query($query1);
			if (!$result1) die($connection->error);
			if($result1->num_rows > 0)
			{
				echo 3;
			}
			else
			{
				$query2 = "DELETE FROM $questionsTableName WHERE questionId='$questionId'";
				$result2 = $connection->query($query2);
				if ($result2)
				{	
					dlhsDeleteStimulusLinksForQuestions($connection, $testId, array($questionId));
					echo 1;
				}
				else
				{
					echo 2;
				}
			}
		}
		else
		{
			echo 0;
		}	
	}	
	
?>
