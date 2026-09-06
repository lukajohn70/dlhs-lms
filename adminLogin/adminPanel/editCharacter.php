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
	
		if(isset($_POST['characterId']))
		{    
			$characterId = mysqli_real_escape_string($connection, $_POST['characterId']);
			$characterName = mysqli_real_escape_string($connection, $_POST['characterName']);
						
			$query = "SELECT * FROM characters WHERE characterDescription='$characterName'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE characters SET characterDescription='$characterName' WHERE characterId='$characterId'";
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