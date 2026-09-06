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
	
		//post for adding a house
		if(isset($_POST['addHouse']))
		{    
			$houseName = mysqli_real_escape_string($connection, $_POST['houseName']);
						
			$query = "SELECT * FROM houses WHERE houseName='$houseName'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO houses(houseName) VALUES('{$houseName}')";
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
		
		//post for deleting a house
		if(isset($_POST['deleteAHouse']))
		{    
			$houseId = mysqli_real_escape_string($connection, $_POST['houseId']);
						
			$query = "DELETE FROM houses WHERE houseId='$houseId'";
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
		
		//Edit a house
		if(isset($_POST['editHouse']))
		{    
			$houseId = mysqli_real_escape_string($connection, $_POST['houseId']);
			$houseName = mysqli_real_escape_string($connection, $_POST['houseName']);
						
			$query = "SELECT * FROM houses WHERE houseName='$houseName' AND houseId !='$houseId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;	//If the house name coming in is the same as one already existing but not of the id referenced.
			}
			else
			{		
				$query1 = "UPDATE houses SET houseName='$houseName' WHERE houseId='$houseId'";
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