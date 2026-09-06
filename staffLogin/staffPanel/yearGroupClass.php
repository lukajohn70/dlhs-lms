<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
		
	if(isset($_POST['selectedTestId']))
	{    
		// Capture selected year group
		$selectedTestId = $_POST["selectedTestId"];
		$staffId=$_SESSION['staffId'];
		
		//Getting the subject and yeargroupId of the selected test
		$getTestInfo="SELECT subject, yearGroup FROM tests WHERE testId='$selectedTestId'";
		$result = $connection->query($getTestInfo);
		$row = $result->fetch_assoc();
		$subjectId = $row['subject'];
		$yearGroupId = $row['yearGroup'];
		
		$return_arr = array();
		
		// If staff is logged in, only show assigned classes (arms)
		if (isset($_SESSION['staffLoggedIn'])) {
			$getClass = "SELECT c.* FROM classes c 
						 INNER JOIN subject_teacher_assignment sta ON c.classId = sta.classId 
						 WHERE c.classYearGroup = '$yearGroupId' 
						 AND sta.subjectId = '$subjectId' 
						 AND sta.teacherId = '$staffId'";
		} else {
			// Admin sees all classes for the year group
			$getClass = "SELECT * FROM classes WHERE classYearGroup='$yearGroupId'";
		}
		
		$result1 = $connection->query($getClass);
		if ($result1 && ($result1->num_rows)>0)
		{
			while($row1 = $result1->fetch_assoc())
			{	
				$classId = $row1['classId'];
				$className = $row1['className'];
				$return_arr[] = array("classId" => $classId,
										"className" => $className);
			}
		}
		echo json_encode($return_arr);
	}
?>

