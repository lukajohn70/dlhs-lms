<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM result_grading";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$gradeId  = $row[0];
			$gradeRange = $row[1];
			$gradeAlphabet = $row[2];
			$gradeRemark = $row[3];
			$gradeSectionId = $row[4];
			
			//Getting the grade section name from the sections table
			$query1="SELECT * FROM sections WHERE sectionId='$gradeSectionId'";
			$result1 = $connection->query($query1);
			$row1 = $result1->fetch_array(MYSQLI_NUM);
			$gradeSectionName = $row1[1];
					
			$return_arr[] = array("gradeId" => $gradeId,
							"gradeRange" => $gradeRange,
							"gradeAlphabet" => $gradeAlphabet,
							"gradeRemark" => $gradeRemark,
							"gradeSectionId" => $gradeSectionId,
							"gradeSectionName" => $gradeSectionName);
		}
		echo json_encode($return_arr);
?>
