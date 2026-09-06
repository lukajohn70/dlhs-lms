<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
	require_once "shuffle_options_helper.php";
	require_once "answer_grading_helper.php";
	
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
		$randomizeOptions = $row[19] ?? 'No';  // Get option randomization setting (column 19)
				
		$totalQuestions=$_SESSION['totalQuestions'];
		
		$_SESSION['nextIndexId']=1;
		$_SESSION['prevIndexId']=-1;
		
		
		$arrayQuestionsIds=$_SESSION['questionsIdsArray'];
		$questionIdToFetch=$arrayQuestionsIds[0];
		$_SESSION['nextQuesIdTosave']=$questionIdToFetch;
		
	$query2 = "SELECT * FROM $questionsTableName WHERE questionId='$questionIdToFetch'";
	$result2 = $connection->query($query2);
	$row2 = $result2->fetch_array(MYSQLI_NUM);
	$question = htmlspecialchars_decode($row2[1]);
	$optionA = htmlspecialchars_decode($row2[2]);
	$optionB = htmlspecialchars_decode($row2[3]);
	$optionC = htmlspecialchars_decode($row2[4]);
	$optionD = htmlspecialchars_decode($row2[5]);
	$optionE = htmlspecialchars_decode($row2[6]);
	$correctOption = $row2[7];
	$markForQuestion = $row2[8];
	
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
				htmlspecialchars_decode($row2[2]),
				htmlspecialchars_decode($row2[3]),
				htmlspecialchars_decode($row2[4]),
				htmlspecialchars_decode($row2[5]),
				htmlspecialchars_decode($row2[6])
			);
		}
	}
	echo json_encode(array('q' => $question, 'a' => $optionA, 'b' => $optionB, 'c' => $optionC, 'd' => $optionD, 'e' => $optionE, 'f' => $totalQuestions, 'g' => $testName, 'h' => $optionAnsweredQues));
}
?>
