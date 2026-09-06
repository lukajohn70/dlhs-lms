<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';

// Set content type to JSON
header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn']))
{	
    echo json_encode(array('error' => 'not_logged_in'));
}
else
{
    include "../../db_connection/dlhs_db_connection.php";
    
    if(isset($_POST['yearGroupId']))
    {    
        $yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
        
        $return_arr = array();
        $query = "SELECT classId, className FROM classes WHERE classYearGroup = '$yearGroupId' ORDER BY className";
        $result = $connection->query($query);
        
        if($result && $result->num_rows > 0)
        {
            while($row = $result->fetch_array(MYSQLI_NUM))
            {
                $return_arr[] = array(
                    "classId" => $row[0],
                    "className" => $row[1]
                );
            }
        }
        
        echo json_encode($return_arr);
    }
    else
    {
        echo json_encode(array('error' => 'no_year_group_id'));
    }
}
?>