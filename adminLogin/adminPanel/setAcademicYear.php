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
	
		if(isset($_POST['academicYearId']))
		{    
			$academicYearId = mysqli_real_escape_string($connection, $_POST['academicYearId']);
			$academicYearName = mysqli_real_escape_string($connection, $_POST['academicYearName']);
			
			$idToSet = 1;
			$query = "UPDATE set_academic_year SET setAcademicYearId='$academicYearId', setAcademicYearName='$academicYearName' WHERE id='$idToSet'";
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
	}
?>