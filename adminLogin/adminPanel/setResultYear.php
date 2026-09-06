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
	
		if(isset($_POST['resultYearId']))
		{    
			$resultYearId = mysqli_real_escape_string($connection, $_POST['resultYearId']);
			$resultYearName = mysqli_real_escape_string($connection, $_POST['resultYearName']);
			
			$idToSet = 1;
			$query = "UPDATE set_result_year SET setResultYearId='$resultYearId', setResultYearName='$resultYearName' WHERE id='$idToSet'";
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
	}
?>