<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
error_reporting(0);
		
	if(isset($_POST['selectedTestId']))
	{    
		// Capture selected year group
		$selectedTestId = $_POST["selectedTestId"];
		
		//Getting the yeargroupId of the selected test
		$getYearGroupId="SELECT * FROM tests WHERE testId='$selectedTestId'";
		$result = $connection->query($getYearGroupId);
		$row = $result->fetch_array(MYSQLI_NUM);
		$yearGroupId = $row[9];
		
		$return_arr = array();
		$getClass="SELECT * FROM classes WHERE classYearGroup='$yearGroupId'";
		$result1 = $connection->query($getClass);
		if (($result1->num_rows)>0)
		{
			while($row1 = $result1->fetch_array(MYSQLI_NUM))
			{	
				$classId=$row1[0];
				$className=$row1[2];
				$return_arr[] = array("classId" => $classId,
										"className" => $className);
			}
			echo json_encode($return_arr);
		}
		else
		{
			echo json_encode($return_arr);
		}
	}
?>

