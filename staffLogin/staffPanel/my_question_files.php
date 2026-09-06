<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) {
    header('location:../index.php');
    exit;
}

include "../../db_connection/dlhs_db_connection.php";

$teacherId = (int)$_SESSION['staffId'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $fileId = (int)$_POST['fileId'];
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    // Security check: Make sure this file belongs to the teacher
    $chk = $connection->query("SELECT id FROM teacher_question_files WHERE id = $fileId AND teacherId = $teacherId");
    if ($chk && $chk->num_rows > 0) {
        $update = $connection->query("UPDATE teacher_question_files SET status = '$status' WHERE id = $fileId");
        echo json_encode(['status' => $update ? 'success' : 'error']);
    } else {
        echo json_encode(['status' => 'unauthorized']);
    }
    exit;
}

// Fetch this teacher's question files
$query = "SELECT tqf.*, yg.yearGroupName, s.subjectName,
          adm.username as adminName
          FROM teacher_question_files tqf
          LEFT JOIN yeargroup yg ON tqf.yearGroupId = yg.yearGroupId
          LEFT JOIN subjects s ON tqf.subjectId = s.subjectId
          LEFT JOIN admintable adm ON tqf.uploadedBy = adm.adminId
          WHERE tqf.teacherId = $teacherId
          ORDER BY tqf.uploadDate DESC";
$filesRes = $connection->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Question Files | DLHS Staff</title>

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

        .btn-status-toggle {
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 20px;
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
                        <h3 class="page-header"><i class="fa fa-files-o"></i> My Question Files</h3>
                        <ol class="breadcrumb">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-files-o"></i>My Question Files</li>
                            <a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
                        </ol>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="glass-card">
                            <h4 style="font-weight: 600; color: #0c2340; margin-bottom: 20px;"><i class="fa fa-cloud-download"></i> Received Question Documents</h4>
                            <p class="text-muted" style="font-size: 13px; margin-bottom: 20px;">
                                Below is the list of exam question documents parsed and automatically routed to you based on your subject allocations. Please download them, compile them into your tests, and update their statuses to let administrators know!
                            </p>

                            <div class="table-responsive">
                                <table id="myFilesTable" class="table table-striped table-bordered" style="width: 100%;">
                                    <thead>
                                        <tr style="font-size: 13px;">
                                            <th>S/NO</th>
                                            <th>File details</th>
                                            <th>Target Context</th>
                                            <th>Uploaded By</th>
                                            <th>Status</th>
                                            <th>Action</th>
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
                                                <td><?php echo htmlspecialchars((string)($row['adminName'] ?? $row['uploadedBy'] ?? 'Admin')); ?></td>
                                                <td align="center">
                                                    <select class="form-control input-sm" style="max-width: 120px; font-size: 12px;" onchange="updateStatus(<?php echo $row['id']; ?>, this.value)">
                                                        <option value="Pending" <?php if ($row['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                                        <option value="Downloaded" <?php if ($row['status'] === 'Downloaded') echo 'selected'; ?>>Downloaded</option>
                                                        <option value="Reviewed" <?php if ($row['status'] === 'Reviewed') echo 'selected'; ?>>Reviewed</option>
                                                        <option value="Processed" <?php if ($row['status'] === 'Processed') echo 'selected'; ?>>Processed</option>
                                                    </select>
                                                </td>
                                                <td align="center">
                                                    <a href="../../<?php echo htmlspecialchars($row['filePath']); ?>" class="btn btn-primary btn-sm" onclick="markDownloaded(<?php echo $row['id']; ?>)" download>
                                                        <i class="fa fa-download"></i> Download File
                                                    </a>
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
            $('#myFilesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'desc']]
            });
        });

        function updateStatus(fileId, status) {
            $.ajax({
                url: 'my_question_files.php',
                type: 'POST',
                data: {
                    action: 'update_status',
                    fileId: fileId,
                    status: status
                },
                dataType: 'JSON',
                success: function(res) {
                    if (res.status === 'success') {
                        // Success toast or sound can go here
                    } else {
                        alert('Error updating file status.');
                    }
                }
            });
        }

        function markDownloaded(fileId) {
            // Automatically select Downloaded in the select options
            var select = $('#row_' + fileId + ' select');
            if (select.val() === 'Pending') {
                select.val('Downloaded').trigger('change');
            }
        }
    </script>
</body>
</html>
