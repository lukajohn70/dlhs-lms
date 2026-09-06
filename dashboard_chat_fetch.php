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

$sinceId = isset($_GET['sinceId']) ? (int) $_GET['sinceId'] : 0;
$threadKey = isset($_GET['threadKey']) ? trim((string) $_GET['threadKey']) : '';

if ($threadKey === '') {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Conversation is required'));
    exit;
}

$typing = isset($_GET['typing']) ? trim((string) $_GET['typing']) : '';
dlhsDashboardChatTouchPresence($connection, $identity, $threadKey, $typing === '1' ? $threadKey : '');
dlhsDashboardChatMarkIncomingDelivered($connection, $identity);
dlhsDashboardChatMarkThreadRead($connection, $identity, $threadKey);

$messages = dlhsDashboardChatFetchMessages($connection, $identity, $threadKey, $sinceId, 80);
if ($messages === null) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Conversation not available'));
    exit;
}

$threadMeta = dlhsDashboardChatDescribeThread($connection, $identity, $threadKey);

echo json_encode(array(
    'ok' => true,
    'messages' => $messages,
    'thread' => $threadMeta,
    'unreadTotal' => dlhsDashboardChatCountUnreadMessages($connection, $identity),
));
