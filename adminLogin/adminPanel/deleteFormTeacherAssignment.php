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
		if(isset($_POST['formTeacherAssignmentId']))
		{   
			$formTeacherAssignmentId = mysqli_real_escape_string($connection, $_POST['formTeacherAssignmentId']);
			
			$query = "DELETE FROM form_teacher_assignment WHERE formTeacherAssignmentId='$formTeacherAssignmentId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if ($result)
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
			header('location:logout.php');
		}
	}
	
?>