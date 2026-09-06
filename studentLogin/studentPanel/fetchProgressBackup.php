<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
	
	//post for change of password
	if(isset($_POST['selectedAnswer']))
	{    
		$selectedAnswer = mysqli_real_escape_string($connection, $_POST['selectedAnswer']);
		$progressValue = mysqli_real_escape_string($connection, $_POST['progressValue'])-1;
		$testId = $_SESSION['testId'];
		$studentId = $_SESSION['studentId'];
		
		$greenFlag=0;
		if (($selectedAnswer =="A") || ($selectedAnswer =="B") || ($selectedAnswer =="C") || ($selectedAnswer =="D") || ($selectedAnswer =="E"))
		{
			$greenFlag=1;
		}
		
		$correctOption=$_SESSION['correctOption'];
		$markForQuestion=$_SESSION['markForQuestion'];
		
		$questionsTableName=$_SESSION['questTableName'];	//variable to retireve the table name
		$questionIdToSave=$_SESSION['nextQuesIdTosave'];	//getting question id that's to be saved
		$answersTableName=$_SESSION['answersTableName'];	//getting answers table name
		$logoutStatus=0;
		
		//date_default_timezone_set("Africa/Lagos");	//setting default time zone.
		$remainingTimeToReturn=0;
		
		$query1 = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND testId='$testId' AND questionId='$questionIdToSave'";
		$result1 = $connection->query($query1);
		if (!$result1) die($connection->error);
		if (($result1->num_rows)>0)	//if the student has already answered and saved this question
		{
			$query3 = "UPDATE $answersTableName SET selectedOption='$selectedAnswer', correctOption='$correctOption', markForQuestion='$markForQuestion' WHERE userLoginId='$studentId' AND testId='$testId' AND questionId='$questionIdToSave'";
			$result3 = $connection->query($query3);
				
			//getting the remaining time and subtracting it from elapsed time
			$testedTableName=$_SESSION['testedTableName'];
			$query5 = "SELECT * FROM $testedTableName WHERE examineeUserId ='$studentId' AND testId='$testId'";
			$result5 = $connection->query($query5);
			$row5 = $result5->fetch_array(MYSQLI_NUM);
			$selectedTime=$row5[7];
			$timeElapsed=time() - $_SESSION['startTime'];
			$_SESSION['startTime']=time();
			$remainingTime=$selectedTime - $timeElapsed;
			$remainingTimeToReturn=$remainingTime;
			
			$query6 = "UPDATE $testedTableName SET remainingTime ='$remainingTime' WHERE testId='$testId' AND examineeUserId='$studentId'";
			$result6 = $connection->query($query6);
			
			if ($remainingTimeToReturn <=0)
			{
				$logoutStatus=1;
			}
			else
			{
				$_SESSION['startTime']=time();
			}
		}
		else
		{
			$query3 = "INSERT INTO $answersTableName(userLoginId, testId, questionId, selectedOption, correctOption, markForQuestion) VALUES('{$studentId}', '{$testId}', '{$questionIdToSave}', '{$selectedAnswer}', '{$correctOption}', '{$markForQuestion}')";
			$result3 = $connection->query($query3);
			
			//getting the remaining time and subtracting it from elapsed time
			$testedTableName=$_SESSION['testedTableName'];
			$query5 = "SELECT * FROM $testedTableName WHERE examineeUserId ='$studentId' AND testId='$testId'";
			$result5 = $connection->query($query5);
			$row5 = $result5->fetch_array(MYSQLI_NUM);
			$selectedTime=$row5[7];
			$timeElapsed=time() - $_SESSION['startTime'];
			$_SESSION['startTime']=time();
			$remainingTime=$selectedTime - $timeElapsed;
			$remainingTimeToReturn=$remainingTime;
		
			
			$query6 = "UPDATE $testedTableName SET remainingTime ='$remainingTime' WHERE testId='$testId' AND examineeUserId='$studentId'";
			$result6 = $connection->query($query6);
			
			if ($remainingTimeToReturn <=0)
			{
				$logoutStatus=1;
			}
			else
			{
				$_SESSION['startTime']=time();
			}
		}
		
				
		
		$arrayQuestionsIds=$_SESSION['questionsIdsArray'];	//variable to hold arrays of questions ids
		
		$currentIndexToDisplay=$progressValue+1;	//variable to hold index of current question number to be displayed 
		$totalQuestions=$_SESSION['totalQuestions'];
		
		
		$questionIdToFetch=$arrayQuestionsIds[$progressValue];
		
		$_SESSION['nextQuesIdTosave']=$questionIdToFetch;
		$_SESSION['prevIndexId']=$progressValue ;		//variable to hold index of previous question that can be selected from the questionIds array
		$_SESSION['nextIndexId']=$progressValue + 1;
		
		$query = "SELECT * FROM $questionsTableName WHERE questionId='$questionIdToFetch'"; //Selecting question to be displayed
		$result = $connection->query($query);
		$row = $result->fetch_array(MYSQLI_NUM);
		$question=$row[1];
		$optionA=$row[2];
		$optionB=$row[3];
		$optionC=$row[4];
		$optionD=$row[5];
		$optionE=$row[6];
		$_SESSION['correctOption']=$row[7];
		$_SESSION['markForQuestion']=$row[8];
		
		
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
		
		echo json_encode(array('q' => $question, 'a' => $optionA, 'b' => $optionB, 'c' => $optionC, 'd' => $optionD, 'e' => $optionE, 'k' => $currentIndexToDisplay, 'f' => $totalQuestions, 'g' => $optionAnsweredQues, 'h' => $remainingTimeToReturn, 'i' => $greenFlag, 'j' => $logoutStatus));
	}
?>