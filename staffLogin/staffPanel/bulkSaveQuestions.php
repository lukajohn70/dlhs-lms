<?php
session_start();
require_once 'sessionTime.php';

header('Content-Type: application/json');

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

if (!isset($_POST['testId']) || !isset($_POST['questions']) || !isset($_SESSION['staffId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/question_authoring_helper.php";

$staffId = (int) $_SESSION['staffId'];
$testId = (int) $_POST['testId'];
$questions = json_decode($_POST['questions'], true);

if (!is_array($questions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid questions data']);
    exit;
}

$testRow = dlhsFetchTestRowById($connection, $testId);
if (!$testRow || (int) $testRow['staffId'] !== $staffId) {
    echo json_encode(['success' => false, 'message' => 'Test not found or unauthorized']);
    exit;
}

$questionsTableName = isset($testRow['tableName']) ? $testRow['tableName'] : '';
if ($questionsTableName === '') {
    echo json_encode(['success' => false, 'message' => 'Questions table not found']);
    exit;
}
$questionsTableSql = dlhsEscapeIdentifier($questionsTableName);

$successCount = 0;
$failCount = 0;

foreach ($questions as $q) {
    $questionText = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['question'] ?? ''));
    $optionA = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['optionA'] ?? ''));
    $optionB = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['optionB'] ?? ''));
    $optionC = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['optionC'] ?? ''));
    $optionD = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['optionD'] ?? ''));
    $optionE = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($q['optionE'] ?? ''));
    $correctOption = mysqli_real_escape_string($connection, $q['correctOption'] ?? 'A');
    $mark = mysqli_real_escape_string($connection, $q['mark'] ?? '1');

    $query = "
        INSERT INTO {$questionsTableSql}
            (question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution, questionSeriaNo)
        VALUES
            ('{$questionText}', '{$optionA}', '{$optionB}', '{$optionC}', '{$optionD}', '{$optionE}', '{$correctOption}', '{$mark}', '', 0)
    ";

    if ($connection->query($query)) {
        $successCount++;
    } else {
        $failCount++;
    }
}

echo json_encode([
    'success' => true,
    'message' => "Successfully imported $successCount questions." . ($failCount > 0 ? " ($failCount failed)" : "")
]);
