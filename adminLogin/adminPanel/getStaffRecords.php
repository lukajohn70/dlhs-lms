<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="select * from stafflogin";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$staffId  = $row[0];
			$surname = $row[1];
			$firstName = $row[2];
			$middleName = $row[3];
			$gender = $row[6];
			$staffEmail = $row[4];
			$staffPassword = $row[5];
			$staffPassport = $row[8];
						
			$return_arr[] = array("staffId" => $staffId,
							"surname" => $surname,
							"firstName" => $firstName,
							"middleName" => $middleName,
							"gender" => $gender,
							"staffEmail" => $staffEmail,
							"staffPassword" => $staffPassword,
							"staffPassport" => $staffPassport);
		}
		echo json_encode($return_arr);
?>
