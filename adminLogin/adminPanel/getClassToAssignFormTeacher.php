<?php
session_start();
	include "../../db_connection/dlhs_db_connection.php";
		
	if(isset($_POST["yearGroup"]))
	{
		// Capture selected year group
		$yearGroup = $_POST["yearGroup"];
		$yearGroupName = $_POST["yearGroupName"];

        $lgas="select * from classes WHERE classYearGroup='$yearGroup'";
		$result = $connection->query($lgas);
		if (($result->num_rows)>0)
		{
			echo "<option value=''>. . . Select Class from ".$yearGroupName." . . .</option>";
			while($row = $result->fetch_array(MYSQLI_NUM)){
				echo "<option value='$row[0]'>". $row[2] . "</option>";
			}
		}
    }
?>
