<?php
session_start();
require_once 'sessionTime.php';

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo 0;
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/question_authoring_helper.php";

if (!isset($_POST['questionId']) || !isset($_SESSION['staffId'])) {
    echo 0;
    exit;
}

$staffId = (int) $_SESSION['staffId'];
$testId = isset($_POST['testId']) ? (int) $_POST['testId'] : 0;
$questionId = isset($_POST['questionId']) ? (int) $_POST['questionId'] : 0;
$sharedStimulusId = isset($_POST['sharedStimulusId']) ? (int) $_POST['sharedStimulusId'] : 0;
$question = isset($_POST['question']) ? (string) $_POST['question'] : '';
$optionA = isset($_POST['optionA']) ? (string) $_POST['optionA'] : '';
$optionB = isset($_POST['optionB']) ? (string) $_POST['optionB'] : '';
$optionC = isset($_POST['optionC']) ? (string) $_POST['optionC'] : '';
$optionD = isset($_POST['optionD']) ? (string) $_POST['optionD'] : '';
$optionE = isset($_POST['optionE']) ? (string) $_POST['optionE'] : '';
$optionCount = (isset($_POST['optionCount']) && (int) $_POST['optionCount'] === 5) ? 5 : 4;
$optionCount = ($optionCount === 4 && dlhsEditorHtmlHasContent($optionE)) ? 5 : $optionCount;
$selectedValue = isset($_POST['selectedValue']) ? strtoupper(trim((string) $_POST['selectedValue'])) : '';
$mark = isset($_POST['mark']) ? trim((string) $_POST['mark']) : '';

$testRow = dlhsFetchTestRowById($connection, $testId);
if (!$testRow || (int) $testRow['staffId'] !== $staffId) {
    echo 3;
    exit;
}

$validation = dlhsValidateObjectiveQuestionEntry(array(
    'question' => $question,
    'optionA' => $optionA,
    'optionB' => $optionB,
    'optionC' => $optionC,
    'optionD' => $optionD,
    'optionE' => $optionE,
    'optionCount' => $optionCount
));

if (!$validation['valid']) {
    echo 5;
    exit;
}

if ($optionCount === 4 && $selectedValue === 'E') {
    $selectedValue = '';
}

$allowedOptions = ($optionCount === 5) ? array('A', 'B', 'C', 'D', 'E') : array('A', 'B', 'C', 'D');
if (!in_array($selectedValue, $allowedOptions, true) || $mark === '') {
    echo 2;
    exit;
}

$questionsTableName = isset($testRow['tableName']) ? $testRow['tableName'] : '';
if ($questionsTableName === '') {
    echo 3;
    exit;
}
$questionsTableSql = dlhsEscapeIdentifier($questionsTableName);

$questionEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($question));
$optionAEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($optionA));
$optionBEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($optionB));
$optionCEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($optionC));
$optionDEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($optionD));
$optionEEscaped = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($optionCount === 5 ? $optionE : ''));
$markEscaped = mysqli_real_escape_string($connection, $mark);

$query = "
    UPDATE {$questionsTableSql}
    SET question='{$questionEscaped}',
        optionA='{$optionAEscaped}',
        optionB='{$optionBEscaped}',
        optionC='{$optionCEscaped}',
        optionD='{$optionDEscaped}',
        optionE='{$optionEEscaped}',
        correctOption='{$selectedValue}',
        markForQuestion='{$markEscaped}'
    WHERE questionId='{$questionId}'
";

$result = $connection->query($query);
if (!$result) {
    echo 2;
    exit;
}

dlhsAssignStimulusToQuestion($connection, $testId, $questionId, $sharedStimulusId);

echo 1;
