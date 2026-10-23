<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure tables exist
dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// 1. Submissions from Teachers to Admin
$submissionsQuery = "SELECT f.*, c.categoryName, s.subjectName,
                            CONCAT(st.surname, ' ', st.firstName) as teacherName,
                            st.username as teacherEmail,
                            st.passport as teacherPassport
                     FROM file_uploads f
                     LEFT JOIN file_categories c ON f.categoryId = c.categoryId
                     LEFT JOIN subjects s ON f.subjectId = s.subjectId
                     LEFT JOIN stafflogin st ON f.uploadedBy = st.staffId
                     WHERE f.uploaderType = 'staff' 
                       AND f.uploadedFor = 'admin' 
                       AND f.isActive = 1
                     ORDER BY f.created_at DESC";

$subResult = $connection->query($submissionsQuery);
$teacherSubmissions = [];
$pendingCount = 0;
$reviewedCount = 0;

if ($subResult && $subResult->num_rows > 0) {
    while ($row = $subResult->fetch_assoc()) {
        if ($row['adminStatus'] === 'reviewed' || $row['adminStatus'] === 'acknowledged') {
            $reviewedCount++;
        } else {
            $pendingCount++;
        }
        $teacherSubmissions[] = $row;
    }
}

// 2. Files shared by Admin
$adminQuery = "SELECT f.*, c.categoryName, s.subjectName,
                      (SELECT COUNT(*) FROM file_staff_assignments WHERE fileId = f.fileId) as recipientStaffCount,
                      cl.className as targetClassName
               FROM file_uploads f
               LEFT JOIN file_categories c ON f.categoryId = c.categoryId
               LEFT JOIN subjects s ON f.subjectId = s.subjectId
               LEFT JOIN classes cl ON f.targetClassId = cl.classId
               WHERE f.uploaderType = 'admin' AND f.isActive = 1
               ORDER BY f.created_at DESC";

$adminResult = $connection->query($adminQuery);
$adminSharedFiles = [];
if ($adminResult && $adminResult->num_rows > 0) {
    while ($row = $adminResult->fetch_assoc()) {
        $adminSharedFiles[] = $row;
    }
}

// 3. All System Files (Audit directory)
$allQuery = "SELECT f.*, c.categoryName, s.subjectName,
                    CASE 
                        WHEN f.uploaderType = 'admin' THEN 'School Administration'
                        ELSE CONCAT(st.surname, ' ', st.firstName)
                    END as uploaderName,
                    f.uploaderType
             FROM file_uploads f
             LEFT JOIN file_categories c ON f.categoryId = c.categoryId
             LEFT JOIN subjects s ON f.subjectId = s.subjectId
             LEFT JOIN stafflogin st ON f.uploadedBy = st.staffId
             WHERE f.isActive = 1
             ORDER BY f.created_at DESC";

$allResult = $connection->query($allQuery);
$allSystemFiles = [];
$totalStorageBytes = 0;

if ($allResult && $allResult->num_rows > 0) {
    while ($row = $allResult->fetch_assoc()) {
        $totalStorageBytes += (int)$row['fileSize'];
        $allSystemFiles[] = $row;
    }
}

// Staff count
$staffCountQuery = $connection->query("SELECT COUNT(*) as cnt FROM stafflogin");
$staffCount = 0;
if ($staffCountQuery && $r = $staffCountQuery->fetch_assoc()) {
    $staffCount = (int)$r['cnt'];
}

echo json_encode([
    'success'            => true,
    'teacherSubmissions' => $teacherSubmissions,
    'adminSharedFiles'   => $adminSharedFiles,
    'allSystemFiles'     => $allSystemFiles,
    'stats'              => [
        'totalSubmissions'  => count($teacherSubmissions),
        'pendingCount'      => $pendingCount,
        'reviewedCount'     => $reviewedCount,
        'adminSharedCount'  => count($adminSharedFiles),
        'allFilesCount'     => count($allSystemFiles),
        'staffCount'        => $staffCount,
        'totalStorageBytes' => $totalStorageBytes
    ]
]);
?>
