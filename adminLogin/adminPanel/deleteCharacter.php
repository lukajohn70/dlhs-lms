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
		if(isset($_POST['characterId']))
		{   
			$characterId = mysqli_real_escape_string($connection, $_POST['characterId']);
		
			//deleting a character
			$query = "DELETE FROM characters WHERE characterId='$characterId'";
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
		else
		{
			echo 0;
		}
	}	
	
?>