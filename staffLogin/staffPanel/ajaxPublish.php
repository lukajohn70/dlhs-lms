 <?php
include 'config.php';
if(trim($_POST['question']))
{
	include 'blogClass.php';
	
	$blogClass = new blogClass();
	$question=$_POST['question'];
	echo $blogClass->blogPublish($question);
}
?>