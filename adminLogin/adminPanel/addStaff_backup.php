<?php
session_start();
	require_once 'sessionTime.php';
	if ((time() - $_SESSION['adminLast_login'])> $allottedTime)
	{	
		require_once 'unsetSessions.php';
		echo 0;
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
	
		if(isset($_POST['file']))
		{    
			$surname = mysql_entities_fix_string($connection, $_POST['surname']);
			$firstName = mysql_entities_fix_string($connection, $_POST['firstName']);
			$middleName = mysql_entities_fix_string($connection, $_POST['middleName']);
			$gender = mysql_entities_fix_string($connection, $_POST['gender']);
			$email = mysql_entities_fix_string($connection, $_POST['email']);
			$password = mysql_entities_fix_string($connection, $_POST['thePassword']);
									
			$query = "SELECT * FROM stafflogin WHERE username='$email'";
			$result = $connection->query($query);
			if (!$result) die($connection->error);
			if (($result->num_rows)>0)
			{
				echo 3;
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
					
					$query1 = "INSERT INTO stafflogin(surname, firstName, middleName, username, password, gender) VALUES('{$surname}', '{$firstName}', '{$middleName}', '{$email}', '{$password}', '{$gender}')";
					$result1 = $connection->query($query1);
					
					if ($result1)
					{	
						echo 1;
					}
					else
					{
						echo 2;
					}
				}
			}
		}
		else
		{
			echo 0;
		}
	}
?>