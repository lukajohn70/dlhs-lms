<?php
session_start();
	$staffId = isset($_SESSION['staffId']) ? $_SESSION['staffId'] : 0;
	$isStaffLoggedIn = isset($_SESSION['staffLoggedIn']);
	session_write_close();

	include "../../db_connection/dlhs_db_connection.php";
		
	if(isset($_POST['selectedTestId']))
	{    
		// Capture selected year group
		$selectedTestId = mysqli_real_escape_string($connection, $_POST["selectedTestId"]);
		
		//Getting the subject and yeargroupId of the selected test
		$getTestInfo="SELECT subject, yearGroup FROM tests WHERE testId='$selectedTestId'";
		$result = $connection->query($getTestInfo);
		$row = $result->fetch_assoc();
		$subjectId = $row['subject'];
		$yearGroupId = $row['yearGroup'];
		
		$return_arr = array();
		
		// If staff is logged in, only show assigned classes (arms)
		if ($isStaffLoggedIn) {
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
			$rows = array();
			while($row1 = $result1->fetch_assoc())
			{	
				$rows[] = array("classId" => $row1['classId'],
								"className" => $row1['className']);
			}
			if (count($rows) > 1) {
				$return_arr[] = array("classId" => "all", "className" => "★ ALL ASSIGNED ARMS (" . count($rows) . " arms)");
			}
			foreach ($rows as $r) {
				$return_arr[] = $r;
			}
		}
		echo json_encode($return_arr);
	}
?>

