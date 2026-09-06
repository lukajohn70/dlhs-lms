<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

$sessionName = isset($_POST['sessionName']) ? $_POST['sessionName'] : '';

if (empty($sessionName)) {
    echo "Invalid session name";
    exit();
}

// Begin transaction
$connection->begin_transaction();

try {
    // Set all sessions to not current
    $resetQuery = "UPDATE academic_sessions SET isCurrentSession = 0";
    if (!$connection->query($resetQuery)) {
        throw new Exception("Failed to reset sessions");
    }
    
    // Set the selected session as current
    $updateQuery = "UPDATE academic_sessions SET isCurrentSession = 1 
                    WHERE sessionName = '" . $connection->real_escape_string($sessionName) . "'";
    
    if (!$connection->query($updateQuery)) {
        throw new Exception("Failed to set current session");
    }
    
    $connection->commit();
    echo 1;
    
} catch (Exception $e) {
    $connection->rollback();
    echo "Error: " . $e->getMessage();
}
?>
