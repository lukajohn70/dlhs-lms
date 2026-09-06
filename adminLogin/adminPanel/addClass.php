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
	
		if(isset($_POST['classname']))
		{    
			$classname = mysqli_real_escape_string($connection, $_POST['classname']);
			$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
						
			$query = "SELECT * FROM classes WHERE className='$classname' AND classYearGroup='$yearGroupId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO classes(classYearGroup, className) VALUES('{$yearGroupId}', '{$classname}')";
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