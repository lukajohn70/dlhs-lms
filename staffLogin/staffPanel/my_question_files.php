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

$filesList = [];
$statusCounts = [
    'all' => 0,
    'Pending' => 0,
    'Downloaded' => 0,
    'Reviewed' => 0,
    'Processed' => 0
];

if ($filesRes) {
    while ($r = $filesRes->fetch_assoc()) {
        $filesList[] = $r;
        $statusCounts['all']++;
        $st = $r['status'] ?? 'Pending';
        if (isset($statusCounts[$st])) {
            $statusCounts[$st]++;
        }
    }
}
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
    <style>
        :root {
            --primary: #00AEEF;
            --success: #0acca2;
            --bg-glass: rgba(255, 255, 255, 0.95);
            --border-glass: rgba(0, 174, 239, 0.1);
        }

        body {
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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

        .status-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            align-items: center;
        }

        .status-pill {
            border: 1px solid #dce4ec;
            background: #fff;
            color: #4a5568;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .status-pill.active {
            background: #0c2340;
            color: #fff;
            border-color: #0c2340;
            box-shadow: 0 4px 12px rgba(12, 35, 64, 0.18);
        }

        .status-pill .count-badge {
            background: rgba(0, 0, 0, 0.08);
            padding: 1px 7px;
            border-radius: 999px;
            font-size: 11px;
        }

        .status-pill.active .count-badge {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        .ext-badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
            margin-right: 6px;
        }
        .ext-pdf { background: #fee2e2; color: #b91c1c; }
        .ext-doc, .ext-docx { background: #dbeafe; color: #1d4ed8; }
        .ext-xls, .ext-xlsx, .ext-csv { background: #d1fae5; color: #047857; }
        .ext-default { background: #f3f4f6; color: #374151; }

        .action-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }

        .dlhs-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0c2340;
            color: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            font-size: 13px;
            font-weight: 500;
            z-index: 99999;
            display: none;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #0acca2;
        }
    </style>
</head>
<body>

    <div id="dlhsToast" class="dlhs-toast">
        <i class="fa fa-check-circle" style="color: #0acca2; font-size: 16px;"></i>
        <span id="dlhsToastText">Status updated successfully!</span>
    </div>

    <section id="container">
        <?php include 'header.php'; ?>
        <?php include 'sideBar.php'; ?>
        <?php session_write_close(); ?>

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
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                <div>
                                    <h4 style="font-weight: 600; color: #0c2340; margin: 0 0 6px 0;"><i class="fa fa-cloud-download"></i> Received Question Documents</h4>
                                    <p class="text-muted" style="font-size: 13px; margin: 0;">
                                        Below is the list of exam question documents routed to you based on your subject allocations. Download, preview, or click <strong>Create Test</strong> to set up the exam directly!
                                    </p>
                                </div>
                            </div>

                            <!-- Status Filter Pills -->
                            <div class="status-filter-bar">
                                <button type="button" class="status-pill active" onclick="filterByStatus('all', this)">
                                    All Files <span class="count-badge" id="count_all"><?php echo $statusCounts['all']; ?></span>
                                </button>
                                <button type="button" class="status-pill" onclick="filterByStatus('Pending', this)">
                                    <i class="fa fa-clock-o" style="color: #f59e0b;"></i> Pending <span class="count-badge" id="count_Pending"><?php echo $statusCounts['Pending']; ?></span>
                                </button>
                                <button type="button" class="status-pill" onclick="filterByStatus('Downloaded', this)">
                                    <i class="fa fa-download" style="color: #3b82f6;"></i> Downloaded <span class="count-badge" id="count_Downloaded"><?php echo $statusCounts['Downloaded']; ?></span>
                                </button>
                                <button type="button" class="status-pill" onclick="filterByStatus('Reviewed', this)">
                                    <i class="fa fa-eye" style="color: #8b5cf6;"></i> Reviewed <span class="count-badge" id="count_Reviewed"><?php echo $statusCounts['Reviewed']; ?></span>
                                </button>
                                <button type="button" class="status-pill" onclick="filterByStatus('Processed', this)">
                                    <i class="fa fa-check-circle" style="color: #10b981;"></i> Processed <span class="count-badge" id="count_Processed"><?php echo $statusCounts['Processed']; ?></span>
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table id="myFilesTable" class="table table-striped table-bordered" style="width: 100%;">
                                    <thead>
                                        <tr style="font-size: 13px;">
                                            <th>S/NO</th>
                                            <th>File details</th>
                                            <th>Target Context</th>
                                            <th>Uploaded By</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $cnt = 1;
                                        foreach ($filesList as $row): 
                                            $ext = strtolower(pathinfo($row['fileName'], PATHINFO_EXTENSION));
                                            $extClass = 'ext-default';
                                            if ($ext === 'pdf') $extClass = 'ext-pdf';
                                            elseif (in_array($ext, ['doc', 'docx'])) $extClass = 'ext-docx';
                                            elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $extClass = 'ext-csv';
                                            $canPreview = in_array($ext, ['pdf', 'txt', 'csv']);
                                            $createTestUrl = 'addTestForm.php?openCreate=1'
                                                . (!empty($row['subjectId']) ? '&presetSubject=' . (int)$row['subjectId'] : '')
                                                . (!empty($row['yearGroupId']) ? '&presetYearGroup=' . (int)$row['yearGroupId'] : '')
                                                . (!empty($row['testType']) ? '&presetType=' . urlencode($row['testType']) : '');
                                        ?>
                                            <tr id="row_<?php echo $row['id']; ?>" data-status="<?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?>" style="font-size: 13px;">
                                                <td><?php echo $cnt++; ?></td>
                                                <td>
                                                    <span class="ext-badge <?php echo $extClass; ?>"><?php echo htmlspecialchars($ext ?: 'FILE'); ?></span>
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
                                                    <select class="form-control input-sm status-select" style="max-width: 125px; font-size: 12px; font-weight: 600;" onchange="updateStatus(<?php echo $row['id']; ?>, this.value)">
                                                        <option value="Pending" <?php if ($row['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                                        <option value="Downloaded" <?php if ($row['status'] === 'Downloaded') echo 'selected'; ?>>Downloaded</option>
                                                        <option value="Reviewed" <?php if ($row['status'] === 'Reviewed') echo 'selected'; ?>>Reviewed</option>
                                                        <option value="Processed" <?php if ($row['status'] === 'Processed') echo 'selected'; ?>>Processed</option>
                                                    </select>
                                                </td>
                                                <td align="center">
                                                    <div class="action-btn-group">
                                                        <a href="../../<?php echo htmlspecialchars($row['filePath']); ?>" class="btn btn-primary btn-xs" onclick="markDownloaded(<?php echo $row['id']; ?>)" download title="Download question file">
                                                            <i class="fa fa-download"></i> Download
                                                        </a>
                                                        <?php if ($canPreview): ?>
                                                        <button type="button" class="btn btn-info btn-xs" onclick="previewQuestionFile('../../<?php echo htmlspecialchars(addslashes($row['filePath'])); ?>', '<?php echo htmlspecialchars(addslashes($row['fileName'])); ?>')" title="Preview in browser">
                                                            <i class="fa fa-eye"></i> Preview
                                                        </button>
                                                        <?php endif; ?>
                                                        <a href="<?php echo htmlspecialchars($createTestUrl); ?>" class="btn btn-success btn-xs" title="Create a test pre-filled with this subject & year group">
                                                            <i class="fa fa-plus-circle"></i> Create Test
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </section>

    <!-- File Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="width: 85%; max-width: 1000px;">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header" style="background: #0c2340; color: #fff;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.9;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="previewModalTitle" style="font-size: 15px; font-weight: 600;"><i class="fa fa-file-text-o"></i> Document Preview</h4>
                </div>
                <div class="modal-body" style="padding: 0; height: 75vh; background: #f1f5f9;">
                    <iframe id="previewFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScripts -->
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="../../datatables/js/jquery.dataTables.min.js"></script>
    <script src="../../datatables/js/dataTables.responsive.min.js"></script>

    <script>
        var currentStatusFilter = 'all';
        var filesTable = null;

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'myFilesTable') return true;
            if (currentStatusFilter === 'all') return true;
            var rowNode = settings.aoData[dataIndex].nTr;
            var rowStatus = $(rowNode).attr('data-status') || 'Pending';
            return rowStatus === currentStatusFilter;
        });

        $(document).ready(function() {
            filesTable = $('#myFilesTable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']]
            });
        });

        function filterByStatus(status, btnEl) {
            currentStatusFilter = status;
            $('.status-pill').removeClass('active');
            $(btnEl).addClass('active');
            if (filesTable) {
                filesTable.draw();
            }
        }

        function showToast(msg, isError) {
            var toast = $('#dlhsToast');
            $('#dlhsToastText').text(msg);
            toast.css('border-left-color', isError ? '#ef4444' : '#0acca2');
            toast.find('i').attr('class', isError ? 'fa fa-exclamation-circle' : 'fa fa-check-circle')
                           .css('color', isError ? '#ef4444' : '#0acca2');
            toast.stop(true, true).fadeIn(200).css('display', 'flex');
            setTimeout(function() {
                toast.fadeOut(300);
            }, 3000);
        }

        function recalculateStatusCounts() {
            var counts = { all: 0, Pending: 0, Downloaded: 0, Reviewed: 0, Processed: 0 };
            if (filesTable) {
                filesTable.rows().nodes().to$().each(function() {
                    var st = $(this).attr('data-status') || 'Pending';
                    counts.all++;
                    if (counts[st] !== undefined) counts[st]++;
                });
            }
            $('#count_all').text(counts.all);
            $('#count_Pending').text(counts.Pending);
            $('#count_Downloaded').text(counts.Downloaded);
            $('#count_Reviewed').text(counts.Reviewed);
            $('#count_Processed').text(counts.Processed);
        }

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
                        $('#row_' + fileId).attr('data-status', status);
                        recalculateStatusCounts();
                        if (filesTable && currentStatusFilter !== 'all') {
                            filesTable.draw(false);
                        }
                        showToast('File status updated to "' + status + '"', false);
                    } else {
                        showToast('Error updating file status.', true);
                    }
                },
                error: function() {
                    showToast('Network error while updating status.', true);
                }
            });
        }

        function markDownloaded(fileId) {
            var select = $('#row_' + fileId + ' select');
            if (select.val() === 'Pending') {
                select.val('Downloaded').trigger('change');
            }
        }

        function previewQuestionFile(fileUrl, fileName) {
            $('#previewModalTitle').html('<i class="fa fa-file-text-o"></i> ' + $('<div>').text(fileName).html());
            $('#previewFrame').attr('src', fileUrl);
            $('#previewModal').modal('show');
        }

        $('#previewModal').on('hidden.bs.modal', function () {
            $('#previewFrame').attr('src', '');
        });
    </script>
</body>
</html>
