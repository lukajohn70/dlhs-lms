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
	
		//post for bulk toggle review option
		if(isset($_POST['testIds']) && isset($_POST['newReviewOption']))
		{    
			$testIds = mysqli_real_escape_string($connection, $_POST['testIds']);
			$newReviewOption = mysqli_real_escape_string($connection, $_POST['newReviewOption']);
			
			// Split the comma-separated test IDs
			$testIdArray = explode(',', $testIds);
			$successCount = 0;
			$failCount = 0;
			
			// Update each test
			foreach($testIdArray as $testId)
			{
				$testId = trim($testId);
				if(!empty($testId))
				{
					$query = "UPDATE tests SET reviewOption='$newReviewOption' WHERE testId='$testId'";
					$result = $connection->query($query);
					
					if ($result)
					{	
						$successCount++;
					}
					else
					{
						$failCount++;
					}
				}
			}
			
			// Return success if at least one test was updated successfully
			if($successCount > 0)
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
			echo 2;
		}
	}
?>

