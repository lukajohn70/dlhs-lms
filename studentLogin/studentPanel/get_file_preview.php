<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

$isStudent = isset($_SESSION['studentLoggedIn']) && $_SESSION['studentLoggedIn'] === "yes";
if (!$isStudent) {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$studentId = (int)$_SESSION['studentId'];
$fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : (isset($_GET['fileId']) ? intval($_GET['fileId']) : 0);

if ($fileId <= 0) {
    echo '<div class="alert alert-danger">Invalid file ID</div>';
    exit();
}

// Fetch file information
$query = "SELECT f.*, c.categoryName, s.subjectName,
                 CASE 
                     WHEN f.uploaderType = 'admin' THEN 'School Administration'
                     WHEN f.uploaderType = 'student' THEN CONCAT(stu.surname, ' ', stu.firstName)
                     ELSE CONCAT(st.surname, ' ', st.firstName)
                 END as senderName,
                 CASE
                     WHEN f.uploaderType = 'student' THEN CONCAT(targetSt.surname, ' ', targetSt.firstName)
                     ELSE ''
                 END as recipientTeacherName
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN stafflogin st ON (f.uploaderType = 'staff' AND f.uploadedBy = st.staffId)
          LEFT JOIN studentlogin stu ON (f.uploaderType = 'student' AND f.uploadedBy = stu.studentId)
          LEFT JOIN stafflogin targetSt ON f.targetStaffId = targetSt.staffId
          WHERE f.fileId = $fileId AND f.isActive = 1";

$result = $connection->query($query);

if (!$result || $result->num_rows == 0) {
    echo '<div class="alert alert-danger">File not found</div>';
    exit();
}

$file = $result->fetch_assoc();

// Student access check:
// 1. If uploaded by a student, it MUST be uploaded by this specific student (NO STUDENT-TO-STUDENT SHARING)
// 2. If uploaded by staff/admin, it MUST be published to students and match their class or all
$hasAccess = false;
$uploaderType = $file['uploaderType'] ?? 'staff';

if ($uploaderType === 'student') {
    $hasAccess = ((int)$file['uploadedBy'] === $studentId);
} else {
    if ($file['isPublishedToStudents'] == 1) {
        if ($file['uploadedFor'] === 'all') {
            $hasAccess = true;
        } elseif ($file['uploadedFor'] === 'specific_class') {
            $studentQuery = "SELECT classId FROM studentlogin WHERE studentId = $studentId";
            $sqRes = $connection->query($studentQuery);
            if ($sqRes && $sqRes->num_rows > 0) {
                $sr = $sqRes->fetch_assoc();
                if ((int)$file['targetClassId'] === (int)$sr['classId']) {
                    $hasAccess = true;
                }
            }
        } elseif ($file['uploadedFor'] === 'specific_students') {
            $assignCheck = $connection->query("SELECT 1 FROM file_student_assignments WHERE fileId = $fileId AND studentId = $studentId LIMIT 1");
            $hasAccess = ($assignCheck && $assignCheck->num_rows > 0);
        }
    }
}

if (!$hasAccess) {
    echo '<div class="alert alert-danger">Access denied. You do not have permission to view this document.</div>';
    exit();
}

$isYouTube = ($file['fileType'] === 'youtube' || strpos($file['filePath'], 'youtube.com') !== false || strpos($file['filePath'], 'youtu.be') !== false);
$fileExt = strtolower(pathinfo($file['originalName'], PATHINFO_EXTENSION));

function dlhsFormatFileSize($bytes) {
    if ($bytes <= 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>

<div class="panel panel-default" style="border: 1px solid rgba(0, 174, 239, 0.2); border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
    <div class="panel-body" style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
            <div>
                <h4 style="margin: 0 0 6px 0; font-weight: 700; color: #020b1a;">
                    <?php echo htmlspecialchars($file['title']); ?>
                </h4>
                <p style="margin: 0; color: #64748b; font-size: 13px;">
                    <?php echo htmlspecialchars($file['originalName']); ?>
                </p>
            </div>
            <div>
                <?php if ($uploaderType === 'admin'): ?>
                    <span class="label label-primary" style="background: linear-gradient(135deg, #0077b6 0%, #00AEEF 100%); font-size: 11px; padding: 5px 10px;"><i class="fa fa-shield"></i> Administration Notice</span>
                <?php elseif ($uploaderType === 'student'): ?>
                    <span class="label label-success" style="background: #10b981; font-size: 11px; padding: 5px 10px;"><i class="fa fa-check"></i> Submitted to <?php echo htmlspecialchars($file['recipientTeacherName'] ?: 'Teacher'); ?></span>
                <?php else: ?>
                    <span class="label label-info" style="background: #0284c7; font-size: 11px; padding: 5px 10px;"><i class="fa fa-graduation-cap"></i> Teacher Study Material</span>
                <?php endif; ?>
            </div>
        </div>

        <hr style="margin: 15px 0;">

        <div class="row" style="font-size: 13px;">
            <div class="col-sm-6">
                <p><strong><i class="fa fa-user"></i> <?php echo ($uploaderType === 'student') ? 'Submitted By:' : 'Shared By:'; ?></strong> <?php echo htmlspecialchars($file['senderName']); ?></p>
                <?php if ($uploaderType === 'student' && !empty($file['recipientTeacherName'])): ?>
                    <p><strong><i class="fa fa-user-circle"></i> Recipient Teacher:</strong> <?php echo htmlspecialchars($file['recipientTeacherName']); ?></p>
                <?php endif; ?>
                <p><strong><i class="fa fa-book"></i> Subject:</strong> <?php echo htmlspecialchars($file['subjectName'] ?: 'General'); ?></p>
            </div>
            <div class="col-sm-6">
                <p><strong><i class="fa fa-calendar"></i> Date:</strong> <?php echo date('M d, Y - h:i A', strtotime($file['created_at'])); ?></p>
                <p><strong><i class="fa fa-hdd-o"></i> Size:</strong> <?php echo $isYouTube ? 'YouTube Streaming' : dlhsFormatFileSize($file['fileSize']); ?></p>
            </div>
        </div>

        <?php if (!empty($file['description'])): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-top: 10px; font-size: 13px;">
                <strong><i class="fa fa-commenting-o"></i> Notes / Description:</strong>
                <p style="margin: 5px 0 0 0; color: #334155; white-space: pre-line;"><?php echo htmlspecialchars($file['description']); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Preview Container -->
<div class="panel panel-default" style="border: 1px solid rgba(0, 174, 239, 0.2); border-radius: 10px; overflow: hidden;">
    <div class="panel-heading" style="background: #f8fafc; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fa fa-eye"></i> Content Preview</span>
        <?php if (!$isYouTube): ?>
            <a href="download_file.php?fileId=<?php echo $fileId; ?>" class="btn btn-primary btn-xs" style="background: #00AEEF; border: none; border-radius: 4px; padding: 5px 12px; font-weight: 600;">
                <i class="fa fa-download"></i> Download File
            </a>
        <?php endif; ?>
    </div>
    <div class="panel-body" style="text-align: center; padding: 20px; background: #fafafa;">
        <?php if ($isYouTube): ?>
            <?php
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $file['filePath'], $ytMatches);
            $ytId = $ytMatches[1] ?? '';
            ?>
            <?php if ($ytId): ?>
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                    <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($ytId); ?>" style="position: absolute; top:0; left: 0; width: 100%; height: 100%; border:0;" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($file['filePath']); ?>" target="_blank" class="btn btn-danger">
                    <i class="fa fa-youtube-play"></i> Watch on YouTube
                </a>
            <?php endif; ?>

        <?php elseif (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <img src="../../<?php echo htmlspecialchars($file['filePath']); ?>" style="max-width: 100%; max-height: 520px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" alt="Preview">

        <?php elseif ($fileExt === 'pdf'): ?>
            <iframe src="../../<?php echo htmlspecialchars($file['filePath']); ?>" style="width: 100%; height: 580px; border: 1px solid #cbd5e1; border-radius: 8px;"></iframe>

        <?php elseif (in_array($fileExt, ['mp4', 'webm', 'ogg'])): ?>
            <video controls style="max-width: 100%; max-height: 480px; border-radius: 8px;">
                <source src="../../<?php echo htmlspecialchars($file['filePath']); ?>" type="video/<?php echo $fileExt; ?>">
                Your browser does not support HTML5 video.
            </video>

        <?php elseif (in_array($fileExt, ['mp3', 'wav', 'aac'])): ?>
            <audio controls style="width: 100%; margin: 20px 0;">
                <source src="../../<?php echo htmlspecialchars($file['filePath']); ?>" type="audio/<?php echo $fileExt; ?>">
                Your browser does not support HTML5 audio.
            </audio>

        <?php else: ?>
            <div style="padding: 30px; background: white; border-radius: 8px; border: 1px dashed #cbd5e1; max-width: 480px; margin: 0 auto;">
                <i class="fa fa-file-text-o" style="font-size: 54px; color: #00AEEF; margin-bottom: 15px;"></i>
                <h5 style="font-weight: 600; color: #020b1a; margin-bottom: 8px;">Standard Document File</h5>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
                    This document (<strong>.<?php echo strtoupper($fileExt); ?></strong>) is ready to download to your device.
                </p>
                <a href="download_file.php?fileId=<?php echo $fileId; ?>" class="btn btn-primary" style="background: #00AEEF; border: none; padding: 10px 22px; font-weight: 600; border-radius: 6px;">
                    <i class="fa fa-download"></i> Download <?php echo htmlspecialchars($file['originalName']); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
