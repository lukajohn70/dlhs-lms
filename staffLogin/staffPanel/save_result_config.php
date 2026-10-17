<?php
header('Content-Type: application/json');
echo json_encode(['success' => false, 'msg' => 'Access Denied: Moved to Admin Portal.']);
exit;
?>
