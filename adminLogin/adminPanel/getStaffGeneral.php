<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
	
    $getStaff="SELECT * from stafflogin";
	$result = $connection->query($getStaff);
	if (($result->num_rows)>0)
	{
		echo "<option value=''>... Select House master ...</option>";
		while($row = $result->fetch_array(MYSQLI_NUM)){
			echo "<option value='$row[0]'>". $row[1].' '.$row[1].' '.$row[1]."</option>";
		}
	}
?>
