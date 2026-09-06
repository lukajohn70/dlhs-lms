<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{	
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['uploadCsvOfStaff']))
		{    			
			$csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
			
			// Validate whether selected file is a CSV file
			if(!empty($_FILES['file4']['name']) && in_array($_FILES['file4']['type'], $csvMimes))
			{
				// If the file is uploaded
				if(is_uploaded_file($_FILES['file4']['tmp_name']))
				{					
					// Open uploaded CSV file with read-only mode
					$csvFile = fopen($_FILES['file4']['tmp_name'], 'r');
					
					
					
					
					// Skip the first row
					fgetcsv($csvFile);
					
					$staffRecordsArray[] = array();	//declaration of an empty array
					unset($staffRecordsArray);
					$oneRequiredFieldEmptyOrNot = 0; //to hold flag to checkif an input area is empty or not for any record
									
					while(($line = fgetcsv($csvFile)) !== FALSE)
					{
						$surname = ucfirst(strToLower(mysqli_real_escape_string($connection, $line[0])));
						$firstName = ucfirst(strToLower(mysqli_real_escape_string($connection, $line[1])));
						$middleName = ucfirst(strToLower(mysqli_real_escape_string($connection, $line[2])));
						$gender = ucfirst(strToLower(mysqli_real_escape_string($connection, $line[3])));
						$staffEmail = mysqli_real_escape_string($connection, $line[4]);
						$password = mysqli_real_escape_string($connection, $line[5]);

						$staffRecordsArray[] = array("surname" => $surname,
														"firstName" => $firstName,
														"middleName" => $middleName,
														"gender" => $gender,
														"staffEmail" => $staffEmail,
														"password" => $password);
	
						//checking if any of the required inputs for a row is empty on the CSV file
						if($surname == "" || $firstName == "" || $gender == "" || $staffEmail == "" || $password == "")
						{
							$oneRequiredFieldEmptyOrNot=1;
						}
					}
									// Close opened CSV file
					fclose($csvFile);
					
					if($oneRequiredFieldEmptyOrNot == 0)
					{
						$updatedOrNotStatus=0; //flag set to check if it is an update of already inserted record										
														
						foreach($staffRecordsArray as $key=>$value)
						{
							// Get row data from array into variables
							$theSurname = $value['surname'];
							$theFirstName = $value['firstName'];
							$theMiddleName = $value['middleName'];
							$theGender = $value['gender'];
							$theStaffEmail = $value['staffEmail'];
							$thePassword = $value['password'];
						
							$checkForAlreadyInsertedRecords="SELECT * FROM stafflogin WHERE username='$theStaffEmail'";
							$result3 = $connection->query($checkForAlreadyInsertedRecords);
							if (($result3->num_rows)>0)	//check if the stafflogin table has a similar record. Then an update is made based on certain criteria.
							{
								$updateCourseValue="UPDATE stafflogin SET surname='$theSurname', firstName='$theFirstName', middleName='$theMiddleName', password='$thePassword', gender='$theGender' WHERE username='$theStaffEmail'";
								$result4 = $connection->query($updateCourseValue);
								$updatedOrNotStatus=1;
							}
							else	//if no similar record exists. This allows for new insertion
							{						
								$insertCourseValues="INSERT INTO stafflogin(surname, firstName, middleName, username, password, gender) VALUES('{$theSurname}', '{$theFirstName}', '{$theMiddleName}', '{$theStaffEmail}', '{$thePassword}', '{$theGender}')";
								$result4 = $connection->query($insertCourseValues);
							}
						}
						if($updatedOrNotStatus==1)
						{
							header("location:addStaffForm.php?msgStatus3=11");	//success messsage if the upload was an update
						}
						else
						{
							header("location:addStaffForm.php?msgStatus3=1");	//success messsage if the upload was all a new upload
						}
					}
					else
					{
						header("location:addStaffForm.php?msgStatus3=2"); //error if one of the required inut areas is empty
					}
				}
			}
			else
			{
				header("location:addStaffForm.php?msgStatus3=3"); //error if file extension is anot among the allowed ones
			}
		}
		else
		{
			header('location:../index.php');
		}
	}
?>