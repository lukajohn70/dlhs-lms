<?php
session_start();
$_SESSION['studentLoggedIn'] = true;
$_SESSION['studentId'] = 1;
$_SESSION['testId'] = 1;
require 'index.php';
