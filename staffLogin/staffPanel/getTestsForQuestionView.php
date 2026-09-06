<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

// Get filters from request
$filterSession = isset($_GET['session']) ? $_GET['session'] : '';
$filterTerm = isset($_GET['term']) ? $_GET['term'] : '';
$filterExamType = isset($_GET['examType']) ? $_GET['examType'] : '';

// Build query
if(isset($_SESSION['staffId'])){
	$staffId = $_SESSION['staffId'];
	
	// Get tests created by this teacher
	// Include all tests (even if assignment is now deactivated) to preserve historical access
	$query = "SELECT DISTINCT t.testId, t.testName, t.academicSession, t.term, t.testType 
	          FROM tests t
	          WHERE t.staffId='$staffId'";
} else {
	// Admin view - show all tests
	$query = "SELECT testId, testName, academicSession, term, testType FROM tests";
}

// Add filter conditions
$conditions = array();
if(!empty($filterSession)){
	$conditions[] = "academicSession = '" . mysqli_real_escape_string($connection, $filterSession) . "'";
}
if(!empty($filterTerm)){
	$conditions[] = "term = '" . mysqli_real_escape_string($connection, $filterTerm) . "'";
}
if(!empty($filterExamType)){
	$conditions[] = "testType = '" . mysqli_real_escape_string($connection, $filterExamType) . "'";
}

if(count($conditions) > 0){
	$query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY t.testName";

$result = $connection->query($query);
$return_arr = array();

while($row = $result->fetch_array(MYSQLI_NUM)){
	$return_arr[] = array(
		"testId" => $row[0],
		"testName" => $row[1],
		"academicSession" => $row[2],
		"term" => $row[3],
		"testType" => $row[4]
	);
}

echo json_encode($return_arr);
?>
