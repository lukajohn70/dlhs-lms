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
	
		//post for adding a grade
		if(isset($_POST['addGrade']))
		{    
			$sectionId = mysqli_real_escape_string($connection, $_POST['sectionId']);
			$gradeRange = mysqli_real_escape_string($connection, $_POST['gradeRange']);
			$gradeAlphabet = StrToUpper(mysqli_real_escape_string($connection, $_POST['gradeAlphabet']));
			$gradeRemark = StrToUpper(mysqli_real_escape_string($connection, $_POST['gradeRemark']));
						
			$query = "SELECT * FROM result_grading WHERE gradeSection='$sectionId' AND gradeAlphabet='$gradeAlphabet'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{		
				$query1 = "INSERT INTO result_grading(gradeRange, gradeAlphabet, gradeRemark, gradeSection) VALUES('{$gradeRange}', '{$gradeAlphabet}', '{$gradeRemark}', '{$sectionId}')";
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
		
		//post for deleting a sport
		if(isset($_POST['deleteGrade']))
		{    
			$gradeId = mysqli_real_escape_string($connection, $_POST['gradeId']);
						
			$query = "DELETE FROM result_grading WHERE gradeId='$gradeId'";
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
		
		//Edit a sport
		if(isset($_POST['editGrade']))
		{    
			$gradeId = mysqli_real_escape_string($connection, $_POST['gradeId']);
			$sectionId = mysqli_real_escape_string($connection, $_POST['sectionId']);
			$gradeRange = mysqli_real_escape_string($connection, $_POST['gradeRange']);
			$gradeAlphabet = mysqli_real_escape_string($connection, $_POST['gradeAlphabet']);
			$gradeRemark = mysqli_real_escape_string($connection, $_POST['gradeRemark']);
						
			$query = "SELECT * FROM result_grading WHERE gradeSection='$sectionId' AND gradeAlphabet='$gradeAlphabet' AND gradeId !='$gradeId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;	//If there is a grade beside this one to edit with the same details as the incoming details
			}
			else
			{		
				$query1 = "UPDATE result_grading SET gradeRange='$gradeRange', gradeAlphabet='$gradeAlphabet', gradeRemark='$gradeRemark', gradeSection='$sectionId' WHERE gradeId='$gradeId'";
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