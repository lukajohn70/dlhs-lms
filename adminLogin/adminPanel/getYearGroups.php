<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM yeargroup";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$yearGroupId  = $row[0];
			$yearGroupName = $row[1];
			$sectionId = $row[2];
			
			$getSectionName="SELECT * FROM sections WHERE sectionId='$sectionId'";
			$result1 = $connection->query($getSectionName);
			$row1 = $result1->fetch_array(MYSQLI_NUM);
			$sectionName = $row1[1];			
			
			$return_arr[] = array("yearGroupId" => $yearGroupId,
							"yearGroupName" => $yearGroupName,
							"sectionId" => $sectionId,
							"sectionName" => $sectionName);
		}
		echo json_encode($return_arr);
?>
