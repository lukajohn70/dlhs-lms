<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

$testIds = isset($_POST['testIds']) ? $_POST['testIds'] : array();
$reason = isset($_POST['reason']) ? $_POST['reason'] : '';
$adminId = $_SESSION['adminId'];
$archivedDate = date('Y-m-d H:i:s');

if (empty($testIds)) {
    echo "No tests selected";
    exit();
}

// Begin transaction
$connection->begin_transaction();

try {
    foreach ($testIds as $testId) {
        $testId = intval($testId);
        
        // Get test details for audit
        $testQuery = "SELECT testName, academicSession, term, testType FROM tests WHERE testId = $testId";
        $testResult = $connection->query($testQuery);
        $testData = $testResult->fetch_assoc();
        
        // Update test to archived
        $updateQuery = "UPDATE tests SET 
                        isArchived = 1,
                        archivedDate = '$archivedDate',
                        archivedBy = $adminId
                        WHERE testId = $testId";
        
        if (!$connection->query($updateQuery)) {
            throw new Exception("Failed to archive test ID: $testId");
        }
        
        // Insert audit record
        $auditQuery = "INSERT INTO test_archives 
                       (testId, testName, archivedBy, archivedDate, academicSession, term, testType, reason)
                       VALUES 
                       ($testId, '" . $connection->real_escape_string($testData['testName']) . "', 
                        $adminId, '$archivedDate', 
                        '" . $connection->real_escape_string($testData['academicSession']) . "',
                        '" . $connection->real_escape_string($testData['term']) . "',
                        '" . $connection->real_escape_string($testData['testType']) . "',
                        '" . $connection->real_escape_string($reason) . "')";
        
        if (!$connection->query($auditQuery)) {
            throw new Exception("Failed to create audit record for test ID: $testId");
        }
    }
    
    $connection->commit();
    echo 1;
    
} catch (Exception $e) {
    $connection->rollback();
    echo "Error: " . $e->getMessage();
}
?>
