$(document).ready(function() {
				    //if submit button is clicked
				 
					$('#submit').click(function () {       
						
						//show the loader
						//$('.loading').html(loader).fadeIn();     
					  
						var name = $('input[name=adminUsername]').val();
						var password = $('input[name=adminPassword]').val();
						
						 
				 
						if (name=="")
						{
							$('.message').html('Please enter the username')
						}
						else if (password=="")
						{
							$('.message').html('Please enter the password')
						}
						else
						{
						//organize the data properly
						var form_data = {
						  adminUsername: name,
						  adminPassword: password
						};
						 
						//disabled all the text fields
						//$('.text').attr('disabled','true');
						 
						 
						//start the ajax
						$.ajax({
							url: "login.php",
							type: "POST",       
							data: form_data,
							success: function (html) {
								html = $.trim(html);
								if (html=="1") {                              
									 $('.message').hide();
									 $('.message1').html('<i class="fa fa-check"></i>'+' Login successful...').fadeIn('slow');
									 $('#submit').attr('disabled','true'); 
									 setTimeout(' window.location.href = "adminPanel/"; ',1000);
								}
								else if (html=="2")
								{
									$('.message').hide();
									$('.message1').html('<i class="fa fa-check"></i> Login successful. Please change your default password.').fadeIn('slow');
									$('#submit').attr('disabled','true');
									setTimeout(' window.location.href = "adminPanel/changePassword.php?force=1"; ',1000);
								}
								else if (html=="0")
								{
									//$('.loading').html(loader).fadeIn();
									//$('.loading').fadeOut();
									$('.message1').hide();									
									$('.message').html('<i class="fa fa-times"></i>'+' Incorrect username/password').fadeIn('slow');
								}
								else
								{
									$('.message1').hide();
									$('.message').html('<i class="fa fa-times"></i>'+' Login service is not connected to the database').fadeIn('slow');
								}
							},
							error: function () {
								$('.message1').hide();
								$('.message').html('<i class="fa fa-times"></i>'+' Login service is not connected to the database').fadeIn('slow');
							}
										
						});
						 }
						//cancel the submit button default behaviours
						return false;
					});
				});
