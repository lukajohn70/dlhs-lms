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

if (!isset($_POST['selectedClassId']) || !isset($_POST['selectedYearGroupId'])) {
	echo json_encode($return_arr);
	exit;
}

$selectedYearGroupId = intval($_POST['selectedYearGroupId']);
$selectedClassId     = intval($_POST['selectedClassId']);

if ($selectedClassId <= 0) {
	echo json_encode($return_arr);
	exit;
}

// Single high-performance JOIN query (1 query instead of 1 + 2N queries)
$query = "
	SELECT 
		s.studentId,
		s.surname,
		s.firstName,
		s.middleName,
		s.gender,
		s.admissionNumber,
		s.studentEmail,
		s.password,
		s.yearGroupId,
		s.classId,
		s.passport,
		c.className,
		c.classYearGroup,
		y.yearGroupName
	FROM studentlogin s
	LEFT JOIN classes c ON s.classId = c.classId
	LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId
	WHERE s.classId = '$selectedClassId'
	ORDER BY s.surname ASC, s.firstName ASC
";

$result = $connection->query($query);

if ($result && $result->num_rows > 0)
{
	while ($row = $result->fetch_assoc())
	{
		$yearGroupId = !empty($row['classYearGroup']) ? $row['classYearGroup'] : $row['yearGroupId'];
		$return_arr[] = array(
			"studentId"          => $row['studentId'],
			"surname"            => $row['surname'],
			"firstName"          => $row['firstName'],
			"middleName"         => $row['middleName'],
			"gender"             => $row['gender'],
			"admissionNumber"    => $row['admissionNumber'],
			"studentClassName"   => trim(($row['yearGroupName'] ?? '') . ' ' . ($row['className'] ?? '')),
			"studentEmail"       => $row['studentEmail'],
			"studentPassword"    => $row['password'],
			"studentYearGroupId" => $yearGroupId,
			"studentClassId"     => $row['classId'],
			"studentPassport"    => $row['passport']
		);
	}
}

echo json_encode($return_arr);
?>
