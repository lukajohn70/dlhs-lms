<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
require_once 'userExpiredSession.php';

if (!isset($_SESSION['adminLoggedIn']))
{
    echo 0;
}
else
{
    if(isset($_POST['selectedTests']) && !empty($_POST['selectedTests']))
    {   
        $selectedTests = $_POST['selectedTests'];
        
        // Split the comma-separated test IDs and sanitize them
        $testIds = explode(',', $selectedTests);
        $deletedCount = 0;
        $failedCount = 0;
        $errors = array();
        
        foreach($testIds as $testId)
        {
            $testId = trim($testId);
            
            // Validate test ID is numeric
            if (!is_numeric($testId) || $testId <= 0) {
                $failedCount++;
                $errors[] = "Invalid test ID: $testId";
                continue;
            }
            
            // Use prepared statement to get test details
            $stmt = $connection->prepare("SELECT * FROM tests WHERE testId = ?");
            $stmt->bind_param("i", $testId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0)
            {
                $row = $result->fetch_assoc();
                $testName = $row['testName'];
                $questionsTableName = $row['tableName'];
                $examineesTableName = $row['examineesTableName'];
                $answersTableName = $row['answersTable'];
                
                // Start transaction for atomicity
                $connection->autocommit(FALSE);
                
                try {
                    $tableDeletionSuccess = true;
                    
                    // Delete the questions table
                    if (!empty($questionsTableName)) {
                        $query1 = "DROP TABLE IF EXISTS `$questionsTableName`";
                        if (!$connection->query($query1)) {
                            $tableDeletionSuccess = false;
                            $errors[] = "Failed to delete questions table for test $testId: $questionsTableName";
                        }
                    }
                    
                    // Delete the examinees table
                    if (!empty($examineesTableName)) {
                        $query2 = "DROP TABLE IF EXISTS `$examineesTableName`";
                        if (!$connection->query($query2)) {
                            $tableDeletionSuccess = false;
                            $errors[] = "Failed to delete examinees table for test $testId: $examineesTableName";
                        }
                    }
                    
                    // Delete the answers table
                    if (!empty($answersTableName)) {
                        $query4 = "DROP TABLE IF EXISTS `$answersTableName`";
                        if (!$connection->query($query4)) {
                            $tableDeletionSuccess = false;
                            $errors[] = "Failed to delete answers table for test $testId: $answersTableName";
                        }
                    }
                    
                    // Delete the main test record
                    $deleteStmt = $connection->prepare("DELETE FROM tests WHERE testId = ?");
                    $deleteStmt->bind_param("i", $testId);
                    $deleteResult = $deleteStmt->execute();
                    
                    if ($deleteResult && $tableDeletionSuccess) {
                        // Commit the transaction
                        $connection->commit();
                        $deletedCount++;
                        
                        // Log the deletion for audit purposes
                        $logMessage = "Admin bulk deleted test ID: $testId, Name: $testName. Tables deleted: " . implode(', ', array_filter([$questionsTableName, $examineesTableName, $answersTableName]));
                        error_log($logMessage);
                    } else {
                        $connection->rollback();
                        $failedCount++;
                        $errors[] = "Failed to delete test record for test $testId";
                    }
                    
                    $deleteStmt->close();
                    
                } catch (Exception $e) {
                    // Rollback on any error
                    $connection->rollback();
                    $failedCount++;
                    $errors[] = "Error deleting test $testId: " . $e->getMessage();
                    error_log("Error deleting test $testId: " . $e->getMessage());
                }
                
                // Re-enable autocommit
                $connection->autocommit(TRUE);
            }
            else
            {
                $failedCount++;
                $errors[] = "Test not found with ID: $testId";
            }
            
            $stmt->close();
        }
        
        // Log any errors
        if (!empty($errors)) {
            error_log("Bulk test deletion errors: " . implode(', ', $errors));
        }
        
        if($deletedCount > 0 && $failedCount == 0)
        {
            echo 1; // All tests deleted successfully
        }
        else if($deletedCount > 0 && $failedCount > 0)
        {
            echo 2; // Some tests deleted, some failed
        }
        else
        {
            echo 3; // No tests were deleted
        }
    }
    else
    {
        echo 0; // No tests selected or empty input
    }
}
?>

