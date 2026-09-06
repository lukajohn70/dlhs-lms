<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$idToSelectFrom = 1;
		$query="SELECT * FROM set_result_year WHERE id='$idToSelectFrom'";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$resultYearId  = $row[1];
			$resultYearName = $row[2];
									
			$return_arr[] = array("resultYearId" => $resultYearId,
							"resultYearName" => $resultYearName);
		}
		echo json_encode($return_arr);
?>
