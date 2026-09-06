<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connection/dlhs_db_connection.php';
require_once __DIR__ . '/scripts/dashboard_chat_helper.php';

$identity = dlhsDashboardChatResolveIdentity($connection);
session_write_close(); // Release PHP session lock so other pages aren't blocked
if ($identity === null) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Unauthorised'));
    exit;
}

dlhsDashboardChatEnsureTable($connection);

$threadKey = isset($_REQUEST['threadKey']) ? trim((string) $_REQUEST['threadKey']) : '';
$typing = isset($_REQUEST['typing']) ? trim((string) $_REQUEST['typing']) : '';
$typingThreadKey = ($typing === '1' && $threadKey !== '') ? $threadKey : '';

dlhsDashboardChatTouchPresence($connection, $identity, $threadKey, $typingThreadKey);
dlhsDashboardChatMarkIncomingDelivered($connection, $identity);

$threadMeta = null;
if ($threadKey !== '' && dlhsDashboardChatCanViewThread($identity, $threadKey)) {
    $threadMeta = dlhsDashboardChatDescribeThread($connection, $identity, $threadKey);
}

echo json_encode(array(
    'ok' => true,
    'thread' => $threadMeta,
    'unreadTotal' => dlhsDashboardChatCountUnreadMessages($connection, $identity),
));
