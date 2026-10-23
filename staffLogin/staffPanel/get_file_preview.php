<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

$isStaff = isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === "yes";
$isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === "yes";

if (!$isStaff && !$isAdmin) {
    echo '<div class="alert alert-danger">Not authorized</div>';
    exit();
}

$fileId = isset($_POST['fileId']) ? intval($_POST['fileId']) : (isset($_GET['fileId']) ? intval($_GET['fileId']) : 0);

if ($fileId <= 0) {
    echo '<div class="alert alert-danger">Invalid file ID</div>';
    exit();
}

$query = "SELECT f.*, c.categoryName, s.subjectName,
                 CASE 
                     WHEN f.uploaderType = 'admin' THEN 'School Administration'
                     ELSE CONCAT(st.surname, ' ', st.firstName)
                 END as senderName,
                 st.username as senderEmail
          FROM file_uploads f 
          LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
          LEFT JOIN subjects s ON f.subjectId = s.subjectId 
          LEFT JOIN stafflogin st ON f.uploadedBy = st.staffId
          WHERE f.fileId = $fileId";

$result = $connection->query($query);

if (!$result || $result->num_rows == 0) {
    echo '<div class="alert alert-danger">File not found</div>';
    exit();
}

$file = $result->fetch_assoc();
$isYouTube = ($file['fileType'] === 'youtube' || strpos($file['filePath'], 'youtube.com') !== false || strpos($file['filePath'], 'youtu.be') !== false);
$fileExt = strtolower(pathinfo($file['originalName'], PATHINFO_EXTENSION));

function dlhsFormatFileSize($bytes) {
    if ($bytes <= 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Audience label
$audienceLabel = 'All Students';
if ($file['uploadedFor'] === 'admin') {
    $audienceLabel = '🏛️ School Administration (Admin)';
} elseif ($file['uploadedFor'] === 'all_teachers') {
    $audienceLabel = '👨‍🏫 All Teachers (Staff Broadcast)';
} elseif ($file['uploadedFor'] === 'subject_teachers') {
    $audienceLabel = '📚 Teachers of ' . htmlspecialchars($file['subjectName'] ?: 'Subject Department');
} elseif ($file['uploadedFor'] === 'specific_teachers') {
    $audienceLabel = '👤 Specific Teachers';
} elseif ($file['uploadedFor'] === 'specific_class') {
    $audienceLabel = '🏫 Specific Class';
} elseif ($file['uploadedFor'] === 'specific_students') {
    $audienceLabel = '🎯 Specific Students';
}
?>

<div class="panel panel-default" style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
    <div class="panel-body" style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
            <div>
                <h4 style="margin: 0 0 6px 0; font-weight: 700; color: #1e293b;">
                    <?php echo htmlspecialchars($file['title']); ?>
                </h4>
                <p style="margin: 0; color: #64748b; font-size: 13px;">
                    <?php echo htmlspecialchars($file['originalName']); ?>
                </p>
            </div>
            <div>
                <?php if ($file['uploadedFor'] === 'admin'): ?>
                    <?php if ($file['adminStatus'] === 'reviewed'): ?>
                        <span class="label label-info" style="font-size: 11px; padding: 5px 10px;"><i class="fa fa-check"></i> Reviewed by Admin</span>
                    <?php elseif ($file['adminStatus'] === 'acknowledged'): ?>
                        <span class="label label-success" style="font-size: 11px; padding: 5px 10px;"><i class="fa fa-check-circle"></i> Acknowledged</span>
                    <?php else: ?>
                        <span class="label label-warning" style="font-size: 11px; padding: 5px 10px;"><i class="fa fa-clock-o"></i> Pending Admin Review</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <hr style="margin: 15px 0;">

        <div class="row" style="font-size: 13px;">
            <div class="col-sm-6">
                <p><strong><i class="fa fa-user"></i> Shared By:</strong> <?php echo htmlspecialchars($file['senderName']); ?></p>
                <p><strong><i class="fa fa-bullseye"></i> Shared With:</strong> <?php echo $audienceLabel; ?></p>
                <p><strong><i class="fa fa-tag"></i> Category:</strong> <?php echo htmlspecialchars($file['categoryName'] ?: 'General'); ?></p>
                <p><strong><i class="fa fa-book"></i> Subject:</strong> <?php echo htmlspecialchars($file['subjectName'] ?: 'General / Not Specified'); ?></p>
            </div>
            <div class="col-sm-6">
                <p><strong><i class="fa fa-calendar"></i> Date:</strong> <?php echo date('M d, Y - h:i A', strtotime($file['created_at'])); ?></p>
                <p><strong><i class="fa fa-hdd-o"></i> Size:</strong> <?php echo $isYouTube ? 'YouTube Streaming' : dlhsFormatFileSize($file['fileSize']); ?></p>
                <p><strong><i class="fa fa-download"></i> Downloads:</strong> <?php echo (int)$file['downloadCount']; ?> times</p>
            </div>
        </div>

        <?php if (!empty($file['description'])): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-top: 10px; font-size: 13px;">
                <strong><i class="fa fa-commenting-o"></i> Description / Notes:</strong>
                <p style="margin: 5px 0 0 0; color: #334155; white-space: pre-line;"><?php echo htmlspecialchars($file['description']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($file['adminNotes'])): ?>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin-top: 10px; font-size: 13px;">
                <strong style="color: #1e40af;"><i class="fa fa-shield"></i> Administration Feedback / Notes:</strong>
                <p style="margin: 5px 0 0 0; color: #1e3a8a; white-space: pre-line;"><?php echo htmlspecialchars($file['adminNotes']); ?></p>
                <?php if ($file['adminReviewedAt']): ?>
                    <small style="color: #60a5fa; display: block; margin-top: 4px;">Reviewed on <?php echo date('M d, Y h:i A', strtotime($file['adminReviewedAt'])); ?></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Preview Container -->
<div class="panel panel-default" style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
    <div class="panel-heading" style="background: #f8fafc; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fa fa-eye"></i> Content Preview</span>
        <?php if (!$isYouTube): ?>
            <a href="../../download_file.php?fileId=<?php echo $fileId; ?>" class="btn btn-primary btn-xs" style="border-radius: 4px;">
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
                <i class="fa fa-file-text-o" style="font-size: 54px; color: #94a3b8; margin-bottom: 15px;"></i>
                <h5 style="font-weight: 600; color: #334155; margin-bottom: 8px;">Standard Document File</h5>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
                    This document (<strong>.<?php echo strtoupper($fileExt); ?></strong>) can be downloaded directly to your device.
                </p>
                <a href="../../download_file.php?fileId=<?php echo $fileId; ?>" class="btn btn-success" style="padding: 10px 22px; font-weight: 600; border-radius: 6px;">
                    <i class="fa fa-download"></i> Download <?php echo htmlspecialchars($file['originalName']); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
