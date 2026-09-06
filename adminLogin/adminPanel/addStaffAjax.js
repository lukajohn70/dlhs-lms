					//function to check the size of an image before upload
					function validatePicture()	//validating the size of picture
					{
						const fi = document.getElementById('file');
						if (fi.files.length > 0) { 
							for (const i = 0; i <= fi.files.length - 1; i++) 
							{ 
								const fsize = fi.files.item(i).size; 
								const file = Math.round((fsize / 1024)); 
								// The size of the file. 
								if (file >= 60) { 
									return "Picture should be greater than 60KB\n"; 
								} 
								else
								{ 
									return "";
								} 
							} 
						} 	
					}
					
					function addStaff() //Function to add the a new staff
					{         
						$('.message3').html('');
						$('.message4').html('');
						
						var surname = $('input[name=surname]').val();
						var firstName = $('input[name=firstName]').val();
						var middleName = $('input[name=middleName]').val();
						var gender = document.getElementById('gender').value;
						var email = $('input[name=email]').val();
						var thePassword = $('input[name=thePassword]').val();					
						
						var mailformat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
						 		 
						if (surname=="")
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Please enter surname')
						}
						else if (firstName=="")
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Please enter first name')
						}
						else if (gender=="")
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Please select gender')
						}
						else if (email.length < 1)
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Please enter email')
						}
						else if(email.length > 1 && !email.match(mailformat))
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + " Please enter staff's valid email")
						}
						else if (thePassword=="")
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Please enter password')
						}
						else if(document.getElementById("file").files.length == 0)
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' No image file selected')
						}
						else if(validatePicture() !=="")
						{
							$('.message2').html('<i class="fa fa-info-circle"></i> ' + ' Image should not be greater than 60KB').fadeIn('slow');
						}
						else
						{									
							var form_data = 
							  'surname='+surname+
							  '&firstName='+firstName+
							  '&middleName='+middleName+
							  '&gender='+gender+
							  '&email='+email+
							  '&thePassword='+thePassword;
																		 
							//start the ajax
							$.ajax({
								url: "addStaff.php",
								type: "POST",    
								data: form_data,
														
								success: function (html) {             
									
									if (html==0)	//If session is expired.
									{                              
										 window.location.replace("logout.php");
									}
									else if (html==1)	//If student successfully added
									{                              
										$('.message2').html("");
										 $('.message1').html('Staff successfully Added.').fadeIn('slow');
										 $('#example').DataTable().clear().destroy();
										 callTable();
									}
									else if (html==2)	//If insertion is unsuccessful	
									{                              
										 $('.message1').html("");
										 $('.message2').html('Could not add Staff. Please try again.').fadeIn('slow');
									}
									else if (html==3)	//If email is already in use
									{                              
										 $('.message1').html("");
										 $('.message2').html('<i class="fa fa-info-circle"></i> ' + ' The email address you entered is already in use. Please use another email.').fadeIn('slow');
									}
									else	//If email is already in use
									{                              
										 $('.message1').html("");
										 $('.message2').html(html).fadeIn('slow');
									}
								}
											
							});
						}
					}