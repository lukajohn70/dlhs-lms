<?php
/**
 * Promote Basic 8 students from LOCAL deeper_life DB
 * to REMOTE deeper_life server at 10.10.10.1
 * Distributing evenly across SS 1 Science A, SS 1 Science B, and SS 1 Humanities
 */

$localHost = '127.0.0.1';
$localUser = 'root';
$localPass = 'root';
$localDb   = 'deeper_life';
$localPort = 3306;

mysqli_report(MYSQLI_REPORT_OFF);
$localConn = @new mysqli($localHost, $localUser, $localPass, $localDb, $localPort);
if ($localConn->connect_error) {
    $localConn = @new mysqli($localHost, $localUser, '', $localDb, $localPort);
}
if ($localConn->connect_error) {
    die("Local DB connection failed: " . $localConn->connect_error . "\n");
}
$localConn->set_charset('utf8mb4');
echo "[LOCAL] Connected to local MySQL deeper_life database.\n";

// Fetch valid Basic 8 students
$query = "SELECT surname, firstName, middleName, gender, admissionNumber, studentEmail, password 
          FROM studentlogin 
          WHERE yearGroupId = 29 AND admissionNumber != '' 
          ORDER BY studentId ASC";
$res = $localConn->query($query);
if (!$res) {
    die("Error fetching local students: " . $localConn->error . "\n");
}

$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = $row;
}
$total = count($students);
echo "[LOCAL] Found $total valid Basic 8 students.\n";

if ($total === 0) {
    die("No students found. Aborting.\n");
}

// Target classes on remote server (yearGroupId 31 = SS 1)
$targetClasses = [
    0 => [
        'name' => 'Science A',
        'yearGroupId' => 31,
        'classId' => 38,
        'students' => []
    ],
    1 => [
        'name' => 'Science B',
        'yearGroupId' => 31,
        'classId' => 44,
        'students' => []
    ],
    2 => [
        'name' => 'Humanities',
        'yearGroupId' => 31,
        'classId' => 42,
        'students' => []
    ]
];

// Distribute students round-robin
foreach ($students as $idx => $student) {
    $targetClasses[$idx % 3]['students'][] = $student;
}

echo "\n--- Distribution Plan ---\n";
foreach ($targetClasses as $tc) {
    echo "  SS 1 " . $tc['name'] . " (classId: {$tc['classId']}): " . count($tc['students']) . " students\n";
}

// Authenticate with remote server
$cookieFile = sys_get_temp_dir() . '/dlhs_remote_session.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$loginUrl = 'http://10.10.10.1/adminLogin/login.php';
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'adminUsername' => 'admin',
    'adminPassword' => 'rr'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$loginResp = trim(curl_exec($ch));
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || ($loginResp !== '1' && $loginResp !== '2')) {
    die("[REMOTE] Authentication failed. Response: '$loginResp' (HTTP $httpCode)\n");
}
echo "\n[REMOTE] Admin authentication successful.\n";

// Upload each group via CSV upload endpoint
$uploadUrl = 'http://10.10.10.1/adminLogin/adminPanel/uploadCsvStudentsRecords.php';
$tmpDir = sys_get_temp_dir();

foreach ($targetClasses as $tc) {
    $cName = $tc['name'];
    $ygId  = $tc['yearGroupId'];
    $cId   = $tc['classId'];
    $stuList = $tc['students'];
    $count = count($stuList);

    echo "\nUploading $count students to SS 1 $cName (classId: $cId)...\n";

    // Create temporary CSV file
    $csvPath = $tmpDir . "/upload_" . preg_replace('/[^a-zA-Z0-9]/', '_', $cName) . ".csv";
    $fp = fopen($csvPath, 'w');
    // Header row
    fputcsv($fp, ['surname', 'first_name', 'middle_name', 'gender', 'admission_number', 'student_email', 'student_password']);
    foreach ($stuList as $s) {
        fputcsv($fp, [
            $s['surname'],
            $s['firstName'],
            $s['middleName'],
            $s['gender'],
            $s['admissionNumber'],
            $s['studentEmail'],
            $s['password']
        ]);
    }
    fclose($fp);

    // Prepare multipart POST
    $cfile = new CURLFile($csvPath, 'text/csv', basename($csvPath));
    $postData = [
        'yearGroup4' => $ygId,
        'className4' => $cId,
        'file4'      => $cfile
    ];

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (file_exists($csvPath)) {
        unlink($csvPath);
    }

    if ($code === 302 && strpos($response, 'msgStatus3=1') !== false) {
        echo "  -> SUCCESS: Uploaded successfully (msgStatus3=1).\n";
    } elseif ($code === 302 && strpos($response, 'msgStatus3=11') !== false) {
        echo "  -> SUCCESS: Records updated successfully (msgStatus3=11).\n";
    } else {
        echo "  -> WARNING: HTTP Code: $code\n";
        echo "  -> Header response: " . substr($response, 0, 300) . "\n";
    }
}

// Verification
echo "\n=== STEP: Verifying upload on remote server ===\n";
$verifyUrl = 'http://10.10.10.1/adminLogin/adminPanel/getStudentsRecordsBasedOnCriteria.php';

$totalVerified = 0;
foreach ($targetClasses as $tc) {
    $cName = $tc['name'];
    $ygId  = $tc['yearGroupId'];
    $cId   = $tc['classId'];

    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'selectedYearGroupId' => $ygId,
        'selectedClassId'     => $cId
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respJson = curl_exec($ch);
    curl_close($ch);

    $records = json_decode($respJson, true);
    $recCount = is_array($records) ? count($records) : 0;
    $totalVerified += $recCount;

    echo "  SS 1 $cName: Verified $recCount students present on remote server.\n";
}

echo "\nTotal students now in remote SS 1 classes: $totalVerified (expected $total)\n";

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}
$localConn->close();
echo "Done!\n";
