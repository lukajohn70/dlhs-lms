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
	
		if(isset($_POST['resultTermId']))
		{    
			$resultTermId = mysqli_real_escape_string($connection, $_POST['resultTermId']);
			$resultTermName = mysqli_real_escape_string($connection, $_POST['resultTermName']);
			
			$idToSet = 1;
			$query = "UPDATE result_set_current_term SET setCurrentTermId='$resultTermId', setCurrentTermName='$resultTermName' WHERE id='$idToSet'";
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