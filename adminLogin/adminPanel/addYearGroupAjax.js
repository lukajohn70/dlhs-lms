	function addYearGroup()
	{
		$('.message1').html("");
		$('.message2').html("");
				
		var section = document.getElementById('section').value;
		var yearGroup = $('input[name=yearGroup]').val();	
		
		if (section == "")
		{
			$('.message2').html('<i class="fa fa-info-circle"></i> Please select section')
		}
		else if (yearGroup == "")
		{
			$('.message2').html('<i class="fa fa-info-circle"></i> Please enter name of Year group')
		}
		else
		{
			$.ajax({
				url: "addYearGroup.php",
				type: "POST",       
				data: {section:section, yearGroup:yearGroup},    
				success: function (html) {             								
					if (html==0) {                              
						 window.location.replace("logout.php");
					}
					else if (html==1) 
					{                              
						$('.message2').html("");
						$('.message1').html('<i class="fa fa-check"></i> ' + yearGroup + ' successfully added.').fadeIn('slow');
						$('#example').DataTable().clear().destroy();
						callTable();
					}
					else if (html==2)
					{                              
						$('.message1').html("");
						$('.message2').html('<i class="fa fa-times"></i> Could not add Year Group. Please try again.').fadeIn('slow');
					}
					else if (html==3)
					{                              
						$('.message1').html("");
						$('.message2').html('<i class="fa fa-times"></i> The Year group ' + yearGroup + ' already exists. Please enter another Year Group.').fadeIn('slow');
					}
				}
			});
		}
	}