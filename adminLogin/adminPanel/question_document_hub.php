<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

// Set up directories
$uploadDir = '../../uploads/question_docs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle file upload
$uploadResults = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['question_files'])) {
    $files = $_FILES['question_files'];
    $uploadedBy = is_numeric($_SESSION['adminId']) ? (int)$_SESSION['adminId'] : 0;
    
    // Fetch Year Groups
    $yearGroupQuery = $connection->query("SELECT * FROM yeargroup");
    $yearGroups = [];
    while ($yg = $yearGroupQuery->fetch_assoc()) {
        $yearGroups[] = $yg;
    }
    
    // Fetch Subjects
    $subjectQuery = $connection->query("SELECT * FROM subjects");
    $subjects = [];
    while ($sb = $subjectQuery->fetch_assoc()) {
        $subjects[] = $sb;
    }
    
    // Clean string function
    function cleanStringForMatch($str) {
        $str = preg_replace('/[^A-Z0-9]/', '', strtoupper($str));
        return $str;
    }

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $uploadResults[] = [
                'name' => $files['name'][$i],
                'status' => 'error',
                'message' => 'Upload error code: ' . $files['error'][$i]
            ];
            continue;
        }

        $origName = $files['name'][$i];
        $ext = pathinfo($origName, PATHINFO_EXTENSION);
        
        // Generate safe unique filename
        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($origName, PATHINFO_FILENAME)) . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $destPath = $uploadDir . $safeName;
        
        if (move_uploaded_uploaded_file_or_move($files['tmp_name'][$i], $destPath)) {
            // Parser Engine
            $cleanName = strtoupper(pathinfo($origName, PATHINFO_FILENAME));
            
            // 1. Year Group Parsing
            $yearGroupId = null;
            $ygNameParsed = '';
            if (preg_match('/\b(JS|JSS)\s*1\b|\bBASIC\s*7\b/i', $cleanName)) {
                $ygNameParsed = 'BASIC 7';
            } elseif (preg_match('/\b(JS|JSS)\s*2\b|\bBASIC\s*8\b/i', $cleanName)) {
                $ygNameParsed = 'BASIC 8';
            } elseif (preg_match('/\b(JS|JSS)\s*3\b|\bBASIC\s*9\b/i', $cleanName)) {
                $ygNameParsed = 'BASIC 9';
            } elseif (preg_match('/\b(SS|SSS)\s*1\b/i', $cleanName)) {
                $ygNameParsed = 'SS 1';
            } elseif (preg_match('/\b(SS|SSS)\s*2\b/i', $cleanName)) {
                $ygNameParsed = 'SS 2';
            } elseif (preg_match('/\b(SS|SSS)\s*3\b/i', $cleanName)) {
                $ygNameParsed = 'SS 3';
            }
            
            if ($ygNameParsed !== '') {
                foreach ($yearGroups as $yg) {
                    if (strcasecmp(trim($yg['yearGroupName']), $ygNameParsed) === 0) {
                        $yearGroupId = (int)$yg['yearGroupId'];
                        break;
                    }
                }
            }
            
            // 2. Subject Parsing
            $subjectId = null;
            $bestMatchSubject = null;
            $highestScore = 0;
            
            foreach ($subjects as $sb) {
                $subjectName = $sb['subjectName'];
                $cleanSubject = cleanStringForMatch($subjectName);
                $cleanSubjectAlt = str_replace('STUDIES', 'STD', $cleanSubject);
                $cleanFilename = cleanStringForMatch($cleanName);
                
                $score = 0;
                
                if (strpos($cleanFilename, $cleanSubject) !== false) {
                    $score = strlen($cleanSubject);
                } elseif (strpos($cleanFilename, $cleanSubjectAlt) !== false) {
                    $score = strlen($cleanSubjectAlt);
                } else {
                    // Try individual words
                    $words = preg_split('/[^A-Z0-9]+/', strtoupper($subjectName));
                    $matchedWords = 0;
                    $totalWords = 0;
                    foreach ($words as $word) {
                        if (strlen($word) < 3 || in_array($word, ['AND', 'THE', 'FOR', 'WITH', 'OF', 'L1', 'L2'])) continue;
                        $totalWords++;
                        if (strpos($cleanFilename, $word) !== false) {
                            $matchedWords++;
                        }
                    }
                    if ($totalWords > 0 && $matchedWords == $totalWords) {
                        $score = $matchedWords * 5;
                    }
                }
                
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $bestMatchSubject = $sb;
                }
            }
            
            if ($bestMatchSubject !== null) {
                $subjectId = (int)$bestMatchSubject['subjectId'];
            }
            
            // 3. Term Parsing
            $term = null;
            if (preg_match('/\b(1ST|FIRST)\s*TERM\b/i', $cleanName)) {
                $term = '1st Term';
            } elseif (preg_match('/\b(2ND|SECOND)\s*TERM\b/i', $cleanName)) {
                $term = '2nd Term';
            } elseif (preg_match('/\b(3RD|THIRD)\s*TERM\b/i', $cleanName)) {
                $term = '3rd Term';
            }
            
            // 4. Test Type Parsing
            $testType = null;
            if (preg_match('/\bCAT\s*([0-9]+)\b/i', $cleanName, $m)) {
                $testType = 'CAT ' . $m[1];
            } elseif (preg_match('/\bEXAM\b/i', $cleanName)) {
                $testType = 'EXAM';
            } elseif (preg_match('/\bMOCK\b/i', $cleanName)) {
                $testType = 'MOCK';
            }
            
            // 5. Academic Year Parsing
            $academicYear = null;
            if (preg_match('/\b([0-9]{4}\s*-\s*[0-9]{4})\b/', $cleanName, $m)) {
                $academicYear = str_replace(' ', '', $m[1]);
            }
            
            // Find assigned teachers
            $teachersMatched = [];
            if ($subjectId !== null && $yearGroupId !== null) {
                $assignmentQuery = "SELECT DISTINCT sta.teacherId 
                                    FROM subject_teacher_assignment sta
                                    JOIN classes c ON sta.classId = c.classId
                                    WHERE sta.subjectId = $subjectId AND c.classYearGroup = $yearGroupId";
                $assignmentRes = $connection->query($assignmentQuery);
                while ($ar = $assignmentRes->fetch_assoc()) {
                    $teachersMatched[] = (int)$ar['teacherId'];
                }
            }
            
            // DB Save
            $successInsert = 0;
            if (count($teachersMatched) > 0) {
                foreach ($teachersMatched as $tid) {
                    $stmt = $connection->prepare("INSERT INTO teacher_question_files (fileName, filePath, yearGroupId, subjectId, teacherId, testType, term, academicYear, uploadedBy, uploadDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $relPath = 'uploads/question_docs/' . $safeName;
                    $stmt->bind_param("ssiiisssi", $origName, $relPath, $yearGroupId, $subjectId, $tid, $testType, $term, $academicYear, $uploadedBy);
                    if ($stmt->execute()) {
                        $successInsert++;
                    }
                    $stmt->close();
                }
            } else {
                // Insert with NULL teacher so Admin can manually assign
                $stmt = $connection->prepare("INSERT INTO teacher_question_files (fileName, filePath, yearGroupId, subjectId, teacherId, testType, term, academicYear, uploadedBy, uploadDate) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, NOW())");
                $relPath = 'uploads/question_docs/' . $safeName;
                $stmt->bind_param("ssiisssi", $origName, $relPath, $yearGroupId, $subjectId, $testType, $term, $academicYear, $uploadedBy);
                $stmt->execute();
                $stmt->close();
                $successInsert++;
            }
            
            $uploadResults[] = [
                'name' => $origName,
                'status' => 'success',
                'parsed' => [
                    'yearGroup' => $ygNameParsed !== '' ? $ygNameParsed : 'Unresolved',
                    'subject' => $bestMatchSubject !== null ? $bestMatchSubject['subjectName'] : 'Unresolved',
                    'term' => $term !== null ? $term : 'Unresolved',
                    'testType' => $testType !== null ? $testType : 'Unresolved',
                    'academicYear' => $academicYear !== null ? $academicYear : 'Unresolved',
                    'teachers' => count($teachersMatched)
                ]
            ];
        } else {
            $uploadResults[] = [
                'name' => $origName,
                'status' => 'error',
                'message' => 'Failed to save to destination.'
            ];
        }
    }
}

