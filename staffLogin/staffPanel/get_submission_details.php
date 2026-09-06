<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$staffId = $_SESSION['staffId'];
$submissionId = isset($_POST['submissionId']) ? intval($_POST['submissionId']) : 0;

if ($submissionId <= 0) {
    echo '<div class="alert alert-danger">Invalid submission ID</div>';
    exit();
}

// Get submission details (you may need to adjust table names based on your schema)
// This is a sample implementation - adjust according to your actual database structure
$query = "SELECT 
    fs.*,
    s.firstName, s.surname, s.middleName,
    f.title as assignmentTitle, f.maxMarks, f.instructions,
    f.dueDate
    FROM file_submissions fs
    LEFT JOIN studentlogin s ON fs.studentId = s.studentId
    LEFT JOIN file_uploads f ON fs.fileId = f.fileId
    WHERE fs.submissionId = $submissionId AND f.uploadedBy = $staffId";

$result = $connection->query($query);

if (!$result || $result->num_rows == 0) {
    echo '<div class="alert alert-danger">Submission not found or you don\'t have permission to grade it</div>';
    exit();
}

$submission = $result->fetch_assoc();

// Display submission details
echo '<div class="submission-card panel panel-default">';
echo '<div class="panel-body">';
echo '<h4><i class="fa fa-user"></i> ' . $submission['firstName'] . ' ' . $submission['surname'] . '</h4>';
echo '<p><strong>Assignment:</strong> ' . htmlspecialchars($submission['assignmentTitle']) . '</p>';
echo '<p><strong>Submitted:</strong> ' . date('M d, Y H:i', strtotime($submission['submitted_at'])) . '</p>';

$dueDate = strtotime($submission['dueDate']);
$submittedDate = strtotime($submission['submitted_at']);
if ($submittedDate > $dueDate) {
    $lateBy = round(($submittedDate - $dueDate) / 3600);
    echo '<p><strong>Status:</strong> <span class="label label-danger">Late by ' . $lateBy . ' hours</span></p>';
} else {
    echo '<p><strong>Status:</strong> <span class="label label-success">On Time</span></p>';
}

if ($submission['instructions']) {
    echo '<hr>';
    echo '<p><strong>Assignment Instructions:</strong></p>';
    echo '<p>' . nl2br(htmlspecialchars($submission['instructions'])) . '</p>';
}

if ($submission['studentNotes']) {
    echo '<hr>';
    echo '<p><strong>Student\'s Notes:</strong></p>';
    echo '<p>' . nl2br(htmlspecialchars($submission['studentNotes'])) . '</p>';
}

echo '</div>';
echo '</div>';

// Display submitted file
if ($submission['submittedFilePath']) {
    $fileExt = strtolower(pathinfo($submission['submittedFilePath'], PATHINFO_EXTENSION));
    
    echo '<div class="panel panel-default">';
    echo '<div class="panel-heading"><strong>Submitted File</strong></div>';
    echo '<div class="panel-body" style="text-align: center;">';
    
    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
        echo '<img src="../../' . $submission['submittedFilePath'] . '" style="max-width: 100%; max-height: 400px;" alt="Submission">';
    } elseif ($fileExt === 'pdf') {
        echo '<iframe src="../../' . $submission['submittedFilePath'] . '" style="width: 100%; height: 500px; border: none;"></iframe>';
    } else {
        echo '<p><i class="fa fa-file"></i> ' . htmlspecialchars($submission['submittedFileName']) . '</p>';
        echo '<a href="../../' . $submission['submittedFilePath'] . '" class="btn btn-primary" download>';
        echo '<i class="icon_download"></i> Download Submission';
        echo '</a>';
    }
    
    echo '</div>';
    echo '</div>';
}

// Grading form
echo '<div class="panel panel-default">';
echo '<div class="panel-heading"><strong><i class="icon_check"></i> Grade This Submission</strong></div>';
echo '<div class="panel-body">';
echo '<form id="gradingForm_' . $submissionId . '" onsubmit="event.preventDefault(); submitGrade(' . $submissionId . ');">';

echo '<div class="form-group">';
echo '<label for="gradeInput_' . $submissionId . '">Grade (out of ' . $submission['maxMarks'] . ') *</label>';
echo '<input type="number" class="form-control" id="gradeInput_' . $submissionId . '" min="0" max="' . $submission['maxMarks'] . '" step="0.5" required>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="feedbackInput_' . $submissionId . '">Feedback</label>';
echo '<textarea class="form-control" id="feedbackInput_' . $submissionId . '" rows="5" placeholder="Provide feedback to the student..."></textarea>';
echo '</div>';

echo '<button type="submit" class="btn btn-success btn-lg">';
echo '<i class="icon_check"></i> Submit Grade';
echo '</button>';

echo '</form>';
echo '</div>';
echo '</div>';
?>

