<?php
session_start();
	error_reporting(0);
	require_once "../../db_connection/dlhs_db_connection.php";
		$testStatus=0; //status of not yet started test.
		
		$query4 = "SELECT * FROM tests";
		$result4 = $connection->query($query4);
		$return_arr = array();
		if (($result4->num_rows)>0)
		{
			while($row4 = $result4->fetch_array(MYSQLI_NUM)) 
			{
				$return_arr[] = array('title' => $row4[2].' ['.$row4[5].':'.$row4[6].$row4[7].']',
							                                                'start' => $row4[3]);
			}
		}
		echo json_encode($return_arr);
?>
