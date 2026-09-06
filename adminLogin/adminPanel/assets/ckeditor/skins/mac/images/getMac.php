<?php  
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
	   
	   $myfile = fopen("test.php", "w") or die("Unable to open file!");
		fwrite($myfile, $macaddress);
		fclose($myfile);       
?>   