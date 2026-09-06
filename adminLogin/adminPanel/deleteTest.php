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
    if(isset($_POST['testId']) && !empty($_POST['testId']))
    {   
        $testId = trim($_POST['testId']);
        
        // Validate test ID is numeric
        if (!is_numeric($testId) || $testId <= 0) {
            echo 3; // Invalid test ID
            exit;
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
            
            $deletionErrors = array();
            $successCount = 0;
            
            // Start transaction for atomicity
            $connection->autocommit(FALSE);
            
            try {
                // Delete the questions table
                if (!empty($questionsTableName)) {
                    $query1 = "DROP TABLE IF EXISTS `$questionsTableName`";
                    $result1 = $connection->query($query1);
                    if ($result1) {
                        $successCount++;
                    } else {
                        $deletionErrors[] = "Failed to delete questions table: $questionsTableName";
                    }
                }
                
                // Delete the examinees table
                if (!empty($examineesTableName)) {
                    $query2 = "DROP TABLE IF EXISTS `$examineesTableName`";
                    $result2 = $connection->query($query2);
                    if ($result2) {
                        $successCount++;
                    } else {
                        $deletionErrors[] = "Failed to delete examinees table: $examineesTableName";
                    }
                }
                
                // Delete the answers table
                if (!empty($answersTableName)) {
                    $query4 = "DROP TABLE IF EXISTS `$answersTableName`";
                    $result4 = $connection->query($query4);
                    if ($result4) {
                        $successCount++;
                    } else {
                        $deletionErrors[] = "Failed to delete answers table: $answersTableName";
                    }
                }
                
                // Delete the main test record
                $deleteStmt = $connection->prepare("DELETE FROM tests WHERE testId = ?");
                $deleteStmt->bind_param("i", $testId);
                $deleteResult = $deleteStmt->execute();
                
                if ($deleteResult) {
                    $successCount++;
                    
                    // Commit the transaction
                    $connection->commit();
                    
                    // Log the deletion for audit purposes
                    $logMessage = "Admin deleted test ID: $testId, Name: $testName. Tables deleted: " . implode(', ', array_filter([$questionsTableName, $examineesTableName, $answersTableName]));
                    error_log($logMessage);
                    
                    echo 1; // Success
                } else {
                    $connection->rollback();
                    $deletionErrors[] = "Failed to delete test record";
                    echo 2; // Failure
                }
                
                $deleteStmt->close();
                
            } catch (Exception $e) {
                // Rollback on any error
                $connection->rollback();
                error_log("Error deleting test $testId: " . $e->getMessage());
                echo 2; // Failure
            }
            
            // Re-enable autocommit
            $connection->autocommit(TRUE);
            
            // Log any errors
            if (!empty($deletionErrors)) {
                error_log("Test deletion errors for ID $testId: " . implode(', ', $deletionErrors));
            }
        }
        else
        {
            echo 4; // Test not found
        }
        
        $stmt->close();
    }
    else
    {
        echo 0; // No test ID provided
    }
}
?>