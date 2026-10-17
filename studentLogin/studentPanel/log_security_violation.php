<?php
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['studentId']) || !isset($_SESSION['idOfTest'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$studentId = (int)$_SESSION['studentId'];
$testId = (int)$_SESSION['idOfTest'];
$testedTableName = $_SESSION['testedTableName'] ?? null;

// Release session lock immediately to prevent blocking student UI
session_write_close();

require_once('../../db_connection/dlhs_db_connection.php');

// Get violation details
$violation = $_POST['violation'] ?? 'Unknown violation';
$timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
$userAgent = $_POST['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$screenResolution = $_POST['screenResolution'] ?? 'Unknown';
$violationCount = intval($_POST['count'] ?? 1);
$autoSubmit = isset($_POST['autoSubmit']) ? 1 : 0;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Insert violation record
$stmt = $connection->prepare("
    INSERT INTO security_violations 
    (studentId, testId, violation, timestamp, userAgent, screenResolution, violationCount, autoSubmit, ipAddress)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmt) {
    $stmt->bind_param(
        "iissssiis",
        $studentId,
        $testId,
        $violation,
        $timestamp,
        $userAgent,
        $screenResolution,
        $violationCount,
        $autoSubmit,
        $ipAddress
    );

    if ($stmt->execute()) {
        // If this is an auto-submit violation, flag the test
        if ($autoSubmit && $testedTableName) {
            $flagQuery = "UPDATE `$testedTableName` SET 
                         testStatus = 2,
                         timeSubmittedTest = NOW()
                         WHERE examineeUserId = ? AND testId = ?";
            $flagStmt = $connection->prepare($flagQuery);
            if ($flagStmt) {
                $flagStmt->bind_param("ii", $studentId, $testId);
                $flagStmt->execute();
                $flagStmt->close();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Violation logged',
            'violationCount' => $violationCount
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to log violation'
        ]);
    }

    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>
