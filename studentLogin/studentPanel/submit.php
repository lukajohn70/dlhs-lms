<?php
session_start();
if (!isset($_SESSION['studentId']))
{
	header('location:logout.php');
	exit();
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "answer_grading_helper.php";
require_once "../../scripts/essay_timer_helper.php";
require_once "../../track_test_activity.php";

$testId = isset($_SESSION['testId']) ? (int)$_SESSION['testId'] : (isset($_SESSION['idOfTest']) ? (int)$_SESSION['idOfTest'] : 0);
$studentId = (int)$_SESSION['studentId'];

if ($testId <= 0 || $studentId <= 0) {
	header('location:logout.php');
	exit();
}

$query = "SELECT * FROM tests WHERE testId='$testId'";
$result = $connection->query($query);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
	header('location:logout.php');
	exit();
}

$testTestedTable = $row['examineesTableName'] ?? '';
$questionsTableName = $row['tableName'] ?? '';
$answersTableName = $row['answersTable'] ?? '';

if (empty($testTestedTable) || empty($questionsTableName) || empty($answersTableName)) {
	die("Test configuration error: tables missing.");
}

$timeSubmittedTest = date('h:i A');
$setStatusTo = 2;

// Updating candidate's test status to completed
$query1 = "UPDATE `$testTestedTable` SET testStatus = '$setStatusTo', timeSubmittedTest = '$timeSubmittedTest' WHERE testId = '$testId' AND examineeUserId = '$studentId'";
$connection->query($query1);

if (function_exists('markTestCompleted')) {
	markTestCompleted($testId);
}

dlhsRepairAnswerMetadata($connection, $answersTableName, $questionsTableName, (int) $studentId, (int) $testId);

// Total marks to be earned for the test
$query2 = "SELECT SUM(markForQuestion) AS theSum FROM `$questionsTableName`";
$result2 = $connection->query($query2);
$row2 = $result2 ? $result2->fetch_assoc() : array();
$totalToBeEarned = isset($row2['theSum']) ? (float)$row2['theSum'] : 0;

// Total marks earned for the test
$query3 = "SELECT SUM(markForQuestion) AS totalEarned FROM `$answersTableName` WHERE selectedOption = correctOption AND userLoginId = '$studentId' AND testId = '$testId'";
$result3 = $connection->query($query3);
$row3 = $result3 ? $result3->fetch_assoc() : array();
$totalEarned = isset($row3['totalEarned']) ? (float)$row3['totalEarned'] : 0;

// Total number of questions
$query4 = "SELECT COUNT(*) as count FROM `$questionsTableName`";
$result4 = $connection->query($query4);
$row4 = $result4 ? $result4->fetch_assoc() : null;
$totalQuestions = (int)($row4['count'] ?? 0);

// Total number of questions scored correctly
$query5 = "SELECT COUNT(*) as count FROM `$answersTableName` WHERE selectedOption = correctOption AND userLoginId = '$studentId' AND testId = '$testId'";
$result5 = $connection->query($query5);
$row5 = $result5 ? $result5->fetch_assoc() : null;
$numberCorrect = (int)($row5['count'] ?? 0);

// Updating candidate's row with score and summary
$query6 = "UPDATE `$testTestedTable` SET totalToBeEarned = '$totalToBeEarned', totalEarned = '$totalEarned', noOfQuestions = '$totalQuestions', noCorrect = '$numberCorrect' WHERE testId = '$testId' AND examineeUserId = '$studentId'";
$connection->query($query6);

unset($_SESSION['pages']);
unset($_SESSION['setNext']);
unset($_SESSION['questionsIdsArray']);
unset($_SESSION['nextQuestIdToSelect']);
unset($_SESSION['nextIndexId']);
unset($_SESSION['prevIndexId']);
unset($_SESSION['questTableName']);
unset($_SESSION['totalQuestions']);
unset($_SESSION['answersTableName']);

header("location:result/");
exit();
?>
