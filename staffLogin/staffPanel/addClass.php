<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['userLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/zamani_db_connection.php";
	
		//post for change of password
		if(isset($_POST['classname']))
		{    
			$classname = mysqli_real_escape_string($connection, $_POST['classname']);
			$yeargroup = mysqli_real_escape_string($connection, $_POST['yeargroup']);
			$section = mysqli_real_escape_string($connection, $_POST['section']);
						
			$query = "SELECT * FROM classes WHERE className='$classname'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO classes(classSection, classYearGroup, className) VALUES('{$section}', '{$yeargroup}', '{$classname}')";
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
	}
?>