<?php
session_start();
		$return_arr = array();
		header('Content-Type: application/json');
		
		if (!isset($_POST['testId']) || !isset($_POST['examineeUserId']) || !isset($_SESSION['staffId'])) {
			echo json_encode($return_arr);
			exit;
		}
		
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../studentLogin/studentPanel/answer_grading_helper.php";
		
		$testId = (int) $_POST['testId'];
		$examineeUserId = (int) $_POST['examineeUserId'];
		$staffId = (int) $_SESSION['staffId'];
	$query="SELECT * FROM tests WHERE staffId='$staffId' AND testId='$testId'";
	$result = $connection->query($query);
	
	if(!$result || $result->num_rows == 0)
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$row = $result->fetch_array(MYSQLI_NUM);
	$questionsTableName = $row[13];
	$examineesTableName = $row[14];
	$examineesAnswersTableName = $row[16];
	
	//Getting the array of questions the examinee answered from the tested table
	$query1="SELECT * FROM $examineesTableName WHERE examineeUserId='$examineeUserId'";
	$result1 = $connection->query($query1);
	
	if(!$result1 || $result1->num_rows == 0)
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$row1 = $result1->fetch_array(MYSQLI_NUM);
	$getQuestionsArray = $row1[6];
	
	if($getQuestionsArray === "" || $getQuestionsArray === null)
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$questionsArray = unserialize($getQuestionsArray);
	if($questionsArray === false || !is_array($questionsArray))
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$numberOfQuestions = count($questionsArray);
	
	$totalmarksToBeEarned = 0;
	$totalMarksEarned = 0;
	for($i = 0; $i < $numberOfQuestions; $i++)
	{
		$questionId = $questionsArray[$i];
		
		//Getting the question from the questions table
		$query2="SELECT * FROM $questionsTableName WHERE questionId='$questionId'";
		$result2 = $connection->query($query2);
		
		if(!$result2 || $result2->num_rows == 0)
		{
			continue; // Skip this question if not found
		}
		
			$row2 = $result2->fetch_array(MYSQLI_NUM);
			$question = htmlspecialchars_decode($row2[1]);
			$optionA = htmlspecialchars_decode($row2[2]);
			$optionB = htmlspecialchars_decode($row2[3]);
			$optionC = htmlspecialchars_decode($row2[4]);
			$optionD = htmlspecialchars_decode($row2[5]);
			$optionE = htmlspecialchars_decode($row2[6]);
			$correctOption = dlhsNormalizeOptionValue($row2[7]);
			$mark = $row2[8];
		$totalmarksToBeEarned = $totalmarksToBeEarned + $mark;
		$solution = htmlspecialchars_decode($row2[9]);
		
		//Getting the selected option from the answers table
		$markStatus = "";
		$markObtained = "";
		$selectedOption = "";
		$query3="SELECT * FROM $examineesAnswersTableName WHERE userLoginId='$examineeUserId' AND questionId='$questionId'";
		$result3 = $connection->query($query3);
			if($result3 && $result3->num_rows > 0)
			{
				$row3 = $result3->fetch_array(MYSQLI_NUM);
				$selectedOption = dlhsNormalizeOptionValue($row3[4]);
				if($selectedOption == $correctOption)
			{
				$totalMarksEarned = $totalMarksEarned + $mark;
				$markObtained = $mark;
				$markStatus = 1;	//If correct option is selected
			}
			else
			{
				$markObtained = 0;
				$markStatus = 2;	//If wrong option is selected
			}
		}
		else
		{
			$markObtained = "";
			$markStatus = 3; // Not answered
		}
		
		$return_arr[] = array("questionId" => $questionId,
								"question" => $question,
								"optionA" => $optionA,
								"optionB" => $optionB,
								"optionC" => $optionC,
								"optionD" => $optionD,
								"optionE" => $optionE,
								"solution" => $solution,
								"correctOption" => $correctOption,
								"selectedOption" => $selectedOption,
								"markStatus" => $markStatus,
								"mark" => $mark,
								"markObtained" => $markObtained);
	}
	echo json_encode($return_arr);	
?>
