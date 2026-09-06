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
	
		if(isset($_POST['updateStudentPicture3']))
		{    
			$studentId = mysqli_real_escape_string($connection, $_POST['setStudentId3']);
											
			$filename1 = $_FILES['file3']['name'];
			$file_extension=strtolower(pathinfo($filename1, PATHINFO_EXTENSION));
			if($file_extension=="jpeg")
			{
				$file_extension = "jpg";
			}
			$filename2=time();
			$filePath2 = 'studentPassports/'.$filename2;	//used later if the file extension is .png
			$location = 'studentPassports/'.$filename2.".".$file_extension; //Location the file would be saved to.
			$file_extension2=$file_extension;
			if($file_extension=="png")
			{
				$file_extension2="jpg";
			}
			$filename=$filename2.".".$file_extension2;
			// Valid image extensions
			$image_ext = array("jpg","png","jpeg");
			if(move_uploaded_file($_FILES['file3']['tmp_name'],$location))
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
				$query = "SELECT * FROM studentlogin WHERE studentId='$studentId'";
				$result = $connection->query($query);
				if (!$result) die($connection->error);
				$row = $result->fetch_array(MYSQLI_NUM);
				$passportPath='studentPassports/'.$row[10];
				unlink($passportPath);
					
				$query1 = "UPDATE studentlogin SET passport='$filename' WHERE studentId='$studentId'";
				$result1 = $connection->query($query1);
				
				if($result1)
				{						
					header("location:addStudentForm.php?msgStatus2=1");
				}
				else
				{
					header("location:addStudentForm.php?msgStatus2=2");
				}
			}
		}
		else
		{
			header("location:../logout.php");
		}
	}
?>