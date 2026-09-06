<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

$testIds = isset($_POST['testIds']) ? $_POST['testIds'] : array();

if (empty($testIds)) {
    echo "No tests selected";
    exit();
}

// Begin transaction
$connection->begin_transaction();

try {
    foreach ($testIds as $testId) {
        $testId = intval($testId);
        
        // Update test to restore from archive
        $updateQuery = "UPDATE tests SET 
                        isArchived = 0,
                        archivedDate = NULL,
                        archivedBy = NULL
                        WHERE testId = $testId";
        
        if (!$connection->query($updateQuery)) {
            throw new Exception("Failed to restore test ID: $testId");
        }
    }
    
    $connection->commit();
    echo 1;
    
} catch (Exception $e) {
    $connection->rollback();
    echo "Error: " . $e->getMessage();
}
?>
