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
$fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : 0;

if ($fileId <= 0) {
    echo '<div class="alert alert-danger">Invalid file ID</div>';
    exit();
}

// Get file details
$query = "SELECT f.*, c.categoryName, s.subjectName 
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          WHERE f.fileId = $fileId";
$result = $connection->query($query);

if (!$result || $result->num_rows == 0) {
    echo '<div class="alert alert-danger">File not found</div>';
    exit();
}

$file = $result->fetch_assoc();
$fileExt = strtolower(pathinfo($file['originalName'], PATHINFO_EXTENSION));

// Display file information
echo '<div class="panel panel-default">';
echo '<div class="panel-body">';
echo '<h4>' . htmlspecialchars($file['title']) . '</h4>';
echo '<hr>';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<p><strong>File Name:</strong> ' . htmlspecialchars($file['originalName']) . '</p>';
echo '<p><strong>Category:</strong> ' . htmlspecialchars($file['categoryName']) . '</p>';
echo '<p><strong>Subject:</strong> ' . htmlspecialchars($file['subjectName']) . '</p>';
echo '<p><strong>Size:</strong> ' . formatFileSize($file['fileSize']) . '</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<p><strong>Uploaded:</strong> ' . date('M d, Y H:i', strtotime($file['created_at'])) . '</p>';
echo '<p><strong>Downloads:</strong> ' . $file['downloadCount'] . '</p>';
echo '<p><strong>Type:</strong> ' . ($file['isAssignment'] ? '<span class="label label-warning">Assignment</span>' : '<span class="label label-info">Resource</span>') . '</p>';
if ($file['dueDate']) {
    echo '<p><strong>Due Date:</strong> ' . date('M d, Y H:i', strtotime($file['dueDate'])) . '</p>';
}
echo '</div>';
echo '</div>';

if ($file['description']) {
    echo '<hr>';
    echo '<p><strong>Description:</strong></p>';
    echo '<p>' . nl2br(htmlspecialchars($file['description'])) . '</p>';
}

echo '</div>';
echo '</div>';

// Preview based on file type
echo '<div class="panel panel-default">';
echo '<div class="panel-heading"><strong>Preview</strong></div>';
echo '<div class="panel-body" style="text-align: center;">';

if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) {
    // Image preview
    echo '<img src="../../' . $file['filePath'] . '" style="max-width: 100%; max-height: 500px;" alt="Preview">';
} elseif ($fileExt === 'pdf') {
    // PDF preview
    echo '<iframe src="../../' . $file['filePath'] . '" style="width: 100%; height: 600px; border: none;"></iframe>';
} elseif (in_array($fileExt, ['mp4', 'webm', 'ogg'])) {
    // Video preview
    echo '<video controls style="max-width: 100%; max-height: 500px;">';
    echo '<source src="../../' . $file['filePath'] . '" type="video/' . $fileExt . '">';
    echo 'Your browser does not support the video tag.';
    echo '</video>';
} elseif (in_array($fileExt, ['mp3', 'wav'])) {
    // Audio preview
    echo '<audio controls style="width: 100%;">';
    echo '<source src="../../' . $file['filePath'] . '" type="audio/' . $fileExt . '">';
    echo 'Your browser does not support the audio tag.';
    echo '</audio>';
} else {
    // No preview available
    echo '<div class="alert alert-info">';
    echo '<i class="fa fa-info-circle"></i> Preview not available for this file type.<br><br>';
    echo '<a href="download_file.php?fileId=' . $fileId . '" class="btn btn-primary">';
    echo '<i class="icon_download"></i> Download File';
    echo '</a>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

