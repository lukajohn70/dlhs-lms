<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM sports ORDER BY sportName ASC";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$sportId  = $row[0];
			$sportName = $row[1];
									
			$return_arr[] = array("sportId" => $sportId,
							"sportName" => $sportName);
		}
		echo json_encode($return_arr);
?>
