<?php
use Phppot\DataSource;

require_once 'DataSource.php';
$db = new DataSource();
$conn = $db->getConnection();

if (isset($_POST["import"])) {
    
    $fileName = $_FILES["file"]["tmp_name"];
    
    if ($_FILES["file"]["size"] > 0) {
        
		$file = fopen($fileName, "r");
        
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
			$userId = "";
            if (isset($column[0])) {
                $userId = mysqli_real_escape_string($conn, $column[0]);
            }
            $userName = "";
            if (isset($column[1])) {
                $userName = mysqli_real_escape_string($conn, $column[1]);
            }
            $password = "";
            if (isset($column[2])) {
                $password = mysqli_real_escape_string($conn, $column[2]);
            }
            $firstName = "";
            if (isset($column[3])) {
                $firstName = mysqli_real_escape_string($conn, $column[3]);
            }
            $lastName = "";
            if (isset($column[4])) {
                $lastName = mysqli_real_escape_string($conn, $column[4]);
            }
			$email = "";
            if (isset($column[5])) {
                $email = mysqli_real_escape_string($conn, $column[5]);
            }
            
            $sqlInsert = "INSERT into users (userId,userName,password,firstName,lastName,email)
                   values (?,?,?,?,?,?)";
            $paramType = "issss";
            $paramArray = array(
                $userId,
                $userName,
                $password,
                $firstName,
                $lastName,
				$email
				
            );
            $insertId = $db->insert($sqlInsert, $paramType, $paramArray);
            
            if (! empty($insertId)) {
                header('location:addStudentForm.php');
            } else {
                //header('location:addStudentForm.php');
            }
        }
    }
}
?>