<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/question_authoring_helper.php";
	
	$staffId = $_SESSION['staffId'];
	$testId = mysqli_real_escape_string($connection, $_POST['testId']);
	$questionId = mysqli_real_escape_string($connection, $_POST['questionId']);
	$return_arr = array();
	
	//Getting the table name from the tests table
	$query="SELECT * FROM tests WHERE staffId='$staffId' AND testId='$testId'";
	$result = $connection->query($query);
	$row = $result->fetch_array(MYSQLI_NUM);
	$questionsTableName = $row[13];
	$stimulusMap = dlhsFetchSharedStimulusMapForQuestions($connection, $testId, array((int) $questionId));
	
	//Getting the quesions from the retrieved questions table
	$getQuestions="SELECT * FROM $questionsTableName WHERE questionId='$questionId'";
	$result1 = $connection->query($getQuestions);
	if($result1->num_rows > 0)
	{
		while($row1 = $result1->fetch_array(MYSQLI_NUM))
		{
			$questionId  = $row1[0];
			$question  = $row1[1];
			$optionA = $row1[2];
			$optionB = $row1[3];
			$optionC = $row1[4];
			$optionD = $row1[5];
			$optionE = $row1[6];
			$correctOption = $row1[7];
			$markForQuestion = $row1[8];
			$solvedSolution = $row1[9];
			$stimulusMeta = isset($stimulusMap[(int) $questionId]) ? $stimulusMap[(int) $questionId] : null;
			
			$return_arr[] = array("questionId" => $questionId,
							"question" => htmlspecialchars_decode($question),
							"optionA" => htmlspecialchars_decode($optionA),
							"optionB" => htmlspecialchars_decode($optionB),
							"optionC" => htmlspecialchars_decode($optionC),
							"optionD" => htmlspecialchars_decode($optionD),
							"optionE" => htmlspecialchars_decode($optionE),
							"correctOption" => $correctOption,
							"markForQuestion" => $markForQuestion,
							"solvedSolution" => htmlspecialchars_decode($solvedSolution),
							"stimulusId" => $stimulusMeta ? $stimulusMeta['stimulusId'] : 0,
							"stimulusTitle" => $stimulusMeta ? $stimulusMeta['stimulusTitle'] : '',
							"stimulusType" => $stimulusMeta ? $stimulusMeta['stimulusType'] : '',
							"stimulusContent" => $stimulusMeta ? $stimulusMeta['stimulusContent'] : '',
							"stimulusLabel" => $stimulusMeta ? $stimulusMeta['stimulusLabel'] : '');
		}
		echo json_encode($return_arr);
	}
	else
	{
		echo json_encode($return_arr);
	}
?>
