<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once "answer_grading_helper.php";
										//getting the student name.
										$studentId=$_SESSION['studentId'];
										$testId=$_POST['testId'];
										
										$return_arr = array();
										
										$query = "SELECT * FROM studentlogin WHERE studentId='$studentId'";
										$result = $connection->query($query);
										$row = $result->fetch_array(MYSQLI_NUM);
										$firstName=$row[2];
										
										//getting the test name
										$query1 = "SELECT * FROM tests WHERE testId='$testId'";
										$result1 = $connection->query($query1);
										$row1 = $result1->fetch_array(MYSQLI_NUM);
										$testName=$row1[2];
										$testedTableName=$row1[13];
										$answersTableName=$row1[15];
										$questionsTableName=$row1[12];

										
																				
										//getting the scores 
										$query3 = "SELECT * FROM $testedTableName WHERE testId='$testId' AND examineeUserId='$studentId'";
										$result3 = $connection->query($query3);
										$row3 = $result3->fetch_array(MYSQLI_NUM);
										
										
										//Getting the array of questions the examinee answered from the tested table
										$query1="SELECT * FROM $testedTableName WHERE examineeUserId='$studentId'";
										$result1 = $connection->query($query1);
										$row1 = $result1->fetch_array(MYSQLI_NUM);
										$questionsArray = unserialize($row1[6]);
										$numberOfQuestions = count($questionsArray);
										
										$totalmarksToBeEarned = 0;
										$totalMarksEarned = 0;
										$totalMarksLost = 0;
										$totalNoCorrect = 0;
										$totalNoNotCorrect = 0;
										$totalQuestionsAnswered = 0;
										$totalQuestionsNotAnswered = 0;
										for($i = 0; $i < $numberOfQuestions; $i++)
										{
											$questionId = $questionsArray[$i];
											
											//Getting the question from the questions table
											$query2="SELECT * FROM $questionsTableName WHERE questionId='$questionId'";
											$result2 = $connection->query($query2);
											$row2 = $result2->fetch_array(MYSQLI_NUM);
											$correctOption = dlhsNormalizeOptionValue($row2[7]);
											$mark = $row2[8];
											$totalmarksToBeEarned = $totalmarksToBeEarned + $mark;
											
											//Getting the selected option from the answers table
											$query3="SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND questionId='$questionId'";
											$result3 = $connection->query($query3);
											if($result3->num_rows > 0)
											{
												$row3 = $result3->fetch_array(MYSQLI_NUM);
												$selectedOption = dlhsNormalizeOptionValue($row3[4]);
												if($selectedOption == $correctOption)
												{
													$totalMarksEarned = $totalMarksEarned + $mark;
													$totalNoCorrect = $totalNoCorrect + 1;
												}
												else
												{
													if($selectedOption == "0")
													{
														$totalQuestionsNotAnswered = $totalQuestionsNotAnswered + 1;
													}
													elseif($selectedOption == "")
													{
														$totalQuestionsNotAnswered = $totalQuestionsNotAnswered + 1;
													}
													else
													{
														$totalQuestionsAnswered = $totalQuestionsAnswered + 1;
														$totalNoNotCorrect = $totalNoNotCorrect + 1;
													}													
												}
											}
											else
											{
												$totalQuestionsNotAnswered = $totalQuestionsNotAnswered +1;
											}
										}
										$totalMarksLost = $totalmarksToBeEarned - $totalMarksEarned;
										$percentMarksEarned = ($totalMarksEarned/$totalmarksToBeEarned) * 100;
										$percentMarksLost = (($totalmarksToBeEarned - $totalMarksEarned) / $totalmarksToBeEarned) * 100;
										
										//getting the number answered wrongly
										$testForZero="0";
										$query5 = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND testId ='$testId' AND selectedOption !=correctOption AND selectedOption !='$testForZero'";
										$result5 = $connection->query($query5);
										$numberAnsweredWrong = $result5->num_rows;
										
										$return_arr[] = array("numberOfQuestions" => $numberOfQuestions,
																"totalNoCorrect" => $totalNoCorrect,
																"totalNoNotCorrect" => $totalNoNotCorrect,
																"totalQuestionsNotAnswered" => $totalQuestionsNotAnswered,
																"percentMarksEarned" => $percentMarksEarned,
																"percentMarksLost" => $percentMarksLost);
										
										echo json_encode($return_arr);
									?>
