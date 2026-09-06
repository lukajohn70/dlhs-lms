	function clickedSubmit()		//function declaration at click of submit button or automatic submission
	{	
		var selectedAnswer;	//getting value of checked option
		if($('input[name=options]:checked').length > 0)
		{
			selectedAnswer = $("input[name=options]:checked").val();
		}
		else
		{
			selectedAnswer="0";
		}
		$.ajax({
				type: "POST",
				url: "beforeSubmit.php",
				data:  {"selectedAnswer": selectedAnswer},
				dataType: "json",
				cache: false,
				success: function(data1){
					if (data1==1)
					{
						window.location.replace("submit.php");
					}
					else
					{
						alert("something went wrong");
					}
				}
       });
	}
	
	
	var browserMinimize=0;	
	var warned=0;
		
	window.addEventListener('blur', function(){
		browserMinimize=browserMinimize + 1;
	}, false);

	window.addEventListener('focus', function(){		
		
		
		if (browserMinimize==1 && warned==1)
		{
			//setTimeout(' window.location.href = "logout.php"; ',2000);
		}
		else if (browserMinimize==1 && warned==0)
		{
			browserMinimize=browserMinimize-1;
			warned=warned+1;
			$('.messsage2').html('<i class="fa fa-exclamation-triangle"></i>' + ' <b>Warning!</b> Please do not minimize the browser or switch to another window again.');
			setTimeout("$('.messsage2').html('');",10000);
		}
	}, false);
	
