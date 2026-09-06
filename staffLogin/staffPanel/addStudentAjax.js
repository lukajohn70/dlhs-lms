$(document).ready(function() {
								   
				   //if submit button is clicked for adding one student at a time.
					$('#submit').click(function () {       
						
						//show the loader
						//$('.loading').html(loader).fadeIn();     
					  
						var surname = $('input[name=surname]').val();
						var otherNames = $('input[name=otherNames]').val();
						var username = $('input[name=username]').val();
						var password = $('input[name=password]').val();
						var gender = document.getElementById('gender').value;
						var className = document.getElementById('className').value;
						var yeargroup = document.getElementById('yeargroup').value;
						var section = document.getElementById('section').value;
						
						
						 
				 
						if (surname=="")
						{
							$('.message').html('Please enter surname')
						}
						else if (otherNames=="")
						{
							$('.message').html('Please other name(s)')
						}
						else if (username=="")
						{
							$('.message').html('Please enter username')
						}
						else if (password=="")
						{
							$('.message').html('Please enter password')
						}
						else if (gender=="")
						{
							$('.message').html('Please select gender')
						}
						else if (className=="")
						{
							$('.message').html('Please select class name')
						}
						else if (yeargroup=="")
						{
							$('.message').html('Please select year group')
						}
						else if (section=="")
						{
							$('.message').html('Please select section')
						}
						else
						{
						//organize the data properly
						var form_data = 
						  'surname='+surname+
						  '&otherNames='+otherNames+
						  '&username='+username+
						  '&password='+password+
						  '&gender='+gender+
						  '&className='+className+
						  '&yearGroup='+yeargroup+
						  '&section='+section;
											  						 
						//start the ajax
						$.ajax({
							//this is the php file that processes the data and send mail
							url: "addStudent.php",
							 
							//POST method is used
							type: "POST",
				 
							//pass the data        
							data: form_data,    
							 
							
							//success
							success: function (html) {             
								//If email doe not exist in database
								
								if (html==0)	//If session is expired.
								{                              
									 window.location.replace("logout.php");
								}
								else if (html==1)	//If class successfully added
								{                              
									 $('.message').hide();
									 $('.message1').html('Student successfully added.').fadeIn('slow');
									 $('#submit').attr('disabled','true'); 
									 setTimeout(' window.location.href = "addStudentForm.php"; ', 1000);
								}
								else if (html==2)	//If insertion is unsuccessful	
								{                              
									 $('.message1').hide();
									 $('.message').html('Could not add Student. Please try again.').fadeIn('slow');
								}
								else if (html==3)	//If class already exists.
								{                              
									 $('.message1').hide();
									 $('.message').html('A student has been registered with that Admission no. Please use another Admission no.').fadeIn('slow');
								}
							}
										
						});
						 }
						//cancel the submit button default behaviours
						return false;
					});
					
					//Submit button for uploading many students from a CSV file
					$("#submitCSV").click(function (){

						var fileType = ".csv";
						var theFile = document.getElementById('file').value;
						var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + fileType + ")$");
						if (theFile =="")
						{
							$('.message').html('Please select a file')
						}
						else if (!regex.test($("#file").val().toLowerCase())) {
								
							$('.message').html("Invalid File. Upload : <b>" + fileType + "</b> Files.");
							return false;
						}
						else
						{
							//$('.message').html("");
							var form_data = new FormData();

						   // Read selected files
							form_data.append("files[]", document.getElementById('file').files[0]);
							
							var return1=0;
							// AJAX request
							$.ajax({
								url: 'uploadCSV.php', 
								 type: 'post',
								 data: form_data,
								 contentType: false,
								 processData: false,
								 async: false,
								 success: function (html) {
										if (html==0)	//If session is expired.
										{                              
											 window.location.replace("logout.php");
										}
										else if (html==1)	//If class successfully added
										{                              
											 $('.message').hide();
											 $('.message1').html('Student successfully added.').fadeIn('slow');
											 $('#submit').attr('disabled','true'); 
											 setTimeout(' window.location.href = "addStudentForm.php"; ', 1000);
										}
										else if (html==2)	//If insertion is unsuccessful	
										{                              
											 $('.message1').hide();
											 $('.message').html('Could not add Student. Please try again.').fadeIn('slow');
										}
										else if (html==3)	//If class already exists.
										{                              
											 $('.message1').hide();
											 $('.message').html('A student has been registered with that Admission no. Please use another Admission no.').fadeIn('slow');
										}
										else
										{
											//$('.message1').hide();
											 $('.message').html('Oops! Something went wrong').fadeIn('slow');
										}
								}
							});
						}
						return true;
					});
});