<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM houses ORDER BY houseName ASC";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$houseId  = $row[0];
			$houseName = $row[1];
									
			$return_arr[] = array("houseId" => $houseId,
							"houseName" => $houseName);
		}
		echo json_encode($return_arr);
?>
