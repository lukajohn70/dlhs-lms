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
	
		if(isset($_POST['section']))
		{    
			$sectionId = mysqli_real_escape_string($connection, $_POST['section']);
			$yearGroup = mysqli_real_escape_string($connection, $_POST['yearGroup']);
						
			$query = "SELECT * FROM yeargroup WHERE yearGroupName='$yearGroup' AND sectionId='$sectionId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO yeargroup(yearGroupName, sectionId) VALUES('{$yearGroup}', '{$sectionId}')";
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