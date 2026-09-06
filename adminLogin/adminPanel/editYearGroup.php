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
	
		if(isset($_POST['sectionId']))
		{    
			$sectionId = mysqli_real_escape_string($connection, $_POST['sectionId']);
			$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
			$newYearGroupName = mysqli_real_escape_string($connection, $_POST['newYearGroupName']);
						
			$query = "SELECT * FROM yeargroup WHERE yearGroupName='$newYearGroupName' AND sectionId='$sectionId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE yeargroup SET yearGroupName='$newYearGroupName', sectionId='$sectionId' WHERE yearGroupId='$yearGroupId'";
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