<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	
    if(isset($_POST["selectedTestId"]))
	{
		// Capture selected year group
		$selectedTestId = $_POST["selectedTestId"];
		$selectedClassId = $_POST["selectedClassId"];
		$staffId=$_SESSION['staffId'];
		
		//Getting the yeargroupId of the selected test
		$getYearGroupId="SELECT * FROM tests WHERE testId='$selectedTestId' AND staffId='$staffId'";
		$result1 = $connection->query($getYearGroupId);
		$row1 = $result1->fetch_assoc();
		$yearGroupId = $row1['yearGroup'];
		
		$return_arr = array();
        $students="SELECT * FROM studentlogin WHERE yearGroupId='$yearGroupId' AND classId='$selectedClassId'";
		$result = $connection->query( $students);
		if (($result->num_rows)>0)
		{	
			while($row = $result->fetch_assoc())
			{	
				$studentId  = $row['studentId'];
				$surname    = $row['surname'];
				$firstName  = $row['firstName'];
				$middleName = $row['middleName'];
				$return_arr[] = array("studentId"  => $studentId,
										"surname"   => $surname,
										"firstName" => $firstName,
										"middleName"=> $middleName);
			}
			echo json_encode($return_arr);
		}
		else
		{
			echo json_encode($return_arr);
		}
    }
?>

