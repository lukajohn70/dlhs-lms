<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$idToSelectFrom = 1;
		$query="SELECT * FROM result_set_current_term WHERE id='$idToSelectFrom'";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$resultTermId  = $row[1];
			$resultTermName = $row[2];
									
			$return_arr[] = array("resultTermId" => $resultTermId,
							"resultTermName" => $resultTermName);
		}
		echo json_encode($return_arr);
?>
