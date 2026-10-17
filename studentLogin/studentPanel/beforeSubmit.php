<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "shuffle_options_helper.php";
require_once "answer_grading_helper.php";

if (!isset($_SESSION['studentId']) || !isset($_SESSION['testId'])) {
	session_write_close();
	echo json_encode(0);
	exit;
}

if (isset($_POST['selectedAnswer'])) {
	$selectedAnswer = mysqli_real_escape_string($connection, $_POST['selectedAnswer']);
	$testId = (int)$_SESSION['testId'];
	$studentId = (int)$_SESSION['studentId'];
	
	$questionsTableName = $_SESSION['questTableName'] ?? '';
	$questionIdToSave = (int)($_SESSION['nextQuesIdTosave'] ?? 0);
	$answersTableName = $_SESSION['answersTableName'] ?? '';
	
	$remainingTimeToReturn = 0;
	
	$query7 = "SELECT randomizeOptions FROM tests WHERE testId='$testId'";
	$result7 = $connection->query($query7);
	$row7 = $result7 ? $result7->fetch_assoc() : null;
	$randomizeOptions = $row7['randomizeOptions'] ?? 'No';

	$selectedAnswerToStore = $selectedAnswer;
	if ($randomizeOptions === 'Yes' && in_array($selectedAnswer, ['A', 'B', 'C', 'D', 'E'], true) && $questionIdToSave > 0 && !empty($questionsTableName)) {
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

	if ($questionIdToSave > 0 && !empty($answersTableName) && !empty($questionsTableName)) {
		dlhsSaveAnswerWithMetadata(
			$connection,
			$answersTableName,
			$questionsTableName,
			$studentId,
			$testId,
			$questionIdToSave,
			$selectedAnswerToStore
		);
	}
		
	// Getting remaining time and subtracting elapsed time
	$testedTableName = $_SESSION['testedTableName'] ?? '';
	if (!empty($testedTableName)) {
		$query5 = "SELECT * FROM $testedTableName WHERE examineeUserId ='$studentId' AND testId='$testId'";
		$result5 = $connection->query($query5);
		if ($result5 && $row5 = $result5->fetch_assoc()) {
			$selectedTime = (int)($row5['remainingTime'] ?? 0);
			$startTime = isset($_SESSION['startTime']) ? (int)$_SESSION['startTime'] : time();
			$timeElapsed = time() - $startTime;
			$_SESSION['startTime'] = time();
			$remainingTime = max(0, $selectedTime - $timeElapsed);		
			
			$query6 = "UPDATE $testedTableName SET remainingTime ='$remainingTime' WHERE testId='$testId' AND examineeUserId='$studentId'";
			$connection->query($query6);
		}
	}

	session_write_close();
	echo json_encode(1);
} else {
	session_write_close();
	echo json_encode(0);
}
