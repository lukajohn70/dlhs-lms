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
	
		if(isset($_POST['classId']))
		{    
			$classId = mysqli_real_escape_string($connection, $_POST['classId']);
			$subjectId = mysqli_real_escape_string($connection, $_POST['subjectId']);
			$teacherId = mysqli_real_escape_string($connection, $_POST['teacherId']);
			
			//Checking if this subject has been assigned to a teacher before 			
			$query = "SELECT * FROM subject_teacher_assignment WHERE classId='$classId' AND subjectId='$subjectId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				$row = $result->fetch_array(MYSQLI_NUM);
				$teacherIdFromTable = $row[3];
				if($teacherId == $teacherIdFromTable)
				{
					echo 3;	//This subject has already been assigned to this teacher
				}
				else
				{
					//Updating the subjects teacher if a new teacher is been assigned to the subject for the selected class
					$query01 = "UPDATE subject_teacher_assignment SET teacherId='$teacherId' WHERE classId='$classId' AND subjectId='$subjectId'";
					$result01 = $connection->query($query01);
					if($result01)
					{
						echo 4;	//If update is successful
					}
					else
					{
						echo 5; //If update is unsuccessful
					}
				}
				
			}
			else
			{		
				$query1 = "INSERT INTO subject_teacher_assignment(classId, subjectId, teacherId) VALUES('{$classId}', '{$subjectId}', '{$teacherId}')";
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