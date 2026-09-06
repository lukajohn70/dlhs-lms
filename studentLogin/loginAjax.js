$(document).ready(function() {
	
	$('#submit').click(function () {       
		$('.message1').html("");
		$('.message2').html("");
		
		var studentUsername = $('input[name=studentUsername]').val();
		var studentPassword = $('input[name=studentPassword]').val();
					
		if (studentUsername=="")
		{
			$('.message1').html('Please enter your email')
		}
		else if (studentPassword=="")
		{
			$('.message1').html('Please enter your password')
		}
		else
		{
			
			//organize the data properly
			var form_data = 
			  'studentUsername='+studentUsername+
			  '&studentPassword='+studentPassword;
					 
			//start the ajax
			$.ajax({
				url: "login.php",
				type: "POST",   
				data: form_data,    
				
				//success
					success: function (html) {
						html = $.trim(html);
						if (html==0) {	//If incorrect login details                            
						$('.message2').html("");									
						$('.message1').html('<i class="fa fa-times"></i>'+' Incorrect email/password').fadeIn('slow'); 
					}
						else if (html==1)	//If correct login details
					{
						$('.message1').html('');
						$('.message2').html('<i class="fa fa-check"></i>'+' Login successful...').fadeIn('slow');
						$('#submit').attr('disabled','true'); 
							setTimeout(' window.location.href = "studentPanel/"; ',1000);
						}
						else if (html==2)
						{
							$('.message1').html('');
							$('.message2').html('<i class="fa fa-check"></i> Login successful. Please change your default password.').fadeIn('slow');
							$('#submit').attr('disabled','true');
							setTimeout(' window.location.href = "studentPanel/changePassword.php?force=1"; ',1000);
						}
					}						
			});
		}
		//cancel the submit button default behaviours
		return false;
	});
});
