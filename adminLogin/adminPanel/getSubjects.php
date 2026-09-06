<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM subjects ORDER BY subjectName ASC";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$subjectId  = $row[0];
			$subjectName = $row[1];
									
			$return_arr[] = array("subjectId" => $subjectId,
							"subjectName" => $subjectName);
		}
		echo json_encode($return_arr);
?>
