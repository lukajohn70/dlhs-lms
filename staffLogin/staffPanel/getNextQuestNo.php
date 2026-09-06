<?php
session_start();
	require_once "../../db_connection/dlhs_db_connection.php";
		$testId = $_POST['testId'];
		
		$query = "SELECT * FROM tests WHERE testId='$testId'";
		$result = $connection->query($query);
        $row = $result->fetch_array(MYSQLI_NUM);
        $questionsTableName = $row[13];

        $query1 = "SELECT * FROM $questionsTableName";
		$result1 = $connection->query($query1);
        $nextQuestionNo = $result1->num_rows + 1;
		echo $nextQuestionNo;
?>
