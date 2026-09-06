<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['classId']))
		{    
			$classId = mysqli_real_escape_string($connection, $_POST['classId']);
			$checkStudents = $_POST['checkedStudents'];
			
			$str_arr = explode (",", $checkStudents); 
			
			//Getting the yeargroup id of the selected class
			$query = "SELECT * FROM classes WHERE classId='$classId'";
			$result = $connection->query($query);
			$row = $result->fetch_array(MYSQLI_NUM);
			$yearGroupId = $row[1];
			
			foreach($str_arr as $chk1)  
			{  
				//Updating each selected student with the new class id
				$query1 = "UPDATE studentlogin SET classId='$classId', yearGroupId='$yearGroupId' WHERE studentId='$chk1'";
				$result1 = $connection->query($query1);
			}
			echo 1;
		}
	}
?>