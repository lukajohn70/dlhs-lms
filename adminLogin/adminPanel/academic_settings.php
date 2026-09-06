<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit();
}
include "../../db_connection/dlhs_db_connection.php";

// Preload data for server-side rendering fallback
$sessions = [];
$terms = [];
$examTypes = [];

$sessionQuery = "SELECT * FROM academic_sessions ORDER BY startDate DESC";
if ($sessionResult = $connection->query($sessionQuery)) {
    while ($row = $sessionResult->fetch_assoc()) {
        $sessions[] = $row;
    }
}

$termQuery = "SELECT * FROM academic_terms ORDER BY isDefault DESC, displayOrder ASC, termName ASC";
if ($termResult = $connection->query($termQuery)) {
    while ($row = $termResult->fetch_assoc()) {
        $terms[] = $row;
    }
}

$examQuery = "SELECT * FROM exam_types ORDER BY isDefault DESC, displayOrder ASC, examTypeName ASC";
if ($examResult = $connection->query($examQuery)) {
    while ($row = $examResult->fetch_assoc()) {
        $examTypes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Academic Settings | DLHS</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
    <link href="fontAwesome/css/brands.css" rel="stylesheet">
    <link href="fontAwesome/css/solid.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <style>
        .settings-panel { margin-bottom: 30px; }
        .settings-panel .panel-heading { display: flex; justify-content: space-between; align-items: center; }
        .badge-default { background-color: #777; }
        .badge-active { background-color: #28a745; }
        .badge-inactive { background-color: #dc3545; }
        .badge-default-flag { background-color: #ffc107; color: #000; }
        .form-inline .form-group { margin-right: 15px; }
    </style>
</head>
<body>
<section id="container" class="">
    <?php include 'header.php'; ?>
    <?php include 'sideBar.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-cogs"></i> Academic Settings</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li><i class="fa fa-cogs"></i> Settings</li>
                        <li>Academic Settings</li>
                    </ol>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                Use this page to define the academic sessions, terms, and exam types that teachers must choose when creating tests. Only the active/current records appear in teachers' forms and filters. Use the display order value if you need to force a custom sorting; otherwise leave it at 0.
            </div>

            <div class="settings-panel panel panel-default">
                <div class="panel-heading">
                    <span><i class="fa fa-calendar"></i> Academic Sessions</span>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#sessionModal"><i class="fa fa-plus"></i> Add Session</button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="sessionsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Session Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Current</th>
                                    <th>Display Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $index => $session): ?>
                                    <tr data-id="<?php echo (int) $session['sessionId']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($session['sessionName']); ?></td>
                                        <td><?php echo htmlspecialchars($session['startDate']); ?></td>
                                        <td><?php echo htmlspecialchars($session['endDate']); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($session['isActive'] ?? 1) ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo ($session['isActive'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($session['isCurrentSession'] == 1): ?>
                                                <span class="badge badge-default-flag">Current</span>
                                            <?php else: ?>
                                                <button class="btn btn-link btn-xs set-current-session" data-id="<?php echo (int) $session['sessionId']; ?>">Set Current</button>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($session['displayOrder']) ? (int) $session['displayOrder'] : 0; ?></td>
                                        <td>
                                            <button class="btn btn-xs btn-info edit-session" data-session='<?php echo json_encode($session); ?>'><i class="fa fa-pencil"></i></button>
                                            <button class="btn btn-xs btn-warning toggle-session" data-id="<?php echo (int) $session['sessionId']; ?>" data-active="<?php echo (int) ($session['isActive'] ?? 1); ?>">
                                                <?php echo ($session['isActive'] ?? 1) ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="settings-panel panel panel-default">
                <div class="panel-heading">
                    <span><i class="fa fa-flag"></i> Academic Terms</span>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#termModal"><i class="fa fa-plus"></i> Add Term</button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="termsTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Term Name</th>
                                    <th>Status</th>
                                    <th>Current</th>
                                    <th>Display Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($terms as $index => $term): ?>
                                    <tr data-id="<?php echo (int) $term['termId']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($term['termName']); ?></td>
                                        <td><span class="badge <?php echo $term['isActive'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $term['isActive'] ? 'Active' : 'Inactive'; ?></span></td>
                                        <td><?php echo $term['isDefault'] ? '<span class="badge badge-default-flag">Current</span>' : '-'; ?></td>
                                        <td><?php echo (int) $term['displayOrder']; ?></td>
                                        <td>
                                            <button class="btn btn-xs btn-info edit-term" data-term='<?php echo json_encode($term); ?>'><i class="fa fa-pencil"></i></button>
                                            <button class="btn btn-xs btn-warning toggle-term" data-id="<?php echo (int) $term['termId']; ?>" data-active="<?php echo (int) $term['isActive']; ?>">
                                                <?php echo $term['isActive'] ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="settings-panel panel panel-default">
                <div class="panel-heading">
                    <span><i class="fa fa-tags"></i> Exam Types</span>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#examTypeModal"><i class="fa fa-plus"></i> Add Exam Type</button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table id="examTypesTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Exam Type</th>
                                    <th>Status</th>
                                    <th>Current</th>
                                    <th>Display Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examTypes as $index => $examType): ?>
                                    <tr data-id="<?php echo (int) $examType['examTypeId']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($examType['examTypeName']); ?></td>
                                        <td><span class="badge <?php echo $examType['isActive'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $examType['isActive'] ? 'Active' : 'Inactive'; ?></span></td>
                                        <td><?php echo $examType['isDefault'] ? '<span class="badge badge-default-flag">Current</span>' : '-'; ?></td>
                                        <td><?php echo (int) $examType['displayOrder']; ?></td>
                                        <td>
                                            <button class="btn btn-xs btn-info edit-exam-type" data-exam='<?php echo json_encode($examType); ?>'><i class="fa fa-pencil"></i></button>
                                            <button class="btn btn-xs btn-warning toggle-exam-type" data-id="<?php echo (int) $examType['examTypeId']; ?>" data-active="<?php echo (int) $examType['isActive']; ?>">
                                                <?php echo $examType['isActive'] ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>'; ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>
    </section>

    <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
</section>

<!-- Session Modal -->
<div class="modal fade" id="sessionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="sessionForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-calendar"></i> <span id="sessionModalTitle">Add Session</span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="sessionId" id="sessionId">
                    <div class="form-group">
                        <label>Session Name</label>
                        <input type="text" name="sessionName" id="sessionName" class="form-control" placeholder="e.g., 2025/2026" required>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="startDate" id="sessionStartDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="endDate" id="sessionEndDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="displayOrder" id="sessionDisplayOrder" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Term Modal -->
<div class="modal fade" id="termModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="termForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-flag"></i> <span id="termModalTitle">Add Term</span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="termId" id="termId">
                    <div class="form-group">
                        <label>Term Name</label>
                        <input type="text" name="termName" id="termName" class="form-control" placeholder="e.g., First Term" required>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="isDefault" id="termIsDefault" value="1"> Mark as current term</label>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="displayOrder" id="termDisplayOrder" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Exam Type Modal -->
<div class="modal fade" id="examTypeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="examTypeForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-tags"></i> <span id="examTypeModalTitle">Add Exam Type</span></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="examTypeId" id="examTypeId">
                    <div class="form-group">
                        <label>Exam Type Name</label>
                        <input type="text" name="examTypeName" id="examTypeName" class="form-control" placeholder="e.g., CAT 1" required>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="isDefault" id="examTypeIsDefault" value="1"> Mark as current exam type</label>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="displayOrder" id="examTypeDisplayOrder" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../libs/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="../../datatables/js/jquery.dataTables.min.js"></script>
<script src="academic_settings.js"></script>
<script>
    window.__PRELOADED_SESSIONS__ = <?php echo json_encode($sessions); ?>;
    window.__PRELOADED_TERMS__ = <?php echo json_encode($terms); ?>;
    window.__PRELOADED_EXAM_TYPES__ = <?php echo json_encode($examTypes); ?>;
</script>
</body>
</html>
