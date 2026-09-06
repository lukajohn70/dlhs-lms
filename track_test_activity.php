<?php
/**
 * Active Test Taker Tracking Script
 * Include this in test-taking pages to track active exam sessions
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connection/dlhs_db_connection.php';
require_once __DIR__ . '/scripts/online_tracking_schema.php';

function trackTestActivity($testId, $testName, $testedTableName, $remainingTime = null, $questionsAnswered = 0, $totalQuestions = 0) {
    global $connection;
    
    if (!isset($_SESSION['studentId'])) {
        return false;
    }

    if (!dlhsEnsureOnlineTrackingTables($connection)) {
        return false;
    }
    
    $studentId = $_SESSION['studentId'];
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = date('Y-m-d H:i:s');
    
    // Get student name
    $studentName = '';
    $stmt = $connection->prepare("SELECT CONCAT(firstName, ' ', surname) as fullName FROM studentlogin WHERE studentId = ?");
    if (!$stmt) {
        error_log('Active test student lookup prepare failed: ' . $connection->error);
        return false;
    }
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $studentName = $row['fullName'];
    }
    $stmt->close();
    
    // Insert or update test activity
    $stmt = $connection->prepare("
        INSERT INTO active_test_takers 
        (studentId, studentName, testId, testName, testedTableName, startedAt, lastActivityAt, 
         remainingTime, questionsAnswered, totalQuestions, ipAddress, sessionId, isActive)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            lastActivityAt = VALUES(lastActivityAt),
            remainingTime = VALUES(remainingTime),
            questionsAnswered = VALUES(questionsAnswered),
            ipAddress = VALUES(ipAddress),
            isActive = 1
    ");

    if (!$stmt) {
        error_log('Active test tracking prepare failed: ' . $connection->error);
        return false;
    }
    
    $stmt->bind_param("isissssiiiss", 
        $studentId, $studentName, $testId, $testName, $testedTableName, 
        $now, $now, $remainingTime, $questionsAnswered, $totalQuestions, $ipAddress, $sessionId
    );
    
    $stmt->execute();
    $stmt->close();
    
    // Clean up completed/abandoned tests (no activity in last 10 minutes)
    $cleanupTime = date('Y-m-d H:i:s', strtotime('-10 minutes'));
    $connection->query("UPDATE active_test_takers SET isActive = 0 WHERE lastActivityAt < '$cleanupTime'");
    
    return true;
}

function markTestCompleted($testId) {
    global $connection;
    
    if (!isset($_SESSION['studentId'])) {
        return false;
    }

    if (!dlhsEnsureOnlineTrackingTables($connection)) {
        return false;
    }
    
    $studentId = $_SESSION['studentId'];
    $sessionId = session_id();
    
    $stmt = $connection->prepare("UPDATE active_test_takers SET isActive = 0 WHERE studentId = ? AND testId = ? AND sessionId = ?");
    if (!$stmt) {
        error_log('Active test completion prepare failed: ' . $connection->error);
        return false;
    }
    $stmt->bind_param("iis", $studentId, $testId, $sessionId);
    $stmt->execute();
    $stmt->close();
    
    return true;
}
?>
