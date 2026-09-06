<?php 
	include "../../db_connection/dlhs_db_connection.php";
		
		$return_arr = array();
		$query="select * from tests";
		$result = $connection->query($query);
		
		while($row = $result->fetch_array(MYSQLI_NUM))
		{
			$tableName1  = $row[13];
			$tableName2  = $row[14];
			$tableName3  = $row[16];
			
			$query1="DROP TABLE $tableName1";
			$result1 = $connection->query($query1);
			
			$query2="DROP TABLE $tableName2";
			$result2 = $connection->query($query2);
			
			$query3="DROP TABLE $tableName3";
			$result3 = $connection->query($query3);
		
		}
?>