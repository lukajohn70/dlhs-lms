	function addSubject()
	{
		$('.message1').html("");
		$('.message2').html("");
				
		var subjectName = $('input[name=subjectName]').val();
		
		if (subjectName=="")
		{
			$('.message2').html('<i class="fa fa-info-circle"></i> Please enter name of Subject')
		}
		else
		{
			var form_data = 
			  'subjectName='+subjectName;
			$.ajax({
				url: "addSubject.php",
				type: "POST",       
				data: form_data,    
				success: function (html) {             								
					if (html==0) {                              
						 window.location.replace("logout.php");
					}
					else if (html==1) 
					{                              
						$('.message2').html("");
						$('.message1').html('<i class="fa fa-check"></i> ' + subjectName + ' successfully added.').fadeIn('slow');
						$('#example').DataTable().clear().destroy();
						callTable();
					}
					else if (html==2)
					{                              
						$('.message1').html("");
						$('.message2').html('<i class="fa fa-times"></i> Could not add Subject. Please try again.').fadeIn('slow');
					}
					else if (html==3)
					{                              
						$('.message1').html("");
						$('.message2').html('<i class="fa fa-times"></i> The subject ' + subjectName + ' already exists. Please enter another subject name.').fadeIn('slow');
					}
				}
			});
		}
	}