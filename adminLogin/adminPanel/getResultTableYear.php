<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		
		$query="SELECT * FROM results_table_names";
		$result = $connection->query($query);
		if(($result->num_rows) > 0)
		{
			while($row = $result->fetch_array(MYSQLI_NUM))
			{
				$resultTableNameId  = $row[0];
				$resultYearId  = $row[1];
				$term1LockStatus  = $row[5];
				$term2LockStatus  = $row[6];
				$term3LockStatus  = $row[7];
				
				//Getting the year name
				$query1="SELECT * FROM academic_year WHERE academicYearId='$resultYearId'";
				$result1 = $connection->query($query1);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$resultYearName = $row1[1];
										
				$return_arr[] = array("resultTableNameId" => $resultTableNameId,
								"resultYearName" => $resultYearName,
								"term1LockStatus" => $term1LockStatus,
								"term2LockStatus" => $term2LockStatus,
								"term3LockStatus" => $term3LockStatus);
			}
			echo json_encode($return_arr);
		}
		else
		{
			echo json_encode($return_arr);
		}
?>
