<?php
session_start(); 
require_once "../db_connection/dlhs_db_connection.php";
    
if(isset($_POST['staffUsername']))
{
    $username=$_POST['staffUsername'];
    $password=$_POST['staffPassword'];
                        
    $query = "SELECT * FROM stafflogin WHERE username='$username' AND password='$password'";
    $result = $connection->query($query);
    if (!$result) die($connection->error);
    
	    if (($result->num_rows)>0)
	    {
	        $row = $result->fetch_array(MYSQLI_NUM);
                                
        //Setting up session for logged in user.
        $_SESSION['staffLast_login']=time();
        $_SESSION['staffId'] = $row[0];
        $_SESSION['staffEmail'] = $row[2];
	        $_SESSION['staffName'] = $row[1];
	        $_SESSION['staffLoggedIn'] ="yes";
	        $_SESSION['forcePasswordChange'] = ($password === '4321') ? 1 : 0;
        
        if($row[8] == "")
        {
            $_SESSION['passportNameAndLocation'] = '../../images/dlhslogo3.jpg';
        }
        else
        {
            $_SESSION['passportNameAndLocation'] = '../../adminLogin/adminPanel/staffPassports/'.$row[8];
        }
    
	        echo ($password === '4321') ? 2 : 1;
    }
    else
    {
        echo 0;
    }
}
else
{
    header("location:../staffLogin/");
}
?>
