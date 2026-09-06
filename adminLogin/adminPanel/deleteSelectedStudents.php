<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn']))
{
    echo 0;
}
else
{
    include "../../db_connection/dlhs_db_connection.php";
    
    if(isset($_POST['checkedStudents']) && !empty($_POST['checkedStudents']))
    {    
        $checkedStudents = $_POST['checkedStudents'];
        
        // Split the comma-separated student IDs and sanitize them
        $studentIds = explode(',', $checkedStudents);
        $deletedCount = 0;
        $failedCount = 0;
        $errors = array();
        
        foreach($studentIds as $studentId)
        {
            $studentId = trim($studentId);
            
            // Validate student ID is numeric
            if (!is_numeric($studentId) || $studentId <= 0) {
                $failedCount++;
                $errors[] = "Invalid student ID: $studentId";
                continue;
            }
            
            // Use prepared statement for security
            $stmt = $connection->prepare("SELECT * FROM studentlogin WHERE studentId = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result && $result->num_rows > 0)
            {
                $row = $result->fetch_array(MYSQLI_NUM);
                $passportName = $row[10];
                $passportPath = "studentPassports/$passportName";
                
                // Delete related data first (to avoid foreign key constraints)
                // Check if studentloginlog table exists and delete from it
                $tableCheck = $connection->query("SHOW TABLES LIKE 'studentloginlog'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    $logDeleteStmt = $connection->prepare("DELETE FROM studentloginlog WHERE studentId = ?");
                    if ($logDeleteStmt) {
                        $logDeleteStmt->bind_param("i", $studentId);
                        $logDeleteStmt->execute();
                        $logDeleteStmt->close();
                    }
                }
                $tableCheck->close();
                
                // Check if testanswers table exists and delete from it
                $tableCheck = $connection->query("SHOW TABLES LIKE 'testanswers'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    $testDeleteStmt = $connection->prepare("DELETE FROM testanswers WHERE userLoginId = ?");
                    if ($testDeleteStmt) {
                        $testDeleteStmt->bind_param("i", $studentId);
                        $testDeleteStmt->execute();
                        $testDeleteStmt->close();
                    }
                }
                $tableCheck->close();
                
                // Delete the student from the studentLogin table using prepared statement
                $deleteStmt = $connection->prepare("DELETE FROM studentlogin WHERE studentId = ?");
                $deleteStmt->bind_param("i", $studentId);
                $deleteResult = $deleteStmt->execute();
                
                if ($deleteResult)
                {
                    $deletedCount++;
                    
                    // If passport exists, try to delete it
                    if($passportName != "" && file_exists($passportPath))
                    {
                        if (!unlink($passportPath)) {
                            // Log error but don't fail the deletion
                            error_log("Failed to delete passport file: $passportPath");
                        }
                    }
                }
                else
                {
                    $failedCount++;
                    $errors[] = "Failed to delete student with ID: $studentId";
                }
                $deleteStmt->close();
            }
            else
            {
                $failedCount++;
                $errors[] = "Student not found with ID: $studentId";
            }
            $stmt->close();
        }
        
        // Log the deletion attempt for audit purposes
        $logMessage = "Admin deleted $deletedCount students. Failed: $failedCount. Student IDs: " . implode(',', $studentIds);
        error_log($logMessage);
        
        if($deletedCount > 0 && $failedCount == 0)
        {
            echo 1; // All students deleted successfully
        }
        else if($deletedCount > 0 && $failedCount > 0)
        {
            echo 2; // Some students deleted, some failed
        }
        else
        {
            echo 3; // No students were deleted
        }
    }
    else
    {
        echo 4; // No students selected or empty input (distinct from session expired)
    }
}
?>
