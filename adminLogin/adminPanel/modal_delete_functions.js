// Generate random confirmation code
function generateConfirmationCode() {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	let result = '';
	for (let i = 0; i < 8; i++) {
		result += chars.charAt(Math.floor(Math.random() * chars.length));
	}
	return result;
}

//Delete test
function deleteTest(testId, testName)
{
	$('.message1').html('');
	$('.message2').html('');
	$('.message3').html('');
	$('.message4').html('');
	$('.message5').html('');
	$('.message6').html('');
	$('.message7').html('');
	$('.message8').html('');
	$('.message9').html('');
	$('.message10').html('');
	$('.message11').html('');
	$('.message12').html('');
	
	// Generate unique confirmation code
	var confirmationCode = generateConfirmationCode();
	
	// Store test data and confirmation code for later use
	window.currentDeleteTestId = testId;
	window.currentDeleteTestName = testName;
	window.currentConfirmationCode = confirmationCode;
	window.currentDeleteTestIds = null; // Clear bulk delete data
	
	// Populate modal content
	var modalContent = '<div style="margin-bottom: 20px;">' +
		'<h4 style="color: #dc3545; margin-bottom: 15px;">⚠️ WARNING: Delete Test Confirmation</h4>' +
		'<p style="font-size: 16px; margin-bottom: 10px;"><strong>Test to be deleted:</strong> <span style="color: #dc3545;">' + testName + '</span></p>' +
		'<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 15px;">' +
		'<h5 style="color: #721c24; margin-bottom: 10px;">⚠️ This action will PERMANENTLY DELETE:</h5>' +
		'<ul style="color: #721c24; margin: 0; padding-left: 20px;">' +
		'<li>All test records from the database</li>' +
		'<li>All test questions and options</li>' +
		'<li>All student answers and submissions</li>' +
		'<li>All examinee records and progress</li>' +
		'<li>All test-related data tables</li>' +
		'</ul>' +
		'</div>' +
		'<p style="color: #721c24; font-weight: bold; text-align: center; margin-bottom: 15px;">⚠️ This action cannot be undone!</p>' +
		'<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 15px;">' +
		'<h5 style="color: #856404; margin-bottom: 10px;">🔐 Security Confirmation Required:</h5>' +
		'<p style="color: #856404; margin: 0; text-align: center; font-size: 18px; font-weight: bold;">' +
		'Type this code: <span id="confirmationCodeDisplay" style="color: #dc3545; font-family: monospace; background-color: #f8f9fa; padding: 8px 12px; border-radius: 5px; border: 2px solid #dc3545; letter-spacing: 2px;">' + confirmationCode + '</span></p>' +
		'</div>' +
		'</div>';
	
	document.getElementById('deleteTestModalContent').innerHTML = modalContent;
	
	// Reset form
	document.getElementById('deleteConfirmationInput').value = '';
	document.getElementById('confirmationError').innerHTML = '';
	document.getElementById('deleteModalMessage').innerHTML = '';
	document.getElementById('confirmDeleteBtn').disabled = true;
	
	// Show modal
	document.getElementById('myModal3').style.display = 'block';
}

// Close delete modal
function closeDeleteModal()
{
	document.getElementById('myModal3').style.display = 'none';
	document.getElementById('deleteConfirmationInput').value = '';
	document.getElementById('confirmationError').innerHTML = '';
	document.getElementById('deleteModalMessage').innerHTML = '';
	window.currentDeleteTestId = null;
	window.currentDeleteTestName = null;
	window.currentDeleteTestIds = null;
	window.currentDeleteTestNames = null;
	window.currentConfirmationCode = null;
}

