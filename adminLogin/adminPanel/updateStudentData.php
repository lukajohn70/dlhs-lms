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
	
		if(isset($_POST['studentIdToUpdate']))
		{    
			$studentIdToUpdate = mysqli_real_escape_string($connection, $_POST['studentIdToUpdate']);
			$surname = mysqli_real_escape_string($connection, $_POST['surname']);
			$firstName = mysqli_real_escape_string($connection, $_POST['firstName']);
			$middleName = mysqli_real_escape_string($connection, $_POST['middleName']);
			$gender = mysqli_real_escape_string($connection, $_POST['gender']);
			$studentAdmissionNo = mysqli_real_escape_string($connection, $_POST['studentAdmissionNo']);
			$email = mysqli_real_escape_string($connection, $_POST['studentEmail']);
			$password = mysqli_real_escape_string($connection, $_POST['thePassword']);
			$studentYearGroupId = mysqli_real_escape_string($connection, $_POST['studentYearGroupId']);
			$studentClassId = mysqli_real_escape_string($connection, $_POST['studentClassId']);
									
			$query = "SELECT * FROM studentlogin WHERE studentEmail='$email' AND studentId !='$studentIdToUpdate'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE studentlogin SET surname='$surname', firstName='$firstName', middleName='$middleName', gender='$gender', admissionNumber='$studentAdmissionNo', studentEmail='$email', password='$password', yearGroupId='$studentYearGroupId', classId='$studentClassId' WHERE studentId ='$studentIdToUpdate'";
				$result1 = $connection->query($query1);
				
				if($result1)
				{	
					echo 1;
				}
				else
				{
					echo 2;
				}
			}
		}
		else
		{
			echo 0;
		}
	}
?>