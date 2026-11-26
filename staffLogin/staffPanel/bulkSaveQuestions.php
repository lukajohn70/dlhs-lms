<?php
session_start();
require_once 'sessionTime.php';

header('Content-Type: application/json');

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
$isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'yes' && empty($_SESSION['staffLoggedIn']);

if (!isset($_POST['testId']) || !isset($_POST['questions']) || (!$isAdmin && $staffId === 0)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/question_authoring_helper.php";

$testId = (int) $_POST['testId'];
$questions = json_decode($_POST['questions'], true);

if (!is_array($questions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid questions data']);
    exit;
}

$testRow = dlhsFetchTestRowById($connection, $testId);
if (!$testRow || ($staffId > 0 && (int) $testRow['staffId'] !== $staffId) || ($staffId === 0 && !$isAdmin)) {
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

$connection->begin_transaction();

try {
    $stmt = $connection->prepare("
        INSERT INTO {$questionsTableSql}
            (question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution, questionSeriaNo)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, '', 0)
    ");

    if (!$stmt) {
        throw new Exception("Database prepare statement failed: " . $connection->error);
    }

    $qText = '';
    $optA = '';
    $optB = '';
    $optC = '';
    $optD = '';
    $optE = '';
    $corr = 'A';
    $mark = '1';

    $stmt->bind_param("ssssssss", $qText, $optA, $optB, $optC, $optD, $optE, $corr, $mark);

    foreach ($questions as $q) {
        $qText = dlhsEncodeHtmlForStorage($q['question'] ?? '');
        $optA = dlhsEncodeHtmlForStorage($q['optionA'] ?? '');
        $optB = dlhsEncodeHtmlForStorage($q['optionB'] ?? '');
        $optC = dlhsEncodeHtmlForStorage($q['optionC'] ?? '');
        $optD = dlhsEncodeHtmlForStorage($q['optionD'] ?? '');
        $optE = dlhsEncodeHtmlForStorage($q['optionE'] ?? '');
        $corr = strtoupper(trim((string) ($q['correctOption'] ?? 'A')));
        $mark = trim((string) ($q['mark'] ?? '1'));

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $failCount++;
        }
    }

    $stmt->close();
    $connection->commit();
} catch (Exception $e) {
    $connection->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error saving questions: ' . $e->getMessage()
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "Successfully imported $successCount questions." . ($failCount > 0 ? " ($failCount failed)" : "")
]);
