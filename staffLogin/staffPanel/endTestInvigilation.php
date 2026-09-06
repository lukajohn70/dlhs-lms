<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		echo 0;
	}
	else
	{
	
		if(isset($_POST['testId']))
		{   
			$testId = $_POST['testId'];
			$setStatusTo2 = 2;
			$query = "UPDATE tests SET status='$setStatusTo2' WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if($result)
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