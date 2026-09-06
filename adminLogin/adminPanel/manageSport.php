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
	
		//post for adding a sport
		if(isset($_POST['addSport']))
		{    
			$sportName = mysqli_real_escape_string($connection, $_POST['sportName']);
						
			$query = "SELECT * FROM sports WHERE sportName='$sportName'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO sports(sportName) VALUES('{$sportName}')";
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
		
		//post for deleting a sport
		if(isset($_POST['deleteSport']))
		{    
			$sportId = mysqli_real_escape_string($connection, $_POST['sportId']);
						
			$query = "DELETE FROM sports WHERE sportId='$sportId'";
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
		
		//Edit a sport
		if(isset($_POST['editSport']))
		{    
			$sportId = mysqli_real_escape_string($connection, $_POST['sportId']);
			$sportName = mysqli_real_escape_string($connection, $_POST['sportName']);
						
			$query = "SELECT * FROM sports WHERE sportName='$sportName' AND sportId !='$sportId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE sports SET sportName='$sportName' WHERE sportId='$sportId'";
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