<?php
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../../db_connection/dlhs_db_connection.php";
require_once __DIR__ . "/../../scripts/essay_timer_helper.php";

header('Content-Type: application/json; charset=utf-8');

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
$testId = 0;
if (isset($_POST['testId']) && $_POST['testId'] !== '') {
    $testId = (int) $_POST['testId'];
} elseif (isset($_SESSION['testId'])) {
    $testId = (int) $_SESSION['testId'];
} elseif (isset($_SESSION['idOfTest'])) {
    $testId = (int) $_SESSION['idOfTest'];
}

if ($testId > 0) {
    $_SESSION['testId'] = $testId;
    $_SESSION['idOfTest'] = $testId;
}
session_write_close();

if ($studentId <= 0 || $testId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Session expired'));
    exit;
}

$testStmt = $connection->prepare("SELECT essayTime, duration, tableName, examineesTableName FROM tests WHERE testId = ? LIMIT 1");
if (!$testStmt) {
    echo json_encode(array('success' => false, 'message' => 'Unable to load test settings.'));
    exit;
}

$testStmt->bind_param('i', $testId);
$testStmt->execute();
$testResult = $testStmt->get_result();
$testRow = $testResult ? $testResult->fetch_assoc() : null;
$testStmt->close();

$essayMinutes = !empty($testRow) ? (int) $testRow['essayTime'] : 0;
if ($essayMinutes <= 0 && !empty($testRow)) {
    $essayMinutes = isset($testRow['duration']) ? (int) $testRow['duration'] : 0;
}

$essayAttempt = dlhsGetOrCreateEssayAttempt($connection, $testId, $studentId, $essayMinutes);
$isExpired = false;
if ($essayMinutes > 0 && (!$essayAttempt || dlhsGetEssayRemainingSeconds($essayAttempt) <= 0)) {
    $isExpired = true;
}

$essayAnswer = isset($_POST['essayAnswer']) ? trim($_POST['essayAnswer']) : '';
if ($isExpired) {
    $essayAnswer = 'Essay completed (Time Expired)';
} elseif ($essayAnswer === '') {
    echo json_encode(array('success' => false, 'message' => 'Please write your essay before submitting.'));
    exit;
}

// Fast parameterized check & save into essay_answers
$checkStmt = $connection->prepare("SELECT answerId FROM essay_answers WHERE testId = ? AND studentId = ? LIMIT 1");
$existingAnswerId = 0;
if ($checkStmt) {
    $checkStmt->bind_param('ii', $testId, $studentId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    if ($checkRes && ($checkRow = $checkRes->fetch_assoc())) {
        $existingAnswerId = (int) $checkRow['answerId'];
    }
    $checkStmt->close();
}

$result = false;
if ($existingAnswerId > 0) {
    $saveStmt = $connection->prepare("UPDATE essay_answers SET essayAnswer = ?, submittedAt = CURRENT_TIMESTAMP WHERE answerId = ?");
    if ($saveStmt) {
        $saveStmt->bind_param('si', $essayAnswer, $existingAnswerId);
        $result = $saveStmt->execute();
        $saveStmt->close();
    }
} else {
    $saveStmt = $connection->prepare("INSERT INTO essay_answers (testId, studentId, essayAnswer, submittedAt) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
    if ($saveStmt) {
        $saveStmt->bind_param('iis', $testId, $studentId, $essayAnswer);
        $result = $saveStmt->execute();
        $saveStmt->close();
    }
}

// Determine destination: direct to exam.php if objective questions exist
$nextUrl = 'exam.php';
if (!empty($testRow['examineesTableName']) && !empty($testRow['tableName'])) {
    $nextUrl = 'exam.php';
} else {
    $nextUrl = 'index.php';
}

if ($result) {
    dlhsMarkEssaySubmitted($connection, $testId, $studentId);
    echo json_encode(array(
        'success' => true,
        'message' => 'Essay submitted successfully!',
        'nextUrl' => $nextUrl
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Failed to submit essay: ' . $connection->error));
}
?>
