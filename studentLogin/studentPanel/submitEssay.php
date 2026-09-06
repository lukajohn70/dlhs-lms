<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/essay_timer_helper.php";

header('Content-Type: application/json');

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
$testId = 0;
if (isset($_POST['testId']) && $_POST['testId'] !== '') {
    $testId = (int) $_POST['testId'];
} elseif (isset($_SESSION['testId'])) {
    $testId = (int) $_SESSION['testId'];
} elseif (isset($_SESSION['idOfTest'])) {
    $testId = (int) $_SESSION['idOfTest'];
}

if ($studentId <= 0 || $testId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Session expired'));
    exit;
}

$testStmt = $connection->prepare("SELECT essayTime, duration FROM tests WHERE testId = ? LIMIT 1");
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

$createTableQuery = "CREATE TABLE IF NOT EXISTS essay_answers (
    answerId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    testId INT NOT NULL,
    studentId INT NOT NULL,
    essayAnswer TEXT NOT NULL,
    submittedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_test_student (testId, studentId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
$connection->query($createTableQuery);

$essayAnswerEscaped = $connection->real_escape_string($essayAnswer);
$checkQuery = "SELECT * FROM essay_answers WHERE testId='{$testId}' AND studentId='{$studentId}'";
$checkResult = $connection->query($checkQuery);

if ($checkResult && $checkResult->num_rows > 0) {
    $updateQuery = "UPDATE essay_answers SET essayAnswer='{$essayAnswerEscaped}', submittedAt=CURRENT_TIMESTAMP WHERE testId='{$testId}' AND studentId='{$studentId}'";
    $result = $connection->query($updateQuery);
} else {
    $insertQuery = "INSERT INTO essay_answers (testId, studentId, essayAnswer) VALUES ('{$testId}', '{$studentId}', '{$essayAnswerEscaped}')";
    $result = $connection->query($insertQuery);
}

if ($result) {
    dlhsMarkEssaySubmitted($connection, $testId, $studentId);
    echo json_encode(array('success' => true, 'message' => 'Essay submitted successfully!'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Failed to submit essay: ' . $connection->error));
}
?>
