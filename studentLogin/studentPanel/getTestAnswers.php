<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "answer_grading_helper.php";

$studentId = isset($_SESSION['studentId']) ? (int)$_SESSION['studentId'] : 0;
$studentYearGroupId = isset($_SESSION['studentYearGroup']) ? (int)$_SESSION['studentYearGroup'] : 0;
$testId = isset($_POST['testId']) ? (int)$_POST['testId'] : 0;
$examineeUserId = isset($_POST['examineeUserId']) ? (int)$_POST['examineeUserId'] : $studentId;

$return_arr = array();

if ($testId <= 0 || $examineeUserId <= 0) {
    echo json_encode($return_arr);
    exit;
}

$query = "SELECT * FROM tests WHERE testId='$testId'";
$result = $connection->query($query);

if (!$result || $result->num_rows == 0) {
    echo json_encode($return_arr);
    exit;
}

$row = $result->fetch_assoc();
$reviewOption = $row['reviewOption'] ?? 'No';

// Check if review is enabled for this test
if (strcasecmp($reviewOption, "Yes") !== 0) {
    echo json_encode($return_arr);
    exit;
}

$questionsTableName = $row['tableName'] ?? '';
$examineesTableName = $row['examineesTableName'] ?? '';
$examineesAnswersTableName = $row['answersTable'] ?? '';

if (empty($questionsTableName) || empty($examineesTableName) || empty($examineesAnswersTableName)) {
    echo json_encode($return_arr);
    exit;
}

// Getting the array of questions the examinee answered from the tested table
$query1 = "SELECT * FROM `$examineesTableName` WHERE examineeUserId='$examineeUserId' AND testId='$testId'";
$result1 = $connection->query($query1);
$row1 = $result1 ? $result1->fetch_assoc() : null;

$questionsArray = array();
if ($row1 && !empty($row1['questArray'])) {
    $decoded = @unserialize($row1['questArray']);
    if (is_array($decoded)) {
        $questionsArray = $decoded;
    }
}
$numberOfQuestions = count($questionsArray);

$totalmarksToBeEarned = 0;
$totalMarksEarned = 0;
for ($i = 0; $i < $numberOfQuestions; $i++) {
    $questionId = (int)$questionsArray[$i];
    
    // Getting the question from the questions table
    $query2 = "SELECT * FROM `$questionsTableName` WHERE questionId='$questionId'";
    $result2 = $connection->query($query2);
    $row2 = $result2 ? $result2->fetch_assoc() : null;
    if (!$row2) {
        continue;
    }

    $question = htmlspecialchars_decode($row2['question'] ?? '', ENT_QUOTES | ENT_HTML5);
    $optionA = htmlspecialchars_decode($row2['optionA'] ?? '', ENT_QUOTES | ENT_HTML5);
    $optionB = htmlspecialchars_decode($row2['optionB'] ?? '', ENT_QUOTES | ENT_HTML5);
    $optionC = htmlspecialchars_decode($row2['optionC'] ?? '', ENT_QUOTES | ENT_HTML5);
    $optionD = htmlspecialchars_decode($row2['optionD'] ?? '', ENT_QUOTES | ENT_HTML5);
    $optionE = htmlspecialchars_decode($row2['optionE'] ?? '', ENT_QUOTES | ENT_HTML5);
    $correctOption = dlhsNormalizeOptionValue($row2['correctOption'] ?? '');
    $mark = isset($row2['markForQuestion']) ? (float)$row2['markForQuestion'] : 0;
    $totalmarksToBeEarned += $mark;
    $solution = htmlspecialchars_decode($row2['solvedSolution'] ?? '', ENT_QUOTES | ENT_HTML5);
    
    // Getting the selected option from the answers table
    $markStatus = "";
    $markObtained = "";
    $selectedOption = "";
    $query3 = "SELECT * FROM `$examineesAnswersTableName` WHERE userLoginId='$examineeUserId' AND questionId='$questionId' AND testId='$testId'";
    $result3 = $connection->query($query3);
    if ($result3 && $result3->num_rows > 0) {
        $row3 = $result3->fetch_assoc();
        $selectedOption = dlhsNormalizeOptionValue($row3['selectedOption'] ?? '');
        if ($selectedOption !== '' && $selectedOption !== '0' && $selectedOption === $correctOption) {
            $totalMarksEarned += $mark;
            $markObtained = $mark;
            $markStatus = 1;
        } else {
            $markObtained = 0;
            $markStatus = 2;
        }
    } else {
        $markObtained = "";
        $markStatus = 3;
    }
    
    $return_arr[] = array(
        "questionId" => $questionId,
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
        "markObtained" => $markObtained
    );
}
echo json_encode($return_arr);
?>
