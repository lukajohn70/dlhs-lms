# Delete Students Based on Criteria - User Guide

## Overview
The delete students functionality allows administrators to remove students from the system based on specific criteria (year group and class). This feature includes enhanced security measures and comprehensive data cleanup.

## How to Use

### 1. Access the Delete Function
- Navigate to the Add Student Form page (`addStudentForm.php`)
- Click on "Delete student based on criteria" link in the header panel

### 2. Select Criteria
- Choose the **Year Group** from the dropdown
- Select the **Class** from the dropdown (populated based on year group)
- The system will automatically load students matching your criteria

### 3. Review Students
- The system displays all students matching your criteria in a table
- You can see student details including:
  - Surname, First Name, Middle Name
  - Gender, Admission Number, Class, Email
  - Passport photo (if available)

### 4. Select Students for Deletion
- Use individual checkboxes to select specific students
- Use "Select all" checkbox to select all displayed students
- The delete button shows the count of selected students and is enabled only when students are selected

### 5. Confirm Deletion
- Click the "Delete selected students" button
- A detailed confirmation dialog will appear showing:
  - Number of students to be deleted
  - List of selected students
  - Warning about permanent deletion
  - What data will be deleted (records, photos, academic data)
- **Type "DELETE"** to confirm the action

### 6. Monitor Progress
- The system shows loading indicators during the process
- Success/error messages are displayed via toast notifications
- The modal closes automatically after successful deletion

## Security Features

### Enhanced Confirmation
- Requires typing "DELETE" to confirm (not just clicking OK)
- Shows detailed warning about permanent deletion
- Lists exactly which students will be deleted

### Data Cleanup
The system automatically removes:
- Student records from `studentlogin` table
- Associated login logs from `studentloginlog` table
- Test answers from `testanswers` table
- Passport photo files from the server

### Input Validation
- Validates student IDs are numeric and positive
- Uses prepared statements to prevent SQL injection
- Checks for empty or invalid input

### Error Handling
- Comprehensive error messages for different scenarios
- Timeout handling for long operations
- Audit logging of deletion attempts
- Graceful handling of file deletion failures

## Technical Implementation

### Backend (`deleteSelectedStudents.php`)
- Uses prepared statements for security
- Validates input parameters
- Deletes related data in correct order
- Logs all operations for audit trail
- Returns appropriate status codes

### Frontend (JavaScript)
- Enhanced confirmation dialogs
- Real-time button state management
- Loading indicators and progress feedback
- Toast notifications for user feedback
- Timeout handling for AJAX requests

## Return Codes
- `0`: Session expired or no students selected
- `1`: All students deleted successfully
- `2`: Some students deleted, some failed
- `3`: No students were deleted

## Best Practices
1. **Always verify** the student list before confirming deletion
2. **Use specific criteria** (year group + class) to avoid accidentally deleting wrong students
3. **Review the confirmation dialog** carefully before typing "DELETE"
4. **Check the success message** to confirm the operation completed
5. **Keep backups** of important data before bulk deletions

## Troubleshooting
- If no students appear: Verify year group and class selection
- If deletion fails: Check server logs for detailed error messages
- If timeout occurs: Try with smaller batches of students
- If confirmation doesn't work: Ensure you type "DELETE" exactly (case-sensitive)

## Support
For technical issues or questions about the delete functionality, contact the system administrator.

