<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['staffLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['testId']))
		{    
			function dataready($data) 
			{
				$data = trim(preg_replace('/[\s\t\n\r\s]+/', '', $data));
				$data = stripslashes($data);
				$data = htmlspecialchars($data);
				return $data;
			} 
			
			$questionId = $_POST['questionId'];
            $testId = $_POST['testId'];
			$question = html_entity_decode(mysqli_real_escape_string($connection, $_POST['question']));
			$optionA = html_entity_decode(mysqli_real_escape_string($connection, $_POST['optionA']));
			$optionB = html_entity_decode(mysqli_real_escape_string($connection, $_POST['optionB']));
			$optionC = html_entity_decode(mysqli_real_escape_string($connection, $_POST['optionC']));
			$optionD = html_entity_decode(mysqli_real_escape_string($connection, $_POST['optionD']));
			$selectedValue = mysqli_real_escape_string($connection, $_POST['selectedValue']);
			$mark = mysqli_real_escape_string($connection, $_POST['mark']);
						
			$query = "SELECT * FROM tests WHERE testId='$testId'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				$row = $result->fetch_array(MYSQLI_NUM);
				$tableName=$row[13];
				$query1 = "UPDATE $tableName SET question='$question', optionA='$optionA', optionB='$optionB', optionC='$optionC', optionD='$optionD', correctOption='$selectedValue', markForQuestion='$mark' WHERE questionId='$questionId'";
				$result1 = $connection->query($query1);
				
				if ($result1)
				{	
					echo 1;
				}
				else
				{
					echo 2;
				}
			}
			else
			{
				echo 3;
			}
		}
		else
		{
			echo 0;
		}
	}
?>