<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/question_authoring_helper.php";
	
	$staffId = $_SESSION['staffId'];
	$testId = $_POST['testId'];
	$return_arr = array();
	
	//Getting the table name from the tests table
	$query="SELECT * FROM tests WHERE staffId='$staffId' AND testId='$testId'";
	$result = $connection->query($query);
	$row = $result->fetch_array(MYSQLI_NUM);
	$questionsTableName = $row[13];
	$stimulusMap = dlhsFetchSharedStimulusMapForQuestions($connection, $testId, dlhsFetchValidQuestionIdsForTest($connection, array('tableName' => $questionsTableName)));
	
	//Getting the quesions from the retrieved questions table
	$getQuestions="SELECT * FROM $questionsTableName";
	$result1 = $connection->query($getQuestions);
	if($result1->num_rows > 0)
	{
		while($row1 = $result1->fetch_array(MYSQLI_NUM))
		{
			$questionId  = $row1[0];
			$question  = htmlspecialchars_decode($row1[1]);
			$optionA = htmlspecialchars_decode($row1[2]);
			$optionB = htmlspecialchars_decode($row1[3]);
			$optionC = htmlspecialchars_decode($row1[4]);
			$optionD = htmlspecialchars_decode($row1[5]);
			$optionE = htmlspecialchars_decode($row1[6]);
			$correctOption = $row1[7];
			$markForQuestion = $row1[8];
			$solvedSolution = htmlspecialchars_decode($row1[9]);
			$stimulusMeta = isset($stimulusMap[(int) $questionId]) ? $stimulusMap[(int) $questionId] : null;
			
			$return_arr[] = array("questionId" => $questionId,
							"question" => $question,
							"optionA" => $optionA,
							"optionB" => $optionB,
							"optionC" => $optionC,
							"optionD" => $optionD,
							"optionE" => $optionE,
							"correctOption" => $correctOption,
							"markForQuestion" => $markForQuestion,
							"solvedSolution" => $solvedSolution,
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
