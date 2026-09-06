<?php
session_start();
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0; // Not authorized
} else {
    include "../../db_connection/dlhs_db_connection.php";
    
    // Check if connection is successful
    if (!$connection) {
        echo 5; // Database connection failed
        exit();
    }
    
    // Get all tests that are currently in progress (status = 1)
    $query = "SELECT testId, testName FROM tests WHERE status = 1";
    $result = $connection->query($query);
    
    if (!$result) {
        echo 6; // Query failed
        exit();
    }
    
    if ($result->num_rows > 0) {
        $testsEnded = 0;
        
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $testId = mysqli_real_escape_string($connection, $row[0]);
            $testName = mysqli_real_escape_string($connection, $row[1]);
            
            // Update test status to ended (status = 2)
            $updateQuery = "UPDATE tests SET status = 2 WHERE testId = '$testId'";
            if ($connection->query($updateQuery)) {
                $testsEnded++;
                
                // Try to log this action for audit purposes (optional - don't fail if logging fails)
                if (isset($_SESSION['adminId']) && isset($_SESSION['adminName'])) {
                    $adminId = mysqli_real_escape_string($connection, $_SESSION['adminId']);
                    $adminName = mysqli_real_escape_string($connection, $_SESSION['adminName']);
                    $logMessage = "Admin '$adminName' ended test '$testName' (ID: $testId) via 'End All Tests' function";
                    $logQuery = "INSERT INTO admin_actions_log (adminId, action, details, timestamp) VALUES ('$adminId', 'END_ALL_TESTS', '$logMessage', NOW())";
                    $connection->query($logQuery); // Don't check result - logging is optional
                }
            }
        }
        
        if ($testsEnded > 0) {
            echo 1; // Success - tests ended
        } else {
            echo 2; // No tests were ended (database error)
        }
    } else {
        echo 3; // No tests in progress to end
    }
}
?>
