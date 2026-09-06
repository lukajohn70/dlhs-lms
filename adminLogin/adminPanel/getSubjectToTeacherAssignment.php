<?php
session_start();
	error_reporting(0);
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{	
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['classId']))
		{    
			$classId = mysqli_real_escape_string($connection, $_POST['classId']);
			$return_arr = array();
			$query="select * from subject_teacher_assignment WHERE classId='$classId'";
			$result = $connection->query($query);
			
			if(($result->num_rows)>0)
			{
				while($row = $result->fetch_array(MYSQLI_NUM))
				{
					$subjectId  = $row[2];
					$teacherId = $row[3];
							
					//Getting the subject name
					$getSubjectName="select * from subjects WHERE subjectId='$subjectId'";
					$result1 = $connection->query($getSubjectName);
					$row1 = $result1->fetch_array(MYSQLI_NUM);
					
					//Getting the teacher name
					$getTeacherName="select * from stafflogin WHERE staffId='$teacherId'";
					$result2 = $connection->query($getTeacherName);
					$row2 = $result2->fetch_array(MYSQLI_NUM);
					
					$return_arr[] = array("subjectName" => $row1[1],
									"teacherName" => $row2[1]." ".$row2[2]." ".$row2[3]);
				}
			}
			if(count($return_arr) > 0)
			{
				echo json_encode($return_arr);
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
