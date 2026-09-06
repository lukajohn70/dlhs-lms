<?php
session_start(); 
require_once "../db_connection/dlhs_db_connection.php";
    
if(isset($_POST['studentUsername']))
{
    $username=$_POST['studentUsername'];
    $password=$_POST['studentPassword'];
                        
    $query = "SELECT * FROM studentlogin WHERE studentEmail='$username' AND password='$password'";
    $result = $connection->query($query);
    if (!$result) die($connection->error);
    
    if (($result->num_rows)>0)
    {
        $row = $result->fetch_assoc();
                                
        //Setting up session for logged in user.
        $_SESSION['studentLast_login']=time();
        $_SESSION['studentId'] = $row['studentId'];
        $_SESSION['studentEmail'] = $row['studentEmail'];
        $_SESSION['studentYearGroup'] = $row['yearGroupId'];
        $_SESSION['studentName'] = $row['firstName']." ".$row['surname'];
        $_SESSION['studentLoggedIn'] ="yes";
        $_SESSION['forcePasswordChange'] = ($password === '1234') ? 1 : 0;
        
        if(!isset($row['passport']) || $row['passport'] == "")
        {
            $_SESSION['passportNameAndLocation'] = '../../images/dlhslogo3.jpg';
        }
        else
        {
            $_SESSION['passportNameAndLocation'] = '../../adminLogin/adminPanel/studentPassports/'.$row['passport'];
        }
        //Getting the user login log i.e. login time and date.
        $dateAndTime = date('Y-m-d H:i:s');
        $finalDateAndTime = date('d-m-Y h:i A', strtotime($dateAndTime));
        $finalDate=date('Y-m-d');
        $time=date('H:i:s');
        $finalTime=date('h:i A', strtotime($time));
        
        $idOfLoggedInUser = $row['studentId'];
        $usernameOfLoggedInUser = $row['admissionNumber'];
        $nameOfUser = $row['firstName']." ".$row['surname'];
        
        //Inserting login log into the database.
        $query1 = "INSERT INTO studentloginlog(studentId, username, nameOfStudent, loginTime, loginDate, loginTimeDate) VALUES('{$idOfLoggedInUser}', '{$usernameOfLoggedInUser}', '{$nameOfUser}', '{$finalTime}', '{$finalDate}', '{$finalDateAndTime}')";
        $result1 = $connection->query($query1);
    
        echo ($password === '1234') ? 2 : 1;
    }
    else
    {
        echo 0;
    }
}
else
{
    echo 0;
}
?>
