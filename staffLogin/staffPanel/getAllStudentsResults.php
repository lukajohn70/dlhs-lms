<?php
session_start();
		$return_arr = array();
		header('Content-Type: application/json');
		
		if (!isset($_POST['testId']) || !isset($_SESSION['staffId'])) {
			echo json_encode($return_arr);
			exit;
		}
		
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../studentLogin/studentPanel/answer_grading_helper.php";
		
		$testId = (int) $_POST['testId'];
		$staffId = (int) $_SESSION['staffId'];
	$query="SELECT * FROM tests WHERE staffId='$staffId' AND testId='$testId'";
	$result = $connection->query($query);
	
	if(!$result || $result->num_rows == 0)
	{
		echo json_encode($return_arr);
		exit;
	}
	
	include_once "../../scripts/test_workflow_helper.php";
	$row = $result->fetch_array(MYSQLI_BOTH);
	dlhsEnsureTestTablesExist($connection, $row);

	$questionsTableName = $row[13];
	$examineesTableName = $row[14];
	$examineesAnswersTableName = $row[16];
	
	//Getting the array of questions the examinee answered from the tested table
	$query1="SELECT * FROM $examineesTableName";
	$result1 = $connection->query($query1);
	
	if(!$result1)
	{
		// Table might not exist or query failed
		echo json_encode($return_arr);
		exit;
	}
	
	if($result1->num_rows > 0)
	{
		while ($row1 = $result1->fetch_array(MYSQLI_NUM))
		{
			$studentId = $row1[2];
			$studentClassId = $row1[3];
			$studentYearGroupId = $row1[4];
			$getQuestionsArray = $row1[6];
			
			$totalQuestionsToBeAnswered = 0;
			$totalQuestionsAnswered = 0;
			$totalQuestionsAnsweredCorrectly = 0;
			$totalQuestionsFailed = 0;
			$totalQuestionsAnsweredAndFailed = 0;
			$totalQuestionsNotAnswered = 0;
			$totalmarksToBeEarned = 0;
			$totalMarksEarned = 0;
			
			if($getQuestionsArray !== "" && $getQuestionsArray !== null)	//if the examinee started the test and questions were sent into the questions array column
			{
				$questionsArray = unserialize($row1[6]);
				if($questionsArray !== false && is_array($questionsArray))
				{
					$numberOfQuestions = count($questionsArray);
					$totalQuestionsToBeAnswered = $numberOfQuestions;
					for($i = 0; $i < $numberOfQuestions; $i++)
					{
						$questionId = $questionsArray[$i];
						
						//Getting the question from the questions table
						$query2="SELECT * FROM $questionsTableName WHERE questionId='$questionId'";
						$result2 = $connection->query($query2);
						if($result2 && $result2->num_rows > 0)
						{
								$row2 = $result2->fetch_array(MYSQLI_NUM);
								$correctOption = dlhsNormalizeOptionValue($row2[7]);
								$mark = $row2[8];
							$totalmarksToBeEarned = $totalmarksToBeEarned + $mark;
							$solution = $row2[9];
							
							//Getting the selected option from the answers table
							$query3="SELECT * FROM $examineesAnswersTableName WHERE userLoginId='$studentId' AND questionId='$questionId'";
							$result3 = $connection->query($query3);
							if($result3 && $result3->num_rows > 0)	//If examinee submitted an answer for this question
							{
									$row3 = $result3->fetch_array(MYSQLI_NUM);
									$selectedOption = dlhsNormalizeOptionValue($row3[4]);
									if($selectedOption == $correctOption)
								{
									$totalMarksEarned = $totalMarksEarned + $mark;
									$totalQuestionsAnswered = $totalQuestionsAnswered + 1;
									$totalQuestionsAnsweredCorrectly = $totalQuestionsAnsweredCorrectly + 1;
								}
								else
								{	
									if($selectedOption == "0")
									{
										$totalQuestionsNotAnswered = $totalQuestionsNotAnswered + 1;
									}
									else
									{
										$totalQuestionsAnswered = $totalQuestionsAnswered + 1;
										$totalQuestionsAnsweredAndFailed = $totalQuestionsAnsweredAndFailed + 1;
									}
									
								}
							}
							else
							{
								$totalQuestionsNotAnswered = $totalQuestionsNotAnswered + 1;
							}
						}
					}
				}
			}
			
			//Getting the student's name
			$query4="SELECT * FROM studentlogin WHERE studentId='$studentId'";
			$result4 = $connection->query($query4);
			if($result4 && $result4->num_rows > 0)
			{
				$row4 = $result4->fetch_array(MYSQLI_NUM);
				$studentName = $row4[1]." ".$row4[2]." ".$row4[3];
			}
			else
			{
				$studentName = "Unknown Student";
			}
			
			$percentageScore = 0;
			if($totalmarksToBeEarned !== 0)
			{
				$percentageScore = number_format((($totalMarksEarned/$totalmarksToBeEarned) * 100), 2);
			}
					
			$return_arr[] = array("studentId" => $studentId,
									"studentName" => $studentName,
									"totalQuestionsToBeAnswered" => $totalQuestionsToBeAnswered,
									"totalQuestionsAnswered" => $totalQuestionsAnswered,
									"totalQuestionsAnsweredCorrectly" => $totalQuestionsAnsweredCorrectly,
									"totalQuestionsAnsweredAndFailed" => $totalQuestionsAnsweredAndFailed,
									"totalQuestionsNotAnswered" => $totalQuestionsNotAnswered,
									"totalmarksToBeEarned" => $totalmarksToBeEarned,
									"totalMarksEarned" => $totalMarksEarned,
									"percentageScore" => $percentageScore);
									
		}
	}
	
	echo json_encode($return_arr);	
?>
