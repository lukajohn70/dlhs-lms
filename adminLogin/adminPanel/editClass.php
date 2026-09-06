<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{	
		//require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['classId']))
		{    
			$classId = mysqli_real_escape_string($connection, $_POST['classId']);
			$className = mysqli_real_escape_string($connection, $_POST['className']);
			$newYearGroupId = mysqli_real_escape_string($connection, $_POST['newYearGroupId']);
						
			$query = "SELECT * FROM classes WHERE className='$className' AND classYearGroup='$newYearGroupId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE classes SET classYearGroup='$newYearGroupId', className='$className' WHERE classId='$classId'";
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
		}
		else
		{
			echo 0;
		}
	}
?>