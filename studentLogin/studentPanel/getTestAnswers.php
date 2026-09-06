<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	$studentId = $_SESSION['studentId'];
	$studentYearGroupId = $_SESSION['studentYearGroup'];
	$testId = $_POST['testId'];
	$examineeUserId = $_POST['examineeUserId'];
	
	$return_arr = array();
	$query="SELECT * FROM tests WHERE yearGroup='$studentYearGroupId' AND testId='$testId'";
	$result = $connection->query($query);
	
	if(!$result || $result->num_rows == 0)
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$row = $result->fetch_array(MYSQLI_NUM);
	$reviewOption = $row[17]; // reviewOption column
	
	// Check if review is enabled for this test
	if($reviewOption != "Yes")
	{
		echo json_encode($return_arr);
		exit;
	}
	
	$questionsTableName = $row[12];
	$examineesTableName = $row[13];
	$examineesAnswersTableName = $row[15];
	
	//Getting the array of questions the examinee answered from the tested table
	$query1="SELECT * FROM $examineesTableName WHERE examineeUserId='$examineeUserId'";
	$result1 = $connection->query($query1);
	$row1 = $result1->fetch_array(MYSQLI_NUM);
	$questionsArray = unserialize($row1[6]);
	$numberOfQuestions = count($questionsArray);
	
	$totalmarksToBeEarned = 0;
	$totalMarksEarned = 0;
	for($i = 0; $i < $numberOfQuestions; $i++)
	{
		$questionId = $questionsArray[$i];
		
		//Getting the question from the questions table
		$query2="SELECT * FROM $questionsTableName WHERE questionId='$questionId'";
		$result2 = $connection->query($query2);
		$row2 = $result2->fetch_array(MYSQLI_NUM);
		$question = htmlspecialchars_decode($row2[1], ENT_QUOTES | ENT_HTML5);
		$optionA = htmlspecialchars_decode($row2[2], ENT_QUOTES | ENT_HTML5);
		$optionB = htmlspecialchars_decode($row2[3], ENT_QUOTES | ENT_HTML5);
		$optionC = htmlspecialchars_decode($row2[4], ENT_QUOTES | ENT_HTML5);
		$optionD = htmlspecialchars_decode($row2[5], ENT_QUOTES | ENT_HTML5);
		$optionE = htmlspecialchars_decode($row2[6], ENT_QUOTES | ENT_HTML5);
		$correctOption = $row2[7];
		$mark = $row2[8];
		$totalmarksToBeEarned = $totalmarksToBeEarned + $mark;
		$solution = htmlspecialchars_decode($row2[9], ENT_QUOTES | ENT_HTML5);
		
		//Getting the selected option from the answers table
		$markStatus = "";
		$markObtained = "";
		$selectedOption = "";
		$query3="SELECT * FROM $examineesAnswersTableName WHERE userLoginId='$examineeUserId' AND questionId='$questionId'";
		$result3 = $connection->query($query3);
		if($result3->num_rows > 0)
		{
			$row3 = $result3->fetch_array(MYSQLI_NUM);
			$selectedOption = $row3[4];
			if($selectedOption == $correctOption)
			{
				$totalMarksEarned = $totalMarksEarned + $mark;
				$markObtained = $mark;
				$markStatus = 1;
			}
			else
			{
				$markObtained = 0;
				$markStatus = 2;
			}
		}
		else
		{
			$markObtained = "";
			$markStatus = 3;
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
