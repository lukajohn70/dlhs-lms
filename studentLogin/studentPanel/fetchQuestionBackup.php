<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
	
	//post for change of password
	if(isset($_POST['testId']))
	{    
		$testId = mysqli_real_escape_string($connection, $_POST['testId']);
		$studentId = $_SESSION['studentId'];
		
		$_SESSION['testId']=$testId;
		
		$nextIndex=2;		//variable to hold index of next question in the $arrayQuestionsIds[] 
		$prevIndex=0;		//variable to hold index of previous question in the $arrayQuestionsIds[]
		$currentIndex=1;	//variable to hold index of current questionId to be displayed 
		$questionIdToFetch;	//Variable to hold the questionId of current question to be displayed
		
		
		$query = "SELECT * FROM tests WHERE testId='$testId'";
		$result = $connection->query($query);
		$row = $result->fetch_array(MYSQLI_NUM);
		$testName=$row[2];
		$testDate=$row[13];
		$testStartTime=$row[13];
		$testDuration=$row[13];
		$questionsTableName=$row[12];
		$answersTableName=$row[15];
				
		$totalQuestions=$_SESSION['totalQuestions'];
		
		$_SESSION['nextIndexId']=1;
		$_SESSION['prevIndexId']=-1;
		
		
		$arrayQuestionsIds=$_SESSION['questionsIdsArray'];
		$questionIdToFetch=$arrayQuestionsIds[0];
		$_SESSION['nextQuesIdTosave']=$questionIdToFetch;
		
		$query2 = "SELECT * FROM $questionsTableName WHERE questionId='$questionIdToFetch'";
		$result2 = $connection->query($query2);
		$row2 = $result2->fetch_array(MYSQLI_NUM);
		$question=$row2[1];
		$optionA=$row2[2];
		$optionB=$row2[3];
		$optionC=$row2[4];
		$optionD=$row2[5];
		$optionE=$row2[6];
		$_SESSION['correctOption']=$row2[7];
		$_SESSION['markForQuestion']=$row2[8];
		
		//Selecting the selected uption of an already answered question.
		$notSelected="0";
		$query4 = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND testId='$testId' AND questionId='$questionIdToFetch' AND selectedOption !='$notSelected'";
		$result4 = $connection->query($query4);
		$optionAnsweredQues="z";
		if (($result4->num_rows)>0)	//if the student has already answered and saved this question
		{
			$row4 = $result4->fetch_array(MYSQLI_NUM);
			$optionAnsweredQues=$row4[4];
		}
		echo json_encode(array('q' => $question, 'a' => $optionA, 'b' => $optionB, 'c' => $optionC, 'd' => $optionD, 'e' => $optionE, 'f' => $totalQuestions, 'g' => $testName, 'h' => $optionAnsweredQues));
	}
?>