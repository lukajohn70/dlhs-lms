<?php
/**
 * Start Test Override - Admin Only
 * 
 * This script allows administrators to manually start a test immediately,
 * regardless of the scheduled date/time. This bypasses all time/date checks.
 * 
 * Return codes:
 * 0 = Session expired
 * 1 = Success
 * 2 = Test already in progress
 * 3 = Test already completed
 * 4 = Database error or invalid test
 */

session_start();
require_once 'userExpiredSession.php';

// Security check
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0; // Session expired
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

// Get test ID
if (!isset($_POST['testId'])) {
    echo 4; // Invalid request
    exit();
}

$testId = intval($_POST['testId']);

if ($testId <= 0) {
    echo 4; // Invalid test ID
    exit();
}

// Get test details before starting
$testQuery = "SELECT testId, testName, status, testDate, startHour, startMinute, amOrPm 
              FROM tests 
              WHERE testId = $testId";
$testResult = $connection->query($testQuery);

if (!$testResult || $testResult->num_rows == 0) {
    echo 4; // Test not found
    exit();
}

$test = $testResult->fetch_assoc();

// Check if test is already started
if ($test['status'] == 1) {
    echo 2; // Already in progress
    exit();
}

// Check if test is already completed
if ($test['status'] == 2) {
    echo 3; // Already completed
    exit();
}

// Start the test immediately (set status to 1 = In Progress)
// This bypasses all date/time checks - ADMIN OVERRIDE
$updateQuery = "UPDATE tests 
                SET status = 1 
                WHERE testId = $testId";

if ($connection->query($updateQuery)) {
    // Log this action for audit trail
    $adminId = isset($_SESSION['adminId']) ? $_SESSION['adminId'] : 0;
    $adminName = isset($_SESSION['adminName']) ? $_SESSION['adminName'] : 'Unknown Admin';
    $testName = $test['testName'];
    $originalTime = $test['startHour'] . ':' . str_pad($test['startMinute'], 2, '0', STR_PAD_LEFT) . ' ' . $test['amOrPm'];
    $originalDate = $test['testDate'];
    
    // Create log directory if it doesn't exist
    if (!file_exists("../../logs")) {
        @mkdir("../../logs", 0777, true);
    }
    
    // Log to file
    $logMessage = date('Y-m-d H:i:s') . " - ADMIN OVERRIDE: Test '$testName' (ID: $testId) started immediately by $adminName (ID: $adminId). Original schedule: $originalDate at $originalTime\n";
    @error_log($logMessage, 3, "../../logs/test_override.log");
    
    echo 1; // Success
} else {
    echo 4; // Database error
}

$connection->close();
?>

