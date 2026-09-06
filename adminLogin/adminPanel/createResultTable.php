<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'dlhs_db_connection.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
	
		//post for change of password
		if(isset($_POST['yearToCreateResultTable']))
		{    
			$yearToCreateResultTable = mysqli_real_escape_string($connection, $_POST['yearToCreateResultTable']);
			
			$query = "SELECT * FROM results_table_names WHERE yearId='$yearToCreateResultTable'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
			}
			else
			{
				//Getting the year name
				$query1 = "SELECT * FROM academic_year WHERE academicYearId='$yearToCreateResultTable'";
				$result1 = $connection->query($query1);
				if (!$result1) die($connection->error);
				$row1 = $result1->fetch_array(MYSQLI_NUM);
				$yearName = $row1[1];
			
				$status=0;
				$stripApos4rmTestName=str_replace(str_split('\/:*?"<>|+-&\''), "", $yearName);
				$tableName1=strtolower(str_replace( array( '\'', '"', ',' , ';', '<', '>', '.', ' ' ), '', $stripApos4rmTestName));
				
				//Creating the table for results of the selected year.
				$resultTableName = strtolower("result".$tableName1."_".rand(10,1000));
				$query3 = "CREATE TABLE IF NOT EXISTS $resultTableName (
							resultId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
							studentId INT NOT NULL,
							academicYearId INT NOT NULL,
							termId INT NOT NULL,
							studentClassId INT NOT NULL,
							formTeacherId INT NOT NULL,
							studentYearGroupId INT NOT NULL,
							sectionId INT NOT NULL,
							midTermScoresArray TEXT NOT NULL, 
							endOfTermScoresArray TEXT NOT NULL, 
							characterArray TEXT NOT NULL, 
							psychomotorArray TEXT NOT NULL, 
							awardsAndPrizes TEXT NOT NULL, 
							houseId INT NOT NULL,
							sportActivityId INT NOT NULL,
							houseMasterRemark TEXT NOT NULL,
							gamesMasterRemark TEXT NOT NULL,
							principalMidTermRemark TEXT NOT NULL,
							principalEndOfTermRemark TEXT NOT NULL
						)ENGINE=InnoDB DEFAULT CHARACTER SET=utf8";
				$result3 = $connection->query($query3);
				
				$query2 = "INSERT INTO results_table_names(yearId, tableName) VALUES('{$yearToCreateResultTable}', '{$resultTableName}')";
				$result2 = $connection->query($query2);
				
				
				if ($result2)
				{	
					echo 1;
				}
				else
				{
					echo 2;
				}
			}
		}
		else
		{
			echo 0;
		}
	}
?>