// Helper function to handle upload cleanly
function move_uploaded_uploaded_file_or_move($tmp, $dest) {
    return move_uploaded_file($tmp, $dest);
}

// Handle Manual Assignment Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manual_assign') {
    $fileId = (int)$_POST['fileId'];
    $teacherId = (int)$_POST['teacherId'];
    
    $update = $connection->query("UPDATE teacher_question_files SET teacherId = $teacherId WHERE id = $fileId");
    echo json_encode(['status' => $update ? 'success' : 'error']);
    exit;
}

// Handle File Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_file') {
    $fileId = (int)$_POST['fileId'];
    
    // Get file path
    $res = $connection->query("SELECT filePath FROM teacher_question_files WHERE id = $fileId");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $path = '../../' . $row['filePath'];
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    $delete = $connection->query("DELETE FROM teacher_question_files WHERE id = $fileId");
    echo json_encode(['status' => $delete ? 'success' : 'error']);
    exit;
}

// Fetch all staff logins for dropdown
$staffRes = $connection->query("SELECT staffId, CONCAT(surname, ' ', firstName) as fullName FROM stafflogin ORDER BY surname");
$teachersList = [];
while ($t = $staffRes->fetch_assoc()) {
    $teachersList[] = $t;
}

// Fetch current allocated files list
$filesQuery = "SELECT tqf.*, yg.yearGroupName, s.subjectName, 
               CONCAT(sl.surname, ' ', sl.firstName) as teacherName,
               adm.username as adminName
               FROM teacher_question_files tqf
               LEFT JOIN yeargroup yg ON tqf.yearGroupId = yg.yearGroupId
               LEFT JOIN subjects s ON tqf.subjectId = s.subjectId
               LEFT JOIN stafflogin sl ON tqf.teacherId = sl.staffId
               LEFT JOIN admintable adm ON tqf.uploadedBy = adm.adminId
               ORDER BY tqf.uploadDate DESC";
