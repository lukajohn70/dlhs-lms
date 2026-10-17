<?php
session_start();
require_once 'userExpiredSession.php';

header('Content-Type: application/json');

if (!isset($_SESSION['staffLoggedIn'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/question_authoring_helper.php";

if (!isset($_POST['testId']) || !isset($_POST['questions'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required POST parameters.']);
    exit;
}

$testId = $_POST['testId'];
$questionsJson = $_POST['questions'];
$questions = json_decode($questionsJson, true);

if (!is_array($questions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid questions data format.']);
    exit;
}

$testRow = dlhsFetchTestRowById($connection, $testId);
if (!$testRow) {
    echo json_encode(['success' => false, 'error' => 'Selected test does not exist.']);
    exit;
}

$questionEntryCheck = dlhsCheckQuestionEntryAllowed($connection, $testRow);
if (!$questionEntryCheck['allowed']) {
    echo json_encode(['success' => false, 'error' => 'Cannot add questions: ' . $questionEntryCheck['message']]);
    exit;
}

$questionsTableName = $testRow['tableName'];
$questionsTableSql = dlhsEscapeIdentifier($questionsTableName);

$insertedCount = 0;
$updatedCount = 0;

// Start database transaction for safety
$connection->begin_transaction();

try {
    foreach ($questions as $q) {
        $questionSerialNo = mysqli_real_escape_string($connection, (string)$q['serialNo']);
        $question = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$q['question']));
        $optionA = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$q['optionA']));
        $optionB = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$q['optionB']));
        $optionC = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$q['optionC']));
        $optionD = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage((string)$q['optionD']));
        $correctOption = trim(strtoupper((string)$q['correctOption']));
        $markForQuestion = mysqli_real_escape_string($connection, (string)$q['mark']);

        // Check if already exists
        $checkIfAlreadyUploaded = "SELECT * FROM {$questionsTableSql} WHERE questionSeriaNo='$questionSerialNo'";
        $result1 = $connection->query($checkIfAlreadyUploaded);
        
        if ($result1 && ($result1->num_rows) > 0) {
            $updateRecord = "UPDATE {$questionsTableSql} SET question='$question', optionA='$optionA', optionB='$optionB', optionC='$optionC', optionD='$optionD', correctOption='$correctOption', markForQuestion='$markForQuestion' WHERE questionSeriaNo='$questionSerialNo'";
            $result2 = $connection->query($updateRecord);
            if (!$result2) {
                throw new Exception("Failed to update question S/NO " . $questionSerialNo . ": " . $connection->error);
            }
            $updatedCount++;
        } else {
            $insertRecord = "INSERT INTO {$questionsTableSql}(question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution, questionSeriaNo) VALUES('{$question}', '{$optionA}', '{$optionB}', '{$optionC}', '{$optionD}', '', '{$correctOption}', '{$markForQuestion}', '', '{$questionSerialNo}')";
            $result2 = $connection->query($insertRecord);
            if (!$result2) {
                throw new Exception("Failed to insert question S/NO " . $questionSerialNo . ": " . $connection->error);
            }
            $insertedCount++;
        }
    }

    $connection->commit();
    echo json_encode([
        'success' => true,
        'insertedCount' => $insertedCount,
        'updatedCount' => $updatedCount
    ]);
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
