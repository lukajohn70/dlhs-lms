<?php
session_start();
require_once 'userExpiredSession.php';

// Simulate login for testing
$_SESSION['staffLoggedIn'] = true;
$_SESSION['staffLast_login'] = time();

include "../../db_connection/dlhs_db_connection.php";

// Get available tests
$testsQuery = $connection->query("SELECT testId, testName, tableName FROM tests WHERE tableName IS NOT NULL AND tableName != '' ORDER BY testName LIMIT 10");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Simple Question Upload Test</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { margin: 20px; font-family: Arial; }
        .container { max-width: 800px; }
        .form-group { margin-bottom: 15px; }
        .btn { padding: 10px 15px; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <h2>Simple Question Upload Test</h2>
    
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'succ_insert'): ?>
            <div class="alert alert-success">✅ Questions uploaded successfully!</div>
        <?php elseif ($_GET['status'] == 'succ_update'): ?>
            <div class="alert alert-success">✅ Questions updated successfully!</div>
        <?php elseif ($_GET['status'] == 'err'): ?>
            <div class="alert alert-danger">❌ Error uploading questions.</div>
        <?php elseif ($_GET['status'] == 'invalid_file'): ?>
            <div class="alert alert-danger">❌ Please upload a valid CSV file.</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <form method="post" action="uploadQuestions.php" enctype="multipart/form-data">
        <div class="form-group">
            <label><strong>Select Test:</strong></label>
            <select name="testName" class="form-control" required>
                <option value="">Choose a test...</option>
                <?php while ($test = $testsQuery->fetch_assoc()): ?>
                    <option value="<?= $test['testId'] ?>"><?= htmlspecialchars($test['testName']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><strong>Select CSV File:</strong></label>
            <input type="file" name="file" accept=".csv" class="form-control" required>
            <small>Upload a CSV file with columns: Serial No, Question, Option A, Option B, Option C, Option D, Correct Answer, Mark</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Upload Questions</button>
        <a href="downloadQuestionTemplate.php" class="btn btn-secondary">Download Template</a>
    </form>
    
    <hr>
    
    <h3>Manual Question Entry</h3>
    <form id="manualForm">
        <div class="form-group">
            <label>Test:</label>
            <select id="testSelect" class="form-control" required>
                <option value="">Choose a test...</option>
                <?php 
                $testsQuery2 = $connection->query("SELECT testId, testName FROM tests WHERE tableName IS NOT NULL ORDER BY testName LIMIT 10");
                while ($test = $testsQuery2->fetch_assoc()): ?>
                    <option value="<?= $test['testId'] ?>"><?= htmlspecialchars($test['testName']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Question:</label>
            <textarea id="questionText" class="form-control" rows="3" placeholder="Enter your question here" required></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Option A:</label>
                    <input type="text" id="optionA" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Option B:</label>
                    <input type="text" id="optionB" class="form-control" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Option C:</label>
                    <input type="text" id="optionC" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Option D:</label>
                    <input type="text" id="optionD" class="form-control" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label>Correct Answer:</label>
            <select id="correctAnswer" class="form-control" required>
                <option value="">Select correct option</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Mark:</label>
            <input type="number" id="mark" class="form-control" value="1" min="1" required>
        </div>
        
        <button type="submit" class="btn btn-success">Add Question</button>
    </form>
    
    <div id="message" style="margin-top: 15px;"></div>
</div>

<script src="../../libs/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('#manualForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            testId: $('#testSelect').val(),
            question: $('#questionText').val(),
            optionA: $('#optionA').val(),
            optionB: $('#optionB').val(),
            optionC: $('#optionC').val(),
            optionD: $('#optionD').val(),
            selectedValue: $('#correctAnswer').val(),
            mark: $('#mark').val()
        };
        
        $.ajax({
            url: 'addQuestion.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response == '1') {
                    $('#message').html('<div class="alert alert-success">✅ Question added successfully!</div>');
                    $('#manualForm')[0].reset();
                } else if (response == '0') {
                    $('#message').html('<div class="alert alert-danger">❌ Session expired. Please login again.</div>');
                } else if (response == '2') {
                    $('#message').html('<div class="alert alert-danger">❌ Failed to add question. Please try again.</div>');
                } else {
                    $('#message').html('<div class="alert alert-danger">❌ An error occurred: ' + response + '</div>');
                }
            },
            error: function() {
                $('#message').html('<div class="alert alert-danger">❌ Network error. Please try again.</div>');
            }
        });
    });
});
</script>

</body>
</html>