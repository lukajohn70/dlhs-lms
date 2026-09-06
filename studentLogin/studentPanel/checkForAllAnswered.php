<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
	
	$answersTableName=$_SESSION['answersTableName'];	//getting answers table name
	$studentId = $_SESSION['studentId'];
	$totalQuestions = $_SESSION['totalQuestions'];
	
	$valueZeroForNotAnsweredQuestion = "0";
	$query = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND (selectedOption ='A' OR selectedOption ='B' OR selectedOption ='C' OR selectedOption ='D')";
	$result = $connection->query($query);
	if (!$result) die($connection->error);
	if (($result->num_rows)>0)	//if the student has already answered and saved this question
	{
		$numberOfQuestionsAnweredSoFar = $result->num_rows;
		if($totalQuestions == $numberOfQuestionsAnweredSoFar)
		{
			echo 1;	//if all questions have been answered
		}
		else
		{
			if(($totalQuestions - $numberOfQuestionsAnweredSoFar) == 1 )
			{
				$questionIdToSave=$_SESSION['nextQuesIdTosave'];
				//Checking if the sent question Id is the only question yet to be answered
				$query1 = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND questionId ='$questionIdToSave' AND (selectedOption ='A' OR selectedOption ='B' OR selectedOption ='C' OR selectedOption ='D')";
				$result1 = $connection->query($query1);
				if(($result1->num_rows) > 0)
				{
					echo 2;
				}
				elseif(($result1->num_rows) == 0)
				{
					echo 1;
				}
			}
			else
			{
				echo 2;
			}
		}
	}
	else
	{
		echo 2;	//If there is/are still one/more question(s) yet to be answered
	}
?>