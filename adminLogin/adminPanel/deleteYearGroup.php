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
		if(isset($_POST['yearGroupId']))
		{   
			$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
			$query = "DELETE FROM yeargroup WHERE yearGroupId='$yearGroupId'";
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
			header('location:../index.php');
		}
	}
	
?>