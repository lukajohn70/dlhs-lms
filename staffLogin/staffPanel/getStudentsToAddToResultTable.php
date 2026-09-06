<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
	$staffId = $_SESSION['staffId'];
	$classId = mysqli_real_escape_string($connection, $_POST['classId']);
	$yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroupId']);
	
	$return_arr = array();
	
	$idToSelect = 1;
	//Getting the current result year
	$getCurrentResultYear = "SELECT * FROM set_result_year WHERE id='$idToSelect'";
	$result4 = $connection->query($getCurrentResultYear);
	$row4 = $result4->fetch_array(MYSQLI_NUM);
	$currentResultYearId = $row4[1];
	
	//Getting the current result term
	$getCurrentResultTerm = "SELECT * FROM result_set_current_term WHERE id='$idToSelect'";
	$result15 = $connection->query($getCurrentResultTerm);
	$row5 = $result15->fetch_array(MYSQLI_NUM);
	$currentResultTermId = $row5[1];
	
	//Getting the resultTable name created for current result year
	$getResultTableCreated = "SELECT * FROM results_table_names WHERE yearId='$currentResultYearId'";
	$result6 = $connection->query($getResultTableCreated);
	$row6 = $result6->fetch_array(MYSQLI_NUM);
	$currentYearResultTableName = $row6[2];
			
	$query="SELECT * FROM studentlogin WHERE classId='$classId' AND yearGroupId='$yearGroupId'";
	$result = $connection->query($query);
	$row = $result->fetch_array(MYSQLI_NUM);
	
	if(($result->num_rows) > 0)
	{
		//Checking if this staff has added this student to the current year and term's result table
		$checkForStudentInResultTable = "SELECT * FROM $currentYearResultTableName WHERE formTeacherId='$staffId' AND studentClassId='$classId' AND academicYearId='$currentResultYearId' AND termId='$currentResultTermId'";
		$result8 = $connection->query($checkForStudentInResultTable);
		if(($result8->num_rows) > 0)
		{
			$statusToSetAddOrUpdateText = 1;
			$return_arr[] = array("statusToSetAddOrUpdateText" => $statusToSetAddOrUpdateText);
		}
		else
		{
			$statusToSetAddOrUpdateText = 0;
			$return_arr[] = array("statusToSetAddOrUpdateText" => $statusToSetAddOrUpdateText);
		}
			
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$studentId = $row[0];
			$surname = $row[1];
			$firstName= $row[2];
			$middleName= $row[3];
			$gender=$row[4];
			$admissionNumber=$row[5];
			$classId=$row[9];
			$studentPassport=$row[10];
			$houseId=$row[13];
			$sportId=$row[14];
			
			//Getting the house name
			$getHouseName = "SELECT * FROM houses WHERE houseId='$houseId'";
			$result2 = $connection->query($getHouseName);
			$row2 = $result2->fetch_array(MYSQLI_NUM);
			$houseName = $row2[1];
			
			//Getting the sport name
			$getSportName = "SELECT * FROM sports WHERE sportId='$sportId'";
			$result3 = $connection->query($getSportName);
			$row3 = $result3->fetch_array(MYSQLI_NUM);
			$sportName = $row3[1];
			
			$statusForCheck  = 0;
			//Checking if this staff has added this student to the current year and term's result table
			$checkForStudentInResultTable1 = "SELECT * FROM $currentYearResultTableName WHERE formTeacherId='$staffId' AND studentClassId='$classId' AND academicYearId='$currentResultYearId' AND termId='$currentResultTermId' AND studentId='$studentId'";
			$result7 = $connection->query($checkForStudentInResultTable1);
			if(($result7->num_rows) > 0)
			{
				$statusForCheck = 1;	
			}
	
			$return_arr[] = array("studentId" => $studentId,
							"surname" => $surname,
							"firstName" => $firstName,
							"middleName" => $middleName,
							"gender" => $gender,
							"admissionNumber" => $admissionNumber,
							"studentPassport" => $studentPassport,
							"houseName" => $houseName,
							"sportName" => $sportName,
							"statusForCheck" => $statusForCheck);
		}
		echo json_encode($return_arr);
	}
	else
	{
		echo json_encode($return_arr);
	}
	
	
?>
