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
		if(isset($_POST['testId']))
		{   
			$testId = $_POST['testId'];
			$yearGroupId = $_POST['yearGroupId'];
			$invigilatorId = $_POST['invigilatorId'];
			
			$query1 = "UPDATE tests SET invigilatorId='$invigilatorId' WHERE testId='$testId'";
			$result1 = $connection->query($query1);
			if (!$result1) die($connection->error);
			if($result1)
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