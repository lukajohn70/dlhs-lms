<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure tables and columns exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$staffId = (int)$_SESSION['staffId'];

// 1. Files uploaded / shared by this staff member (Sent Files)
$sentQuery = "SELECT f.*, c.categoryName, s.subjectName,
                     (SELECT COUNT(*) FROM file_staff_assignments WHERE fileId = f.fileId) as recipientStaffCount,
                     (SELECT COUNT(*) FROM file_student_assignments WHERE fileId = f.fileId) as recipientStudentCount,
                     cl.className as targetClassName,
                     yg.yearGroupName
              FROM file_uploads f 
              LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
              LEFT JOIN subjects s ON f.subjectId = s.subjectId 
              LEFT JOIN classes cl ON f.targetClassId = cl.classId
              LEFT JOIN yeargroup yg ON cl.classYearGroup = yg.yearGroupId
              WHERE f.uploadedBy = $staffId 
                AND (f.uploaderType = 'staff' OR f.uploaderType IS NULL OR f.uploaderType = '')
                AND f.isActive = 1
              ORDER BY f.created_at DESC";

$sentResult = $connection->query($sentQuery);
$sentFiles = [];
$adminPendingCount = 0;
$adminReviewedCount = 0;

if ($sentResult && $sentResult->num_rows > 0) {
    while ($row = $sentResult->fetch_assoc()) {
        if ($row['uploadedFor'] === 'admin') {
            if ($row['adminStatus'] === 'reviewed' || $row['adminStatus'] === 'acknowledged') {
                $adminReviewedCount++;
            } else {
                $adminPendingCount++;
            }
        }
        $sentFiles[] = $row;
    }
}

// 2. Files shared with this staff member (Received Files from Admin, Colleagues, and Students)
$receivedQuery = "SELECT f.*, c.categoryName, s.subjectName,
                         CASE 
                             WHEN f.uploaderType = 'admin' THEN 'School Administration'
                             WHEN f.uploaderType = 'student' THEN CONCAT(stud.surname, ' ', stud.firstName, ' (', COALESCE(cls.className, 'Student'), ')')
                             ELSE CONCAT(st.surname, ' ', st.firstName)
                         END as senderName,
                         CASE
                             WHEN f.uploaderType = 'student' THEN COALESCE(cls.className, 'Student')
                             ELSE st.username
                         END as senderEmail,
                         st.passport as senderPassport,
                         f.uploaderType as senderType
                  FROM file_uploads f 
                  LEFT JOIN file_categories c ON f.categoryId = c.categoryId 
                  LEFT JOIN subjects s ON f.subjectId = s.subjectId 
                  LEFT JOIN stafflogin st ON (f.uploaderType = 'staff' AND f.uploadedBy = st.staffId)
                  LEFT JOIN studentlogin stud ON (f.uploaderType = 'student' AND f.uploadedBy = stud.studentId)
                  LEFT JOIN classes cls ON f.targetClassId = cls.classId
                  WHERE f.isActive = 1
                    AND (
                        -- Admin shared to all teachers
                        (f.uploaderType = 'admin' AND f.uploadedFor = 'all_teachers')
                        -- Admin shared to this specific teacher
                        OR (f.uploaderType = 'admin' AND f.fileId IN (SELECT fileId FROM file_staff_assignments WHERE staffId = $staffId))
                        -- Other teacher shared to all teachers
                        OR (f.uploaderType = 'staff' AND f.uploadedBy != $staffId AND f.uploadedFor = 'all_teachers')
                        -- Other teacher shared to subject department
                        OR (f.uploaderType = 'staff' AND f.uploadedBy != $staffId AND f.uploadedFor = 'subject_teachers' AND f.subjectId IN (
                            SELECT DISTINCT subjectId FROM subject_teacher_assignment WHERE teacherId = $staffId
                        ))
                        -- Other teacher shared specifically to this teacher
                        OR (f.uploaderType = 'staff' AND f.uploadedBy != $staffId AND f.fileId IN (SELECT fileId FROM file_staff_assignments WHERE staffId = $staffId))
                        -- Student submitted directly to this teacher
                        OR (f.uploaderType = 'student' AND f.targetStaffId = $staffId)
                    )
                  ORDER BY f.created_at DESC";

$receivedResult = $connection->query($receivedQuery);
$receivedFiles = [];
$fromAdminCount = 0;
$fromTeachersCount = 0;
$fromStudentsCount = 0;

if ($receivedResult && $receivedResult->num_rows > 0) {
    while ($row = $receivedResult->fetch_assoc()) {
        if ($row['uploaderType'] === 'admin') {
            $fromAdminCount++;
        } elseif ($row['uploaderType'] === 'student') {
            $fromStudentsCount++;
        } else {
            $fromTeachersCount++;
        }
        $receivedFiles[] = $row;
    }
}

echo json_encode([
    'success'       => true,
    'sentFiles'     => $sentFiles,
    'receivedFiles' => $receivedFiles,
    'files'         => $sentFiles, // backwards compatibility
    'stats'         => [
        'totalSent'     => count($sentFiles),
        'totalReceived' => count($receivedFiles),
        'fromAdmin'     => $fromAdminCount,
        'fromTeachers'  => $fromTeachersCount,
        'fromStudents'  => $fromStudentsCount,
        'adminPending'  => $adminPendingCount,
        'adminReviewed' => $adminReviewedCount
    ]
]);
?>
