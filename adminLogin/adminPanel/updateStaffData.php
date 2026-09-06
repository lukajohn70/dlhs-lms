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
	
		if(isset($_POST['staffIdToUpdate']))
		{    
			$staffIdToUpdate = mysqli_real_escape_string($connection, $_POST['staffIdToUpdate']);
			$surname = mysqli_real_escape_string($connection, $_POST['surname']);
			$firstName = mysqli_real_escape_string($connection, $_POST['firstName']);
			$middleName = mysqli_real_escape_string($connection, $_POST['middleName']);
			$gender = mysqli_real_escape_string($connection, $_POST['gender']);
			$email = mysqli_real_escape_string($connection, $_POST['staffEmail']);
			$password = mysqli_real_escape_string($connection, $_POST['thePassword']);
									
			$query = "SELECT * FROM stafflogin WHERE username='$email' AND staffId !='$staffIdToUpdate'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE stafflogin SET surname='$surname', firstName='$firstName', middleName='$middleName', username='$email', password='$password', gender='$gender' WHERE staffId ='$staffIdToUpdate'";
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