<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn']))
{	
    require_once 'unsetSessions.php';
    echo 0;
}
else
{
    include "../../db_connection/dlhs_db_connection.php";

    if(isset($_POST['selectedArms']))
    {    
        $selectedArms = $_POST['selectedArms'];
        $subjectId = mysqli_real_escape_string($connection, $_POST['subjectId']);
        $teacherId = mysqli_real_escape_string($connection, $_POST['teacherId']);
        
        // Split the comma-separated class IDs
        $classIds = explode(',', $selectedArms);
        $successCount = 0;
        $updateCount = 0;
        $errorCount = 0;
        
        foreach($classIds as $classId)
        {
            $classId = trim($classId);
            
            // Check if this subject is already assigned to a teacher for this class
            $query = "SELECT * FROM subject_teacher_assignment WHERE classId='$classId' AND subjectId='$subjectId'";
            $result = $connection->query($query);
            
            if($result && $result->num_rows > 0)
            {
                $row = $result->fetch_array(MYSQLI_NUM);
                $existingTeacherId = $row[3];
                
                if($teacherId == $existingTeacherId)
                {
                    // Already assigned to same teacher, skip
                    continue;
                }
                else
                {
                    // Update the assignment
                    $query01 = "UPDATE subject_teacher_assignment SET teacherId='$teacherId' WHERE classId='$classId' AND subjectId='$subjectId'";
                    $result01 = $connection->query($query01);
                    
                    if($result01)
                    {
                        $updateCount++;
                    }
                    else
                    {
                        $errorCount++;
                    }
                }
            }
            else
            {
                // Insert new assignment
                $query1 = "INSERT INTO subject_teacher_assignment(classId, subjectId, teacherId) VALUES('{$classId}', '{$subjectId}', '{$teacherId}')";
                $result1 = $connection->query($query1);
                
                if ($result1)
                {
                    $successCount++;
                }
                else
                {
                    $errorCount++;
                }
            }
        }
        
        // Return appropriate response based on results
        if($errorCount == 0 && ($successCount > 0 || $updateCount > 0))
        {
            if($successCount > 0 && $updateCount > 0)
            {
                echo 4; // Mixed success (some new, some updated)
            }
            else if($successCount > 0)
            {
                echo 1; // All new assignments successful
            }
            else if($updateCount > 0)
            {
                echo 4; // All updates successful
            }
        }
        else if($errorCount > 0 && ($successCount > 0 || $updateCount > 0))
        {
            echo 3; // Partial success (some failed)
        }
        else
        {
            echo 2; // All failed
        }
    }
    else
    {
        echo 5; // Invalid request (missing selectedArms)
    }
}
?>
