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
		
		$remainingTimeToReturn=0;
		
		$query7 = "SELECT randomizeOptions FROM tests WHERE testId='$testId'";
		$result7 = $connection->query($query7);
		$row7 = $result7 ? $result7->fetch_assoc() : null;
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
		
		$query6 = "UPDATE $testedTableName SET remainingTime ='$remainingTime' WHERE testId='$testId' AND examineeUserId='$studentId'";
		$result6 = $connection->query($query6);
		echo 1;
	}
?>
