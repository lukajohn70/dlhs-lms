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
	
		if(isset($_POST['existingAssignmentId']))
		{    
			$existingAssignmentId = mysqli_real_escape_string($connection, $_POST['existingAssignmentId']);
			$staffId = mysqli_real_escape_string($connection, $_POST['staffId']);
						
			$query1 = "UPDATE form_teacher_assignment SET teacherId='$staffId' WHERE formTeacherAssignmentId='$existingAssignmentId'";
			$result1 = $connection->query($query1);
			
			if ($result1)
			{	
				echo 1;
			}
			else
			{
				echo 2;	
			}
		}
		else
		{
			echo 0;
		}
	}
?>