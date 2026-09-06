<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
	
	
    if(isset($_POST["selectedTestId"]))
	{
		// Capture selected year group
		$selectedTestId = $_POST["selectedTestId"];
		$selectedClassId = $_POST["selectedClassId"];
		
		//Getting the yeargroupId of the selected test
		$getYearGroupId="SELECT * FROM tests WHERE testId='$selectedTestId'";
		$result1 = $connection->query($getYearGroupId);
		$row1 = $result1->fetch_array(MYSQLI_NUM);
		$yearGroupId = $row1[9];
		
		$return_arr = array();
        $students="SELECT * FROM studentlogin WHERE yearGroupId='$yearGroupId' AND classId='$selectedClassId'";
		$result = $connection->query( $students);
		if (($result->num_rows)>0)
		{	
			while($row = $result->fetch_array(MYSQLI_NUM))
			{	
				$studentId=$row[0];
				$surname=$row[1];
				$firstName=$row[2];
				$middleName=$row[3];
				$return_arr[] = array("studentId" => $studentId,
										"surname" => $surname,
										"firstName" => $firstName,
										"middleName" => $middleName);
			}
			echo json_encode($return_arr);
		}
		else
		{
			echo json_encode($return_arr);
		}
    }
?>

