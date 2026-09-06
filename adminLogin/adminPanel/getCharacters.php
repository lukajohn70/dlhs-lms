<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM characters ORDER BY characterDescription ASC";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$characterId  = $row[0];
			$characterDescription = $row[1];
									
			$return_arr[] = array("characterId" => $characterId,
							"characterDescription" => $characterDescription);
		}
		echo json_encode($return_arr);
?>
