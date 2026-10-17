<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
	@session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}

ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

include "../../db_connection/dlhs_db_connection.php";
	
$return_arr = array();
$query = "SELECT s.studentId, s.surname, s.firstName, s.middleName, s.gender, s.admissionNumber, s.studentEmail, s.yearGroupId, s.classId, s.passport, y.yearGroupName, c.className
		  FROM studentlogin s
		  LEFT JOIN classes c ON c.classId = s.classId
		  LEFT JOIN yeargroup y ON y.yearGroupId = s.yearGroupId
		  ORDER BY s.surname ASC, s.firstName ASC";
$result = $connection->query($query);

if ($result) {
	while($row = $result->fetch_assoc())
	{
		$return_arr[] = array(
			"studentId" => $row['studentId'],
			"surname" => $row['surname'],
			"firstName" => $row['firstName'],
			"middleName" => $row['middleName'],
			"gender" => $row['gender'],
			"admissionNumber" => $row['admissionNumber'],
			"studentClassName" => trim(($row['yearGroupName'] ?? '') . ' ' . ($row['className'] ?? '')),
			"studentEmail" => $row['studentEmail'],
			"studentPassword" => "",
			"studentYearGroupId" => $row['yearGroupId'],
			"studentClassId" => $row['classId'],
			"studentPassport" => $row['passport']
		);
	}
}
echo json_encode($return_arr);
exit;
