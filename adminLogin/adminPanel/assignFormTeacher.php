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
			$classId = mysqli_real_escape_string($connection, $_POST['classId']);
			$staffId = mysqli_real_escape_string($connection, $_POST['staffId']);
						
			$query = "SELECT * FROM form_teacher_assignment WHERE classId='$classId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;	//If the selected class already has a form teacher assigned to it.
			}
			else
			{		
				//Getting the current result year
				$idToSelect = 1;
				$getCurrentResultYear = "SELECT * FROM set_result_year WHERE id='$idToSelect'";
				$result1 = $connection->query($getCurrentResultYear);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$currentResultYearId = $row1[1];
										
				$query2 = "INSERT INTO form_teacher_assignment(teacherId, yearGroupId, classId, resultYearId) VALUES('{$staffId}', '{$yearGroupId}', '{$classId}', '{$currentResultYearId}')";
				$result2 = $connection->query($query2);
				
				if ($result2)
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