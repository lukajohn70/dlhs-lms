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
	
		//post for adding psychomotor
		if(isset($_POST['addPsychomotor']))
		{    
			$pyschomotorSkill = mysqli_real_escape_string($connection, $_POST['pyschomotorSkill']);
						
			$query = "SELECT * FROM psychomotor_skills WHERE psychomotorSkillName='$pyschomotorSkill'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO psychomotor_skills(psychomotorSkillName) VALUES('{$pyschomotorSkill}')";
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
		
		//post for deleting psychomotor
		if(isset($_POST['deletePsychomotor']))
		{    
			$psychomotorSkillId = mysqli_real_escape_string($connection, $_POST['psychomotorSkillId']);
						
			$query = "DELETE FROM psychomotor_skills WHERE psychomotorSkillId='$psychomotorSkillId'";
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
		
		//Edit psychomotor skill
		if(isset($_POST['editPsychomotor']))
		{    
			$pyschomotorSkillId = mysqli_real_escape_string($connection, $_POST['pyschomotorSkillId']);
			$pyschomotorSkill = mysqli_real_escape_string($connection, $_POST['pyschomotorSkill']);
						
			$query = "SELECT * FROM psychomotor_skills WHERE psychomotorSkillName='$pyschomotorSkill'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "UPDATE psychomotor_skills SET psychomotorSkillName='$pyschomotorSkill' WHERE psychomotorSkillId='$pyschomotorSkillId'";
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