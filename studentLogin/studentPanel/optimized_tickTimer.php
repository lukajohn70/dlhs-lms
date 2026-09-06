<?php
/**
 * Optimized Exam Timer Handler
 * Reduces server load by:
 * 1. Client-side countdown with periodic server sync (every 30s instead of 1s)
 * 2. Batched updates for multiple operations
 * 3. Efficient database queries
 */

session_start();
require_once "../../db_connection/optimized_db_connection.php";

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Validate session
if (!isset($_SESSION['studentLast_login']) || !isset($_SESSION['studentId']) || 
    !isset($_SESSION['idOfTest']) || !isset($_SESSION['testedTableName'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized', 
        'remainingTime' => 0, 
        'shouldSubmit' => true,
        'redirect' => 'logout.php'
    ]);
    exit;
}

$dbInstance = OptimizedDBConnection::getInstance();
$connection = $dbInstance->getConnection();

$studentId = intval($_SESSION['studentId']);
$testId = intval($_SESSION['idOfTest']);
$testedTableName = $connection->real_escape_string($_SESSION['testedTableName']);

// Get client-reported remaining time (for validation)
$clientTime = isset($_POST['clientTime']) ? intval($_POST['clientTime']) : null;

try {
    // Use prepared statement for security and performance
    $stmt = $connection->prepare(
        "SELECT remainingTime, testStatus FROM `$testedTableName` 
         WHERE examineeUserId = ? AND testId = ? LIMIT 1"
    );
    
    if (!$stmt) {
        throw new Exception("Database prepare failed");
    }
    
    $stmt->bind_param("ii", $studentId, $testId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        echo json_encode([
            'success' => false, 
            'message' => 'Test status not found', 
            'remainingTime' => 0, 
            'shouldSubmit' => true,
            'redirect' => 'logout.php'
        ]);
        exit;
    }
    
    $serverRemainingTime = intval($row['remainingTime']);
    $testStatus = intval($row['testStatus']);
    
    // Check if test is already completed
    if ($testStatus == 2) {
        echo json_encode([
            'success' => false, 
            'message' => 'Test already completed', 
            'remainingTime' => 0, 
            'shouldSubmit' => true,
            'redirect' => 'result/index.php'
        ]);
        exit;
    }
    
    // Compute elapsed time since last server sync
    $now = time();
    if (!isset($_SESSION['lastServerSync'])) {
        $_SESSION['lastServerSync'] = $now;
    }
    
    $elapsed = $now - intval($_SESSION['lastServerSync']);
    
    // Update remaining time
    $newRemainingTime = max(0, $serverRemainingTime - $elapsed);
    
    // Validate against client time to detect cheating/clock manipulation
    if ($clientTime !== null) {
        $timeDiff = abs($clientTime - $newRemainingTime);
        
        // If difference is more than 5 seconds, trust server time
        if ($timeDiff > 5) {
            error_log("Time mismatch for student $studentId, test $testId: Client=$clientTime, Server=$newRemainingTime");
            // Use server time as authoritative
        }
    }
    
    // Update database only if significant time has passed (reduce DB writes)
    if ($elapsed >= 30 || $newRemainingTime <= 0) {
        $updateStmt = $connection->prepare(
            "UPDATE `$testedTableName` SET remainingTime = ? WHERE examineeUserId = ? AND testId = ?"
        );
        $updateStmt->bind_param("iii", $newRemainingTime, $studentId, $testId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Update session sync timestamp
        $_SESSION['lastServerSync'] = $now;
    }
    
    $stmt->close();
    
    $shouldSubmit = ($newRemainingTime <= 0);
    
    // Prepare response
    $response = [
        'success' => true,
        'remainingTime' => $newRemainingTime,
        'shouldSubmit' => $shouldSubmit,
        'serverTime' => $now,
        'syncInterval' => 30 // Tell client to sync every 30 seconds
    ];
    
    // Add warning when time is running low
    if ($newRemainingTime > 0 && $newRemainingTime <= 300) { // Last 5 minutes
        $response['warning'] = 'Time running out!';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Timer error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'remainingTime' => 0,
        'shouldSubmit' => false
    ]);
}

exit;
?>




