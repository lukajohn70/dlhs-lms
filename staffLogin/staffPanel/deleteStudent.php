<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['userLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/zamani_db_connection.php";
	if(isset($_GET['studentId']))
	{   
		$studentId=$_GET['studentId'];
		$query = "DELETE FROM studentlogin WHERE studentId='$studentId'";
		$result = $connection->query($query);
		if (!$result) die($connection->error);
		if ($result)
		{
			header('location:addStudentForm.php');
		}
		else
		{
			header('location:addStudentForm.php');
		}
		
	}	
	
?>