# Delete Tests Feature - User Guide

## Overview
The delete tests functionality allows administrators to remove tests from the system with comprehensive data cleanup. This feature includes both individual test deletion and bulk deletion capabilities with enhanced security measures.

## How to Use

### 1. Access the Delete Tests Feature
- Navigate to the All Tests Form page (`allTestsForm.php`)
- The delete functionality is integrated into the existing test management interface

### 2. Individual Test Deletion
- In the test table, locate the test you want to delete
- Click the red trash icon (🗑️) in the ACTION column
- A **modal confirmation dialog** will appear showing:
  - Test name and details
  - Warning about permanent deletion
  - What data will be deleted (questions, answers, examinee records, etc.)
  - **Unique 8-character confirmation code** (e.g., "A7K9M2X1")
  - Input field for confirmation code
- **Type the exact confirmation code** displayed in the modal to enable the delete button
- Click the **"Delete"** button to confirm the action

### 3. Bulk Test Deletion
- Use the checkboxes in the first column to select multiple tests
- Use "Select All" checkbox to select all displayed tests
- The "Delete Selected Tests" button shows the count of selected tests
- Click the "Delete Selected Tests" button
- A **modal confirmation dialog** will appear showing:
  - Number of tests to be deleted
  - List of selected test names
  - Comprehensive warning about permanent deletion
  - **Unique 8-character confirmation code** (e.g., "B3L8N4Y2")
  - Input field for confirmation code
- **Type the exact confirmation code** displayed in the modal to enable the delete button
- Click the **"Delete"** button to confirm the action

## Security Features

### Enhanced Confirmation
- **Modal-based confirmation** instead of basic browser prompts
- **Dynamic confirmation codes** that change each time:
  - Unique 8-character alphanumeric codes (e.g., "A7K9M2X1")
  - Codes are randomly generated for each deletion attempt
  - Prevents memorization of static confirmation phrases
- **Real-time validation** - delete button only enables when correct code is typed
- Shows detailed warnings about permanent data loss
- Lists exactly which tests and data will be deleted
- **Visual feedback** with color-coded warnings and success messages
- **Enhanced security** - each deletion requires a unique, non-reusable confirmation code

### Data Cleanup
The system automatically removes:
- Test records from `tests` table
- Questions table (dynamic table created for each test)
- Examinees table (student test progress and submissions)
- Answers table (student answers and scores)
- All related test data and progress

### Input Validation
- Validates test IDs are numeric and positive
- Uses prepared statements to prevent SQL injection
- Checks for empty or invalid input
- Handles missing or corrupted test data gracefully

### Error Handling
- Comprehensive error messages for different scenarios
- Timeout handling for long operations (30s individual, 60s bulk)
- Audit logging of all deletion attempts
- Graceful handling of partial failures in bulk operations
- Database transaction rollback on errors

## Dynamic Confirmation System

### How It Works
- **Random Code Generation**: Each deletion attempt generates a unique 8-character code
- **Character Set**: Uses uppercase letters (A-Z) and numbers (0-9) for clarity
- **Session-Based**: Each modal session has its own unique confirmation code
- **One-Time Use**: Codes are valid only for the current deletion attempt
- **Visual Display**: Codes are prominently displayed in a styled box for easy reading

### Security Benefits
- **Prevents Automation**: Scripts cannot use static phrases to bypass confirmation
- **User Intent Verification**: Requires active user interaction to read and type the code
- **Session Isolation**: Each deletion attempt is independent with its own code
- **Audit Trail**: Each code is unique, making it easier to track deletion attempts

## Technical Implementation

### Backend (`deleteTest.php` & `deleteSelectedTests.php`)
- Uses prepared statements for security
- Validates input parameters
- Implements database transactions for atomicity
- Deletes related tables in correct order
- Logs all operations for audit trail
- Returns appropriate status codes

### Frontend (JavaScript)
- Enhanced confirmation dialogs with detailed warnings
- Real-time button state management for bulk operations
- Loading indicators and progress feedback
- Comprehensive error messages and timeout handling
- Checkbox selection management with "Select All" functionality

## Return Codes
- `0`: Session expired or no tests selected
- `1`: All tests deleted successfully
- `2`: Some tests deleted, some failed (bulk operations)
- `3`: Invalid test ID or no tests were deleted
- `4`: Test not found in database

## Database Structure
Each test creates three dynamic tables:
1. **Questions Table**: Contains test questions, options, and correct answers
2. **Examinees Table**: Tracks student test progress, time remaining, scores
3. **Answers Table**: Stores student answers and submission data

## Best Practices
1. **Always verify** the test list before confirming deletion
2. **Use individual deletion** for single tests to avoid mistakes
3. **Use bulk deletion carefully** - verify all selected tests are correct
4. **Read the confirmation code carefully** before typing it
5. **Type the confirmation code exactly** as displayed (case-sensitive)
6. **Keep backups** of important test data before bulk deletions
7. **Monitor system logs** for any deletion errors or issues
8. **Don't share confirmation codes** - each code is unique to your session

## Troubleshooting
- If no tests appear: Check if tests exist in the database
- If deletion fails: Check server logs for detailed error messages
- If timeout occurs: Try with smaller batches of tests
- If confirmation doesn't work: Ensure you type the exact confirmation code (case-sensitive)
- If bulk deletion partially fails: Check which specific tests failed in the logs
- If confirmation code doesn't match: Refresh the page and try again (codes are session-specific)
- If delete button stays disabled: Make sure you've typed the confirmation code exactly as shown

## Performance Considerations
- Individual deletions are fast (typically < 5 seconds)
- Bulk deletions may take longer (up to 60 seconds for large batches)
- System automatically handles timeouts and provides feedback
- Database transactions ensure data consistency

## Audit Trail
All test deletions are logged with:
- Admin user who performed the deletion
- Test ID and name
- Timestamp of deletion
- List of tables that were deleted
- Any errors encountered during deletion

## Support
For technical issues or questions about the delete tests functionality, contact the system administrator or check the server error logs for detailed information.

## Security Notes
- Only administrators with proper session authentication can delete tests
- All deletion operations are logged for security auditing
- Confirmation phrases prevent accidental deletions
- Database transactions ensure no partial deletions occur
- Prepared statements prevent SQL injection attacks