// Confirm delete test (called from modal)
function confirmDeleteTest()
{
	var testId = window.currentDeleteTestId;
	var testName = window.currentDeleteTestName;
	var confirmationCode = window.currentConfirmationCode;
	var userInput = document.getElementById('deleteConfirmationInput').value.trim();
	
	if (userInput === confirmationCode)
	{
		// Show loading message
		document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting test, please wait...';
		document.getElementById('confirmDeleteBtn').disabled = true;
		
		var form_data = 'testId=' + encodeURIComponent(testId);
		  
		$.ajax({
			url: "deleteTest.php",
			type: "POST",
			data: form_data,
			timeout: 30000, // 30 second timeout
			success: function (html) {             
				if (html == 0)	//If session is expired.
				{                              
					window.location.replace("logout.php");
				}
				else if (html == 1)	
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i> Test "' + testName + '" has been successfully deleted!';
					setTimeout(function() {
						closeDeleteModal();
						$('.message5').html("");
						$('.message4').html('<i class="fa fa-check-circle"></i> Test "' + testName + '" has been successfully deleted along with all related data.').fadeIn('slow');
						$('#example').DataTable().clear().destroy();
						callTable();
					}, 2000);
				}
				else if (html == 2)	//If deletion is unsuccessful	
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Could not delete test. Please try again.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
				else if (html == 3)	//Invalid test ID
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Invalid test ID provided.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
				else if (html == 4)	//Test not found
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Test not found in the database.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
			},
			error: function(xhr, status, error) {
				if (status === 'timeout') {
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> The deletion request timed out. Please try again.';
				} else {
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> An error occurred while deleting the test. Please check your connection and try again.';
				}
				document.getElementById('confirmDeleteBtn').disabled = false;
			}
		});
	}
	else
	{
		document.getElementById('confirmationError').innerHTML = 'Please type the confirmation code exactly as shown above to confirm deletion.';
	}
}

//Delete selected tests (bulk delete)
function deleteSelectedTests()
{
	$('.message4').html('');
	$('.message5').html('');
	
	var selectedTests = [];
	var selectedTestNames = [];
	var checkboxes = document.querySelectorAll("input[name='testSelectCheckBox[]']:checked");

	for (var i = 0; i < checkboxes.length; i++) {
		selectedTests.push(checkboxes[i].value);
		// Get test name from the table row
		var row = checkboxes[i].closest('tr');
		var testName = row.cells[2].textContent.trim();
		selectedTestNames.push(testName);
	}
	
	if (selectedTests.length == 0)
	{
		$('.message5').html('<i class="fa fa-info-circle"></i> Please select at least one test before deleting');
		return;
	}
	
	// Generate unique confirmation code
	var confirmationCode = generateConfirmationCode();
	
	// Store test data and confirmation code for later use
	window.currentDeleteTestIds = selectedTests;
	window.currentDeleteTestNames = selectedTestNames;
	window.currentConfirmationCode = confirmationCode;
	window.currentDeleteTestId = null; // Clear individual delete data
	
	// Populate modal content for bulk deletion
	var testCount = selectedTests.length;
	var modalContent = '<div style="margin-bottom: 20px;">' +
		'<h4 style="color: #dc3545; margin-bottom: 15px;">⚠️ WARNING: Delete Multiple Tests Confirmation</h4>' +
		'<p style="font-size: 16px; margin-bottom: 10px;"><strong>Tests to be deleted:</strong> <span style="color: #dc3545;">' + testCount + ' test(s)</span></p>' +
		'<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 10px; margin-bottom: 15px;">' +
		'<h5 style="color: #856404; margin-bottom: 10px;">Selected Tests:</h5>' +
		'<ul style="color: #856404; margin: 0; padding-left: 20px;">';
		
	selectedTestNames.forEach(function(name, index) {
		modalContent += '<li>' + (index + 1) + '. ' + name + '</li>';
	});
	
	modalContent += '</ul></div>' +
		'<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin-bottom: 15px;">' +
		'<h5 style="color: #721c24; margin-bottom: 10px;">⚠️ This action will PERMANENTLY DELETE:</h5>' +
		'<ul style="color: #721c24; margin: 0; padding-left: 20px;">' +
		'<li>All test records from the database</li>' +
		'<li>All test questions and options</li>' +
		'<li>All student answers and submissions</li>' +
		'<li>All examinee records and progress</li>' +
		'<li>All test-related data tables</li>' +
		'</ul>' +
		'</div>' +
		'<p style="color: #721c24; font-weight: bold; text-align: center; margin-bottom: 15px;">⚠️ This action cannot be undone!</p>' +
		'<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 15px;">' +
		'<h5 style="color: #856404; margin-bottom: 10px;">🔐 Security Confirmation Required:</h5>' +
		'<p style="color: #856404; margin: 0; text-align: center; font-size: 18px; font-weight: bold;">' +
		'Type this code: <span id="confirmationCodeDisplay" style="color: #dc3545; font-family: monospace; background-color: #f8f9fa; padding: 8px 12px; border-radius: 5px; border: 2px solid #dc3545; letter-spacing: 2px;">' + confirmationCode + '</span></p>' +
		'</div>' +
		'</div>';
	
	document.getElementById('deleteTestModalContent').innerHTML = modalContent;
	
	// Reset form
	document.getElementById('deleteConfirmationInput').value = '';
	document.getElementById('confirmationError').innerHTML = '';
	document.getElementById('deleteModalMessage').innerHTML = '';
	document.getElementById('confirmDeleteBtn').disabled = true;
	
	// Show modal
	document.getElementById('myModal3').style.display = 'block';
}

