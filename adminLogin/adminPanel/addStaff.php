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
	
		if(isset($_POST['submitStaff']))
		{    
			$surname = mysqli_real_escape_string($connection, $_POST['surname']);
			$firstName = mysqli_real_escape_string($connection, $_POST['firstName']);
			$middleName = mysqli_real_escape_string($connection, $_POST['middleName']);
			$gender = mysqli_real_escape_string($connection, $_POST['gender']);
			$email = mysqli_real_escape_string($connection, $_POST['staffEmail']);
			$password = mysqli_real_escape_string($connection, $_POST['thePassword']);
									
			$query = "SELECT * FROM stafflogin WHERE username='$email'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				header("location:addStaffForm.php?msgStatus=3");
			}
			else
			{		
				$filename1 = $_FILES['file']['name'];
				$file_extension=strtolower(pathinfo($filename1, PATHINFO_EXTENSION));
				if($file_extension=="jpeg")
				{
					$file_extension = "jpg";
				}
				$filename2=time();
				$filePath2 = 'staffPassports/'.$filename2;	//used later if the file extension is .png
				$location = 'staffPassports/'.$filename2.".".$file_extension; //Location the file would be saved to.
				$file_extension2=$file_extension;
				if($file_extension=="png")
				{
					$file_extension2="jpg";
				}
				$filename=$filename2.".".$file_extension2;
				// Valid image extensions
				$image_ext = array("jpg","png","jpeg");
				if(move_uploaded_file($_FILES['file']['tmp_name'],$location))
				{
					if($file_extension=="png")
					{
						$filePath = $location;

						$image = imagecreatefrompng($filePath);
						$bg = imagecreatetruecolor(imagesx($image), imagesy($image));
						imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
						imagealphablending($bg, TRUE);
						imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
						imagedestroy($image);
						$quality = 80; // 0 = low / smaller file, 100 = better / bigger file 
						imagejpeg($bg, $filePath2 . ".jpg", $quality);
						imagedestroy($bg);
						unlink($location);	//deleting the png image after it had been converted to .jpg
					}
					
					$query1 = "INSERT INTO stafflogin(surname, firstName, middleName, username, password, gender, passport) VALUES('{$surname}', '{$firstName}', '{$middleName}', '{$email}', '{$password}', '{$gender}', '{$filename}')";
					$result1 = $connection->query($query1);
					
					if($result1)
					{	
						header("location:addStaffForm.php?msgStatus=1");
					}
					else
					{
						header("location:addStaffForm.php?msgStatus=2");
					}
				}
			}
		}
		else
		{
			header("location:../logout.php");
		}
	}
?>