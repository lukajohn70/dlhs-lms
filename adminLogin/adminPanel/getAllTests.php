<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";
error_reporting(0);

// Single JOIN query replaces the previous N+1 pattern (4 queries per test × 200+ tests).
// Old code referenced: tests[0]=testId, [1]=staffId, [2]=testName, [3]=testDate, [4]=duration,
// [5]=startHour, [6]=startMinute, [7]=amOrPm, [8]=subject, [9]=yearGroup, [11]=status,
// [16]=academicYearId, [17]=reviewOption, [18]=essayOption, [19]=essayTime
// stafflogin: [1]=surname, [2]=firstName, [3]=middleName
$query = "SELECT
    t.testId,
    t.staffId AS teacherId,
    t.testName,
    t.testDate,
    t.duration,
    t.startHour,
    t.startMinute,
    t.amOrPm AS isAmOrPm,
    t.subject AS subjectId,
    t.yearGroup AS yearGroupId,
    t.status AS testStatus,
    t.reviewOption,
    t.academicYearId,
    t.essayOption,
    t.essayTime,
    s.subjectName,
    yg.yearGroupName,
    ay.academicYearName,
    CONCAT(COALESCE(st.firstName,''), ' ', COALESCE(st.middleName,''), ' ', COALESCE(st.surname,'')) AS teacherFullName
FROM tests t
LEFT JOIN subjects s ON s.subjectId = t.subject
LEFT JOIN yeargroup yg ON yg.yearGroupId = t.yearGroup
LEFT JOIN academic_year ay ON ay.academicYearId = t.academicYearId
LEFT JOIN stafflogin st ON st.staffId = t.staffId
ORDER BY t.testDate DESC, t.testId DESC";

$result = $connection->query($query);

$return_arr = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teacherName = trim((string) $row['teacherFullName']);
        if ($teacherName === '') {
            $teacherName = 'Unknown Teacher';
        }
        $return_arr[] = array(
            'testId'        => $row['testId'],
            'testName'      => $row['testName'],
            'testDate'      => $row['testDate'],
            'duration'      => $row['duration'],
            'startHour'     => $row['startHour'],
            'startMinute'   => $row['startMinute'],
            'isAmOrPm'      => $row['isAmOrPm'],
            'subjectId'     => $row['subjectId'],
            'subjectName'   => $row['subjectName'] ?? 'Unknown Subject',
            'yearGroupId'   => $row['yearGroupId'],
            'yearGroupName' => $row['yearGroupName'] ?? 'Unknown Year Group',
            'testYear'      => $row['academicYearName'] ?? '',
            'reviewOption'  => $row['reviewOption'],
            'teacherName'   => $teacherName,
            'essayOption'   => $row['essayOption'],
            'essayTime'     => $row['essayTime'],
            'status'        => $row['testStatus'],
        );
    }
}
echo json_encode($return_arr);
