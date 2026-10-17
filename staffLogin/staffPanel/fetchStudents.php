<?php
session_start();
	$staffId = isset($_SESSION['staffId']) ? $_SESSION['staffId'] : 0;
	session_write_close();

	include "../../db_connection/dlhs_db_connection.php";
	
    if(isset($_POST["selectedTestId"]))
	{
		// Capture selected year group
		$selectedTestId = mysqli_real_escape_string($connection, $_POST["selectedTestId"]);
		$selectedClassId = mysqli_real_escape_string($connection, $_POST["selectedClassId"]);
		
		//Getting the yeargroupId of the selected test
		$getYearGroupId="SELECT yearGroup FROM tests WHERE testId='$selectedTestId' AND staffId='$staffId' LIMIT 1";
		$result1 = $connection->query($getYearGroupId);
		$row1 = $result1 ? $result1->fetch_assoc() : null;
		$yearGroupId = $row1 ? $row1['yearGroup'] : 0;
		
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