// Confirm delete selected tests (called from modal)
function confirmDeleteSelectedTests()
{
	var selectedTests = window.currentDeleteTestIds;
	var selectedTestNames = window.currentDeleteTestNames;
	var confirmationCode = window.currentConfirmationCode;
	var testCount = selectedTests.length;
	var userInput = document.getElementById('deleteConfirmationInput').value.trim();
	
	if (userInput === confirmationCode)
	{
		// Show loading message
		document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting tests, please wait...';
		document.getElementById('confirmDeleteBtn').disabled = true;
		
		//organize the data properly
		var form_data = 'selectedTests=' + encodeURIComponent(selectedTests.join(','));

		$.ajax({
			url: "deleteSelectedTests.php",
			type: "POST",     
			data: form_data,
			timeout: 60000, // 60 second timeout for bulk operations
			success: function (html) {             
				if (html == 0)	//If session is expired.
				{                              
					window.location.replace("logout.php");
				}
				else if (html == 1)	//If tests successfully deleted
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i> ' + testCount + ' test(s) have been successfully deleted!';
					setTimeout(function() {
						closeDeleteModal();
						$('.message5').html("");
						$('.message4').html('<i class="fa fa-check-circle"></i> ' + testCount + ' test(s) have been successfully deleted from the system.').fadeIn('slow');
						$('#example').DataTable().clear().destroy();
						callTable();
					}, 2000);
				}
				else if (html == 2)	//If some tests deleted, some failed
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Some tests were deleted, but some failed. Please check the logs and try again.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
				else if (html == 3)	//If no tests were deleted
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> No tests were deleted. Please verify the test IDs and try again.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
				else 	//If deletion is unsuccessful	
				{                              
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> Could not delete selected tests. Please try again.';
					document.getElementById('confirmDeleteBtn').disabled = false;
				}
			},
			error: function(xhr, status, error) {
				if (status === 'timeout') {
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> The deletion request timed out. Please try again with fewer tests.';
				} else {
					document.getElementById('deleteModalMessage').innerHTML = '<i class="fa fa-exclamation-triangle"></i> An error occurred while deleting tests. Please check your connection and try again.';
				}
				document.getElementById('confirmDeleteBtn').disabled = false;
			}
		});
	}
	else
	{
		document.getElementById('confirmationError').innerHTML = 'Please type the confirmation code exactly as shown above to confirm deletion.';
	}
}
