$(document).ready(function() {
				   //var loader='<img src="ajax-loader(1).gif" />';
				   $('#submit').attr('disabled','true');
				   
				   
				   //if submit button is clicked
				 
					$('#submit').click(function () {       
						
						//show the loader
						//$('.loading').html(loader).fadeIn();     
					  
						var name = $('input[name=staffName]').val();
						var gender = document.getElementById('gender').value;
						var username = $('input[name=username]').val();
						var password = $('input[name=password]').val();
						
						 
				 
						if (name=="")
						{
							$('.message').html('Please enter the name')
						}
						else
						{
						//organize the data properly
						var form_data = 
						  'staffName='+name+
						  '&gender='+gender+
						  '&username='+username+
						  '&password='+password+
						  '&reset='+'nothing';
						  						 
						//disabled all the text fields
						//$('.text').attr('disabled','true');
						 
						 
						//start the ajax
						$.ajax({
							//this is the php file that processes the data and send mail
							url: "addStaff.php",
							 
							//POST method is used
							type: "POST",
				 
							//pass the data        
							data: form_data,    
							 
							
							//success
							success: function (html) {             
								//If email doe not exist in database
								
								if (html==0) {                              
									 window.location.replace("logout.php");
								}
								//If confirmation email is sent
								else if (html==1) 
								{                              
									 $('.message').hide();
									 $('.message1').html('Staff successfully added.').fadeIn('slow');
									 $('#submit').attr('disabled','true'); 
									 setTimeout(' window.location.href = "addStaffForm.php"; ', 1000);
								}
								else if (html==2)
								{                              
									 $('.message1').hide();
									 $('.message').html('Could not add Staff. Please try again.').fadeIn('slow');
								}
								else if (html==3)
								{                              
									 $('.message1').hide();
									 $('.message').html('The entered email is already in use. Please use another email.').fadeIn('slow');
								}
							}
										
						});
						 }
						//cancel the submit button default behaviours
						return false;
					});
				});