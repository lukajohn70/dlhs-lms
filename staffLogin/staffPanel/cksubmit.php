<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['userLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		    
			function dataready($data) 
			{
				$data = trim(preg_replace('/[\s\t\n\r\s]+/', '', $data));
				$data = stripslashes($data);
				$data = htmlspecialchars($data);
				return $data;
			} 
			
			//$testId = mysql_entities_fix_string($connection, $_POST['testId']);
			//$question = mysqli_real_escape_string($connection, $_POST['question']);
			$question = $_POST['question'];
			echo $question;
		
	}
?>