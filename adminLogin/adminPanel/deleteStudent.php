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
		if(isset($_POST['studentId']))
		{   
			$studentId=$_POST['studentId'];
			
			//getting the passport of the student
			$query="SELECT * FROM studentlogin WHERE studentId='$studentId'";
			$result = $connection->query($query);
			$row = $result->fetch_array(MYSQLI_NUM);
			$passportName = $row[10];
			$passportPath = "studentPassports/$passportName";
			if($passportName == "")
			{
				//deleting the student from the studentLogin table
				$query1 = "DELETE FROM studentlogin WHERE studentId='$studentId'";
				$result1 = $connection->query($query1);
				if (!$result1) die($connection->error);
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
				if(unlink($passportPath))
				{
					//deleting the student from the studentLogin table
					$query1 = "DELETE FROM studentlogin WHERE studentId='$studentId'";
					$result1 = $connection->query($query1);
					if (!$result1) die($connection->error);
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
					echo 3;
				}
			}
		}
		else
		{
			echo 0;
		}
	}	
	
?>