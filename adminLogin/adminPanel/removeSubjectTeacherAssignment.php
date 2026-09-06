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

    if(isset($_POST['assignmentId']))
    {    
        $assignmentId = mysqli_real_escape_string($connection, $_POST['assignmentId']);
        
        $query = "DELETE FROM subject_teacher_assignment WHERE subjectTeacherAssignmentId='$assignmentId'";
        $result = $connection->query($query);
        
        if ($result)
        {
            echo 1; // Success
        }
        else
        {
            echo 2; // Failed
        }
    }
    else
    {
        echo 0; // Invalid request
    }
}
?>
