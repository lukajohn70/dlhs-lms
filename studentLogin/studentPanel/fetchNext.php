<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
	require_once "shuffle_options_helper.php";
	require_once "answer_grading_helper.php";
	
	//post for change of password
	if(isset($_POST['selectedAnswer']))
	{    
		$selectedAnswer = mysqli_real_escape_string($connection, $_POST['selectedAnswer']);
		$testId = $_SESSION['testId'];
		$studentId = $_SESSION['studentId'];
		
		$questionsTableName=$_SESSION['questTableName'];	//variable to retireve the table name
		$questionIdToSave=$_SESSION['nextQuesIdTosave'];	//getting question id that's to be saved
		$answersTableName=$_SESSION['answersTableName'];	//getting answers table name
		$logoutStatus=0;
		
		//date_default_timezone_set("Africa/Lagos");	//setting default time zone.
		$remainingTimeToReturn=0;
		
		$query7 = "SELECT status, randomizeOptions FROM tests WHERE testId='$testId'";
		$result7 = $connection->query($query7);
		$row7 = $result7 ? $result7->fetch_assoc() : null;
		$testStatus = $row7['status'] ?? 1;
		$randomizeOptions = $row7['randomizeOptions'] ?? 'No';

		$selectedAnswerToStore = $selectedAnswer;
		if ($randomizeOptions === 'Yes' && in_array($selectedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
			$querySaveQuestion = "SELECT optionA, optionB, optionC, optionD, optionE FROM $questionsTableName WHERE questionId='$questionIdToSave'";
			$resultSaveQuestion = $connection->query($querySaveQuestion);
			$rowSaveQuestion = $resultSaveQuestion ? $resultSaveQuestion->fetch_array(MYSQLI_ASSOC) : null;
			if ($rowSaveQuestion) {
				$selectedAnswerToStore = reverseTranslateOption(
					$studentId,
					$questionIdToSave,
					$selectedAnswer,
					htmlspecialchars_decode($rowSaveQuestion['optionA'] ?? ''),
					htmlspecialchars_decode($rowSaveQuestion['optionB'] ?? ''),
					htmlspecialchars_decode($rowSaveQuestion['optionC'] ?? ''),
					htmlspecialchars_decode($rowSaveQuestion['optionD'] ?? ''),
					htmlspecialchars_decode($rowSaveQuestion['optionE'] ?? '')
				);
			}
		}

		dlhsSaveAnswerWithMetadata(
			$connection,
			$answersTableName,
			$questionsTableName,
			$studentId,
			$testId,
			$questionIdToSave,
			$selectedAnswerToStore
		);

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
		
				
		
		$arrayQuestionsIds=$_SESSION['questionsIdsArray'];	//variable to hold arrays of questions ids
		
		$currentIndexToDisplay=$_SESSION['nextIndexId']+1;	//variable to hold index of current question number to be displayed 
		$totalQuestions=$_SESSION['totalQuestions'];
		
		
		$questionIdToFetch=$arrayQuestionsIds[$_SESSION['nextIndexId']];
		
		$_SESSION['nextQuesIdTosave']=$questionIdToFetch;
		$_SESSION['prevIndexId']=$_SESSION['nextIndexId'];		//variable to hold index of previous question that can be selected from the questionIds array
		$_SESSION['nextIndexId']=$_SESSION['nextIndexId']+1;
		
	$query = "SELECT * FROM $questionsTableName WHERE questionId='$questionIdToFetch'"; //Selecting question to be displayed
	$result = $connection->query($query);
	$row = $result->fetch_array(MYSQLI_NUM);
	$question = htmlspecialchars_decode($row[1]);
	$originalOptionA = htmlspecialchars_decode($row[2]);
	$originalOptionB = htmlspecialchars_decode($row[3]);
	$originalOptionC = htmlspecialchars_decode($row[4]);
	$originalOptionD = htmlspecialchars_decode($row[5]);
	$originalOptionE = htmlspecialchars_decode($row[6]);
	$optionA = $originalOptionA;
	$optionB = $originalOptionB;
	$optionC = $originalOptionC;
	$optionD = $originalOptionD;
	$optionE = $originalOptionE;
	$correctOption = $row[7];
	$markForQuestion = $row[8];
	
	// Apply option randomization if enabled (SAFE: Uses student+question as seed)
	if ($randomizeOptions === 'Yes') {
		$shuffled = shuffleAnswerOptions(
			$studentId,
			$questionIdToFetch,
			$optionA,
			$optionB,
			$optionC,
			$optionD,
			$optionE,
			$correctOption
		);
		$optionA = $shuffled['optionA'];
		$optionB = $shuffled['optionB'];
		$optionC = $shuffled['optionC'];
		$optionD = $shuffled['optionD'];
		$optionE = $shuffled['optionE'];
		$correctOption = $shuffled['correctOption'];
	}
	
	$_SESSION['correctOption']=$correctOption;
	$_SESSION['markForQuestion']=$markForQuestion;
	
	
	//Selecting the selected uption of an already answered question.
	$notSelected="0";
	$query4 = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND testId='$testId' AND questionId='$questionIdToFetch' AND selectedOption !='$notSelected'";
	$result4 = $connection->query($query4);
	$optionAnsweredQues="z";
	if (($result4->num_rows)>0)	//if the student has already answered and saved this question
	{
		$row4 = $result4->fetch_array(MYSQLI_NUM);
		$optionAnsweredQues=$row4[4];
		if ($randomizeOptions === 'Yes' && $optionAnsweredQues !== '' && $optionAnsweredQues !== '0') {
			$optionAnsweredQues = translateOriginalOptionToDisplayed(
				$studentId,
				$questionIdToFetch,
				$optionAnsweredQues,
				$originalOptionA,
				$originalOptionB,
				$originalOptionC,
				$originalOptionD,
				$originalOptionE
			);
		}
	}
				
		echo json_encode(array('q' => $question, 'a' => $optionA, 'b' => $optionB, 'c' => $optionC, 'd' => $optionD, 'e' => $optionE, 'f' => $currentIndexToDisplay, 'g' => $totalQuestions, 'h' => $optionAnsweredQues, 'j' => $remainingTimeToReturn, 'k' => $logoutStatus, 'm' => $testStatus));
	}
?>
