<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "answer_grading_helper.php";

$studentId = isset($_SESSION['studentId']) ? (int)$_SESSION['studentId'] : 0;
$testId = isset($_POST['testId']) ? (int)$_POST['testId'] : 0;

$return_arr = array();

if ($studentId <= 0 || $testId <= 0) {
    echo json_encode($return_arr);
    exit;
}

//getting the student name.
$query = "SELECT firstName FROM studentlogin WHERE studentId='$studentId'";
$result = $connection->query($query);
$row = $result ? $result->fetch_assoc() : null;
$firstName = $row['firstName'] ?? '';

//getting the test name and table names
$query1 = "SELECT * FROM tests WHERE testId='$testId'";
$result1 = $connection->query($query1);
$row1 = $result1 ? $result1->fetch_assoc() : null;

if (!$row1) {
    echo json_encode($return_arr);
    exit;
}

$testName = $row1['testName'] ?? '';
$testedTableName = $row1['examineesTableName'] ?? '';
$answersTableName = $row1['answersTable'] ?? '';
$questionsTableName = $row1['tableName'] ?? '';

if (empty($testedTableName) || empty($answersTableName) || empty($questionsTableName)) {
    echo json_encode($return_arr);
    exit;
}

//Getting the scores and array of questions from the examinees table
$query3 = "SELECT * FROM `$testedTableName` WHERE testId='$testId' AND examineeUserId='$studentId'";
$result3 = $connection->query($query3);
$row3 = $result3 ? $result3->fetch_assoc() : null;

$questionsArray = array();
if ($row3 && !empty($row3['questArray'])) {
    $decoded = @unserialize($row3['questArray']);
    if (is_array($decoded)) {
        $questionsArray = $decoded;
    }
}
$numberOfQuestions = count($questionsArray);

$totalmarksToBeEarned = 0;
$totalMarksEarned = 0;
$totalMarksLost = 0;
$totalNoCorrect = 0;
$totalNoNotCorrect = 0;
$totalQuestionsAnswered = 0;
$totalQuestionsNotAnswered = 0;

for ($i = 0; $i < $numberOfQuestions; $i++)
{
    $questionId = (int)$questionsArray[$i];
    
    //Getting the question from the questions table
    $query2 = "SELECT * FROM `$questionsTableName` WHERE questionId='$questionId'";
    $result2 = $connection->query($query2);
    $row2 = $result2 ? $result2->fetch_assoc() : null;
    $correctOption = dlhsNormalizeOptionValue($row2['correctOption'] ?? '');
    $mark = isset($row2['markForQuestion']) ? (float)$row2['markForQuestion'] : 0;
    $totalmarksToBeEarned = $totalmarksToBeEarned + $mark;
    
    //Getting the selected option from the answers table
    $queryA = "SELECT * FROM `$answersTableName` WHERE userLoginId='$studentId' AND questionId='$questionId' AND testId='$testId'";
    $resultA = $connection->query($queryA);
    if ($resultA && $resultA->num_rows > 0)
    {
        $rowA = $resultA->fetch_assoc();
        $selectedOption = dlhsNormalizeOptionValue($rowA['selectedOption'] ?? '');
        if ($selectedOption !== '' && $selectedOption !== '0' && $selectedOption === $correctOption)
        {
            $totalMarksEarned = $totalMarksEarned + $mark;
            $totalNoCorrect = $totalNoCorrect + 1;
            $totalQuestionsAnswered = $totalQuestionsAnswered + 1;
        }
        else
        {
            if ($selectedOption === '0' || $selectedOption === '')
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
        $totalQuestionsNotAnswered = $totalQuestionsNotAnswered + 1;
    }
}

$totalMarksLost = $totalmarksToBeEarned - $totalMarksEarned;
$percentMarksEarned = $totalmarksToBeEarned > 0 ? ($totalMarksEarned / $totalmarksToBeEarned) * 100 : 0;
$percentMarksLost = $totalmarksToBeEarned > 0 ? (($totalmarksToBeEarned - $totalMarksEarned) / $totalmarksToBeEarned) * 100 : 0;

$return_arr[] = array(
    "numberOfQuestions" => $numberOfQuestions,
    "totalNoCorrect" => $totalNoCorrect,
    "totalNoNotCorrect" => $totalNoNotCorrect,
    "totalQuestionsNotAnswered" => $totalQuestionsNotAnswered,
    "percentMarksEarned" => round($percentMarksEarned, 2),
    "percentMarksLost" => round($percentMarksLost, 2)
);

echo json_encode($return_arr);
?>
