<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn']))
{	
    echo 0;
}
else
{
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Log to response but don't break JSON
    
    include "../../db_connection/dlhs_db_connection.php";
    
    $return_arr = array();
    $query = "SELECT sta.subjectTeacherAssignmentId as assignmentId, yg.yearGroupName, c.className, s.subjectName, 
                     CONCAT(UPPER(IFNULL(sl.surname, '')), ' ', IFNULL(sl.firstName, ''), ' ', IFNULL(sl.middleName, '')) as teacherName
              FROM subject_teacher_assignment sta
              LEFT JOIN classes c ON sta.classId = c.classId
              LEFT JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
              LEFT JOIN subjects s ON sta.subjectId = s.subjectId
              LEFT JOIN stafflogin sl ON sta.teacherId = sl.staffId
              ORDER BY yg.yearGroupName, c.className, s.subjectName";
    
    $result = $connection->query($query);
    
    if($result && $result->num_rows > 0)
    {
        while($row = $result->fetch_array(MYSQLI_NUM))
        {
            $return_arr[] = array(
                "assignmentId" => $row[0],
                "yearGroupName" => $row[1],
                "className" => $row[2],
                "subjectName" => $row[3],
                "teacherName" => $row[4]
            );
        }
    }
    
    echo json_encode($return_arr);
}
?>
