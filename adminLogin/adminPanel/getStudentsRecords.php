<?php
session_start();
	error_reporting(0);
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="select * from studentlogin";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$studentId  = $row[0];
			$surname = $row[1];
			$firstName = $row[2];
			$middleName = $row[3];
			$gender = $row[4];
			$admissionNumber = $row[5];
			$studentEmail = $row[6];
			$studentPassword = $row[7];
			$studentYearGroupId = $row[8];
			$studentClassId = $row[9];
			$studentPassport = $row[10];
			
			//Getting student's class name
			$query1="SELECT * FROM classes WHERE classId='$studentClassId'";
			$result1 = $connection->query($query1);
			$row1 = $result1->fetch_array(MYSQLI_NUM);
			
			//Getting student's year group name
			$query2="SELECT * FROM yeargroup WHERE yearGroupId='$studentYearGroupId'";
			$result2 = $connection->query($query2);
			$row2 = $result2->fetch_array(MYSQLI_NUM);			
			
			$return_arr[] = array("studentId" => $studentId,
							"surname" => $surname,
							"firstName" => $firstName,
							"middleName" => $middleName,
							"gender" => $gender,
							"admissionNumber" => $admissionNumber,
							"studentClassName" => $row2[1].' '.$row1[2],
							"studentEmail" => $studentEmail,
							"studentPassword" => $studentPassword,
							"studentYearGroupId" => $studentYearGroupId,
							"studentClassId" => $studentClassId,
							"studentPassport" => $studentPassport);
		}
		echo json_encode($return_arr);
?>