$filesRes = $connection->query($filesQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Document Hub | DLHS Admin</title>

    <!-- CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #00AEEF;
            --primary-dark: #008cc0;
            --success: #0acca2;
            --bg-glass: rgba(255, 255, 255, 0.95);
            --border-glass: rgba(0, 174, 239, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f5f8fa;
        }

        .glass-card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 174, 239, 0.05);
            padding: 24px;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            box-shadow: 0 12px 40px rgba(0, 174, 239, 0.08);
            transform: translateY(-2px);
        }

        .hub-title {
            color: #0c2340;
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 28px;
        }

        .dropzone-container {
            border: 2px dashed rgba(0, 174, 239, 0.3);
            background: rgba(0, 174, 239, 0.02);
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dropzone-container:hover {
            background: rgba(0, 174, 239, 0.05);
            border-color: var(--primary);
        }

        .dropzone-icon {
            font-size: 48px;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .file-list-preview {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 15px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            display: none;
        }

        .preview-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #f5f8fa;
            font-size: 14px;
        }

        .badge-parsed {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-teacher { background-color: #ebf5ff; color: #007bff; }
        .badge-subject { background-color: #eafaf1; color: #2ecc71; }
        .badge-yg { background-color: #fef9e7; color: #f1c40f; }

        .teacher-select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 13px;
            max-width: 180px;
        }
    </style>
</head>
<body>

    <section id="container">
        <?php include 'header.php'; ?>
        <?php include 'sideBar.php'; ?>

        <section id="main-content">
            <section class="wrapper">
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header"><i class="fa fa-folder-open"></i> Question Document Hub</h3>
                        <ol class="breadcrumb">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-folder-open"></i>Document Hub</li>
                            <a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <!-- Batch Upload Card -->
                    <div class="col-lg-4">
                        <div class="glass-card">
                            <h4 style="font-weight: 600; color: #0c2340; margin-bottom: 20px;"><i class="fa fa-cloud-upload"></i> Upload & Auto-Allocate</h4>
                            <p class="text-muted" style="font-size: 13px; margin-bottom: 20px;">
                                Drag and drop your exam question PDF or Word documents here. The system will parse the filenames and automatically assign the questions directly to the target teachers!
                            </p>
                            
                            <form action="" method="post" enctype="multipart/form-data" id="uploadForm">
                                <div class="dropzone-container" onclick="document.getElementById('fileInput').click()">
                                    <div class="dropzone-icon"><i class="fa fa-cloud-upload"></i></div>
                                    <h5 style="font-weight: 600; margin-bottom: 4px;">Click to Select Files</h5>
                                    <span class="text-muted" style="font-size: 12px;">Supports .docx, .doc and .pdf files</span>
                                    <input type="file" name="question_files[]" id="fileInput" multiple accept=".docx,.doc,.pdf" style="display: none;" onchange="handleFileSelect(this)">
                                </div>
                                
                                <div class="file-list-preview" id="fileListPreview"></div>
                                
                                <button type="submit" class="btn btn-block btn-info" id="submitBtn" style="margin-top: 20px; font-weight: 600; padding: 12px; border-radius: 8px; display: none;">
                                    <i class="fa fa-magic"></i> Parse & Allocate Files
                                </button>
                            </form>
                        </div>
                        
                        <!-- Upload Results Card -->
                        <?php if (count($uploadResults) > 0): ?>
                        <div class="glass-card" style="border-left: 4px solid var(--success);">
                            <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fa fa-check-circle text-success"></i> Upload Session Summary</h5>
                            <div style="max-height: 250px; overflow-y: auto;">
                                <?php foreach ($uploadResults as $res): ?>
                                    <div style="padding: 10px 0; border-bottom: 1px solid #f5f8fa; font-size: 13px;">
                                        <strong><?php echo htmlspecialchars($res['name']); ?></strong><br>
                                        <?php if ($res['status'] === 'success'): ?>
                                            <span class="badge badge-success"><i class="fa fa-check"></i> Success</span>
                                            <div style="margin-top: 5px; font-size: 11px; display: flex; flex-wrap: wrap; gap: 4px;">
                                                <span class="badge-parsed badge-yg">YG: <?php echo htmlspecialchars($res['parsed']['yearGroup']); ?></span>
                                                <span class="badge-parsed badge-subject">Subj: <?php echo htmlspecialchars($res['parsed']['subject']); ?></span>
                                                <span class="badge-parsed badge-teacher">Teachers Assigned: <?php echo $res['parsed']['teachers']; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fa fa-times"></i> Error</span>
                                            <small class="text-danger"><?php echo htmlspecialchars($res['message']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Database Allocated List -->
                    <div class="col-lg-8">
                        <div class="glass-card">
                            <h4 style="font-weight: 600; color: #0c2340; margin-bottom: 20px;"><i class="fa fa-list"></i> Auto-Allocated Question Documents</h4>
                            
                            <div class="table-responsive">
                                <table id="filesTable" class="table table-striped table-bordered" style="width: 100%;">
                                    <thead>
                                        <tr style="font-size: 13px;">
                                            <th>S/NO</th>
                                            <th>Filename</th>
                                            <th>Parsed Context</th>
                                            <th>Assigned Teacher</th>
                                            <th>Status</th>
                                            <th>Uploaded By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $cnt = 1;
                                        while ($row = $filesRes->fetch_assoc()): 
                                        ?>
                                            <tr id="row_<?php echo $row['id']; ?>" style="font-size: 13px;">
                                                <td><?php echo $cnt++; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['fileName']); ?></strong><br>
                                                    <small class="text-muted"><i class="fa fa-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($row['uploadDate'])); ?></small>
                                                </td>
                                                <td>
                                                    <div style="display: grid; gap: 4px;">
                                                        <span><span class="badge badge-warning" style="font-size: 10px;">YG</span> <?php echo htmlspecialchars($row['yearGroupName'] ? $row['yearGroupName'] : 'Unresolved'); ?></span>
                                                        <span><span class="badge badge-success" style="font-size: 10px;">Subj</span> <?php echo htmlspecialchars($row['subjectName'] ? $row['subjectName'] : 'Unresolved'); ?></span>
                                                        <span><span class="badge badge-info" style="font-size: 10px;">Exam</span> <?php echo htmlspecialchars($row['testType'] ? $row['testType'] : 'N/A') . ' (' . htmlspecialchars($row['term'] ? $row['term'] : 'N/A') . ')'; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select class="teacher-select" onchange="updateTeacherAssignment(<?php echo $row['id']; ?>, this.value)">
                                                        <option value="0">-- Not Assigned --</option>
                                                        <?php foreach ($teachersList as $teach): ?>
                                                            <option value="<?php echo $teach['staffId']; ?>" <?php if ((int)$row['teacherId'] === (int)$teach['staffId']) echo 'selected'; ?>>
                                                                <?php echo htmlspecialchars($teach['fullName']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if ($row['teacherId'] === null): ?>
                                                        <br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> Manual assign required</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td align="center">
                                                    <?php if ($row['status'] === 'Pending'): ?>
                                                        <span class="label label-warning">Pending</span>
                                                    <?php elseif ($row['status'] === 'Downloaded'): ?>
                                                        <span class="label label-info">Downloaded</span>
                                                    <?php else: ?>
                                                        <span class="label label-success"><?php echo htmlspecialchars($row['status']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars((string)($row['adminName'] ?? $row['uploadedBy'] ?? 'Admin')); ?></td>
                                                <td align="center">
                                                    <div class="btn-group">
                                                        <a href="../../<?php echo htmlspecialchars($row['filePath']); ?>" class="btn btn-xs btn-primary" download title="Download"><i class="fa fa-download"></i></a>
                                                        <button class="btn btn-xs btn-danger" onclick="deleteFile(<?php echo $row['id']; ?>)" title="Delete"><i class="fa fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </section>

    <!-- JavaScripts -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="../../datatables/js/jquery.dataTables.min.js"></script>
    <script src="../../datatables/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#filesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'desc']]
            });
        });

        function handleFileSelect(input) {
            var preview = document.getElementById('fileListPreview');
            var submitBtn = document.getElementById('submitBtn');
            preview.innerHTML = '';
            
            if (input.files.length > 0) {
                preview.style.display = 'block';
                submitBtn.style.display = 'block';
                for (var i = 0; i < input.files.length; i++) {
                    var name = input.files[i].name;
                    var ext = name.split('.').pop().toLowerCase();
                    var icon = 'fa-file-o';
                    if (ext === 'pdf') {
                        icon = 'fa-file-pdf-o text-danger';
                    } else if (ext === 'docx' || ext === 'doc') {
                        icon = 'fa-file-word-o text-primary';
                    }
                    
                    var item = document.createElement('div');
                    item.className = 'preview-file-item';
                    item.innerHTML = '<span><i class="fa ' + icon + '"></i> ' + name + '</span><span class="text-muted">' + (input.files[i].size / 1024).toFixed(1) + ' KB</span>';
                    preview.appendChild(item);
                }
            } else {
                preview.style.display = 'none';
                submitBtn.style.display = 'none';
            }
        }

        function updateTeacherAssignment(fileId, teacherId) {
            $.ajax({
                url: 'question_document_hub.php',
                type: 'POST',
                data: {
                    action: 'manual_assign',
                    fileId: fileId,
                    teacherId: teacherId
                },
                dataType: 'JSON',
                success: function(res) {
                    if (res.status === 'success') {
                        // Success toast
                        alert('Teacher assignment updated successfully!');
                        window.location.reload();
                    } else {
                        alert('Error updating teacher assignment.');
                    }
                }
            });
        }

        function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this question document? This cannot be undone.')) return;
            
            $.ajax({
                url: 'question_document_hub.php',
                type: 'POST',
                data: {
                    action: 'delete_file',
                    fileId: fileId
                },
                dataType: 'JSON',
                success: function(res) {
                    if (res.status === 'success') {
                        $('#row_' + fileId).fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error deleting file.');
                    }
                }
            });
        }
    </script>
</body>
</html>
