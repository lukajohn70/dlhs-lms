<?php
session_start();
		$return_arr = array();
		header('Content-Type: application/json');
		if (!isset($_POST['testId']) || !isset($_SESSION['staffId'])) {
			echo json_encode($return_arr);
			exit;
		}
		include "../../db_connection/dlhs_db_connection.php";
			
		$testId = (int) $_POST['testId'];
		$staffId = (int) $_SESSION['staffId'];
	$query="SELECT * FROM essay_questions WHERE testId='$testId' AND staffId='$staffId'";
	$result = $connection->query($query);
	
	if($result->num_rows > 0)
	{
		$row = $result->fetch_array(MYSQLI_NUM);
		$question  = $row[3];
		// Decode any HTML entities and return raw HTML so the staff preview shows formatting
		$question = html_entity_decode($question, ENT_QUOTES | ENT_HTML5);
		$return_arr[] = array("question" => $question);
		echo json_encode($return_arr);
	}
	else
	{
		echo json_encode($return_arr);
	}
	
		
?>
