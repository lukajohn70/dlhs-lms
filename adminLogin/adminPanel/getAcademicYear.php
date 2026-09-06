<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$idToSelectFrom = 1;
		$query="SELECT * FROM set_academic_year WHERE id='$idToSelectFrom'";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$academicYearId  = $row[1];
			$academicYearName = $row[2];
									
			$return_arr[] = array("academicYearId" => $academicYearId,
							"academicYearName" => $academicYearName);
		}
		echo json_encode($return_arr);
?>
