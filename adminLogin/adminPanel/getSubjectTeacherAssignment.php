<?php
session_start();
	error_reporting(0);
	require_once 'userExpiredSession.php';
	
	$return_arr = array();
	if (!isset($_SESSION['adminLoggedIn']))
	{	
		location("header:..index.php");
	}
	else
	{
		include "../../db_connection/astute_db_connection.php";
	
		if(isset($_POST['sectionId']))
		{    
			$sectionId = $_POST['sectionId'];
			$yearGroupId = $_POST['yearGroupId'];
			$classId = $_POST['classId'];
			$return_arr = array();
			$query="SELECT * FROM subject_teacher_assignment WHERE sectionId='$sectionId' AND yearGroupId='$yearGroupId' AND classId='$classId'";
			$result = $connection->query($query);
			
			if(($result->num_rows)>0)
			{
				while($row = $result->fetch_array(MYSQLI_NUM))
				{
					$subjectId  = $row[4];
					$teacherId = $row[5];
							
					//Getting the subject name
					$getSubjectName="SELECT * FROM subjects_primary WHERE subjectId='$subjectId'";
					$result1 = $connection->query($getSubjectName);
					$row1 = $result1->fetch_array(MYSQLI_NUM);
					
					//Getting the teacher name
					$getTeacherName="SELECT * FROM stafflogin WHERE staffId='$teacherId'";
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
