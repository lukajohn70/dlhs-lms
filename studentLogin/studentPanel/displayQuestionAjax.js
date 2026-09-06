	function gotToQuestion(progressValue)	//If the progress button is clicked
	{
		var selectedAnswer;	//getting value of checked option
		if($('input[name=options]:checked').length > 0)
		{
			selectedAnswer = $("input[name=options]:checked").val();
		}
		else
		{
			selectedAnswer=0;
		}
		var currentVisible=$("#currentNumber").text();
		$.ajax({
			   type: "POST",
			   url: "fetchProgress.php",
			   data:  {"selectedAnswer": selectedAnswer, "progressValue": progressValue},
			   dataType: "json",
			   cache: false,
			   success: function(data1){
					
					$('#theQuestion').html(data1.q);	//getting question
					$('#optionA').html(data1.a);		//getting option A
					$('#optionB').html(data1.b);		//getting option B
					$('#optionC').html(data1.c);		//getting option C
					$('#optionD').html(data1.d);		//getting option D
					$('#previous').show();	
					var currentNumber=data1.k;
					var preSelectedOption=data1.g;
					var remainingTime=data1.h;
					var greenFlag=data1.i;
					var logoutStatus=data1.j;
					var logoutStatus1=data1.m;
					if((logoutStatus == 1) || (logoutStatus1 == 0) || (logoutStatus1 == 2))
					{
						window.location.href = "submit.php";
					}
					
					//getting the  number of the current question
					var TotalNumbOfQuestions=data1.f;	//getting the total number of questions
					$('#currentNumber').html(currentNumber);
					if ((currentNumber == TotalNumbOfQuestions))
					{
						$('#previous').show();
						$('#next').hide();
					}
					else if (currentNumber==1)
					{
						$('#next').show();
						$('#previous').hide();
					}
					else
					{
						$('#next').show();
						$('#previous').show();
					}
					
					if ( preSelectedOption == "z")
					{
						$('input[name=options]').prop('checked',false);
					}
					else if( preSelectedOption == "A")
					{
						document.getElementById("a").checked = true;
					}
					else if( preSelectedOption == "B")
					{
						document.getElementById("b").checked = true;
					}
					else if( preSelectedOption == "C")
					{
						document.getElementById("c").checked = true;
					}
					else if( preSelectedOption == "D")
					{
						document.getElementById("d").checked = true;
					}
					if (greenFlag==1)
					{
						$("#p"+(currentVisible-1)).css({'background':'green', 'color':'white'});
					}
					//clearInterval(i);
					//countDown(remainingTime);
					
				}
       });
	}
	
	function displayQuestion(testId, totalQuestions)	//function declaration at load of page
	{		
		$('#previous').hide(); //Hiding the previous button
		
		$.ajax({
			   type: "POST",
			   url: "fetchQuestion.php",
			   data:  {"testId":  testId},
			   dataType: "json",
			   cache: false,
			   success: function(data1){
					$('#theQuestion').html(data1.q);	//getting question
					$('#optionA').html(data1.a);		//getting option A
					$('#optionB').html(data1.b);		//getting option B
					$('#optionC').html(data1.c);		//getting option C
					$('#optionD').html(data1.d);		//getting option D
					$('#testName').html(data1.g +': '); //getting test Name and subject name
						
					$('#currentNumber').html(1);
					$('#totalQuestions').html(totalQuestions);
					var preSelectedOption=data1.h;
					if ( preSelectedOption == "z")
					{
						$('input[name=options]').prop('checked',false);
					}
					else if( preSelectedOption == "A")
					{
						document.getElementById("a").checked = true;
					}
					else if( preSelectedOption == "B")
					{
						document.getElementById("b").checked = true;
					}
					else if( preSelectedOption == "C")
					{
						document.getElementById("c").checked = true;
					}
					else if( preSelectedOption == "D")
					{
						document.getElementById("d").checked = true;
					}
				}
       });
	}	
	
	function clickedNext()		//function declaration at click of next button
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
			   url: "fetchNext.php",
			   data:  {"selectedAnswer": selectedAnswer},
			   dataType: "json",
			   cache: false,
			   success: function(data1){
					
					$('#theQuestion').html(data1.q);	//getting option A
					$('#optionA').html(data1.a);		//getting option A
					$('#optionB').html(data1.b);		//getting option B
					$('#optionC').html(data1.c);		//getting option C
					$('#optionD').html(data1.d);		//getting option D
					$('#previous').show();	
					var currentNumber=data1.f;
					var preSelectedOption=data1.h;
					var remainingTime=data1.j;
					var logoutStatus=data1.k;
					var logoutStatus1=data1.m;
					if((logoutStatus == 1) || (logoutStatus1 == 0) || (logoutStatus1 == 2))
					{
						window.location.href = "submit.php";
					}
					
					//getting the  number of the current question
					var TotalNumbOfQuestions=data1.g;	//getting the total number of questions
					$('#currentNumber').html(currentNumber);
					if (currentNumber==TotalNumbOfQuestions)
					{
						$('#next').hide();
					}
					if ( preSelectedOption == "z")
					{
						$('input[name=options]').prop('checked',false);
					}
					else if( preSelectedOption == "A")
					{
						document.getElementById("a").checked = true;
					}
					else if( preSelectedOption == "B")
					{
						document.getElementById("b").checked = true;
					}
					else if( preSelectedOption == "C")
					{
						document.getElementById("c").checked = true;
					}
					else if( preSelectedOption == "D")
					{
						document.getElementById("d").checked = true;
					}
					if (selectedAnswer!=="0")
					{
						$("#p"+(currentNumber-2)).css({'background':'green', 'color':'white'});
					}
					//clearInterval(i);
					//countDown(remainingTime);
					
				}
       });
	}	
	
	function clickedPrevious()		//function declaration at click of previous page
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
		
		$('#next').show();
		$.ajax({
			   type: "POST",
			   url: "fetchPrevious.php",
			   data:  {"selectedAnswer": selectedAnswer},
			   dataType: "json",
			   cache: false,
			   success: function(data1){
			   
					$('#theQuestion').html(data1.q);	//getting option A
					$('#optionA').html(data1.a);		//getting option A
					$('#optionB').html(data1.b);		//getting option B
					$('#optionC').html(data1.c);		//getting option C
					$('#optionD').html(data1.d);		//getting option D
					$('#previous').show();	
					var currentNumber=data1.k;
					var preSelectedOption=data1.g;
					var remainingTime=data1.h;
					var logoutStatus=data1.j;
					var logoutStatus1=data1.m;
					if((logoutStatus == 1) || (logoutStatus1 == 0) || (logoutStatus1 == 2))
					{
						window.location.href = "submit.php";
					}
					
					//getting the  number of the current question
					var TotalNumbOfQuestions=data1.f;	//getting the total number of questions
					$('#currentNumber').html(currentNumber);
					if (currentNumber==1)
					{
						$('#previous').hide();
					}
					if ( preSelectedOption == "z")
					{
						$('input[name=options]').prop('checked',false);
					}
					else if( preSelectedOption == "A")
					{
						document.getElementById("a").checked = true;
					}
					else if( preSelectedOption == "B")
					{
						document.getElementById("b").checked = true;
					}
					else if( preSelectedOption == "C")
					{
						document.getElementById("c").checked = true;
					}
					else if( preSelectedOption == "D")
					{
						document.getElementById("d").checked = true;
					}
					if (selectedAnswer!=="0")
					{
						$("#p"+(currentNumber)).css({'background':'green', 'color':'white'});
					}					
				}
       });
	}	