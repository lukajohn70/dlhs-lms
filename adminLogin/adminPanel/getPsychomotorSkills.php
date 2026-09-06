<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="SELECT * FROM psychomotor_skills ORDER BY psychomotorSkillName ASC";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$psychomotorSkillId  = $row[0];
			$psychomotorSkillName = $row[1];
									
			$return_arr[] = array("psychomotorSkillId" => $psychomotorSkillId,
							"psychomotorSkillName" => $psychomotorSkillName);
		}
		echo json_encode($return_arr);
?>
