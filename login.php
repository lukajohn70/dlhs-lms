<?php
session_start(); 
	require_once "db_connection/dlhs_db_connection.php";
		
		if(isset($_POST['adminUsername']))
		{			
			$username = mysqli_real_escape_string($connection, $_POST['adminUsername']);
			$password= mysqli_real_escape_string($connection, $_POST['adminPassword']);
			
			$query = "SELECT * FROM stafflogin WHERE username='$username' AND password='$password'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			
			if (($result->num_rows)>0)
			{
				$row = $result->fetch_array(MYSQLI_NUM);
				$name=$row[1]." ".$row[2];		
				//Setting up session for logged in user.			
				echo "Success ".$name;
			}
			else
			{
				echo "Incorrect username/Password";
			}
		}
?>

