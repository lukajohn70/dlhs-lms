								   	
	$("select#test").change(function(){
		$("#yearGroup")[0].selectedIndex = 0;
	});
	
	//Fetching class for year group change.
	$("select#yearGroup").change(function(){
		var selectedYearGroup = $("#yearGroup option:selected").val();
		var theYearGroup=$("#yearGroup option:selected").text();
		$.ajax({
					url: 'fetchStudents.php',
					type: 'POST',
					data: { theYearGroup : yearGroup, theStudentClass : studentClass, theTestName : testName}
					//dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						//alert(len)						
					}
			});
	});
		

	function CheckUncheckAll()
	{
	   var  selectAllCheckbox=document.getElementById("checkUncheckAll");
		if(selectAllCheckbox.checked==true)
		{
			var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].checked = true;
			}
		}
		else
		{
			var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].checked = false;
			}
		}
	}
   
   