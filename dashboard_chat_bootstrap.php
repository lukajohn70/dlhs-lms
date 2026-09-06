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
dlhsDashboardChatMarkIncomingDelivered($connection, $identity);

$activeThreadKey = isset($_GET['threadKey']) ? trim((string) $_GET['threadKey']) : '';
dlhsDashboardChatTouchPresence($connection, $identity, $activeThreadKey, '');

$recipients = dlhsDashboardChatBuildRecipientOptions($connection, $identity);
$threads = dlhsDashboardChatFetchVisibleThreads($connection, $identity, 50);

$defaultThreadKey = '';
if (!empty($identity['canSend']) && !empty($recipients)) {
    $defaultThreadKey = $recipients[0]['threadKey'];
} elseif (!empty($threads)) {
    $defaultThreadKey = $threads[0]['threadKey'];
}

echo json_encode(array(
    'ok' => true,
    'identity' => $identity,
    'recipients' => $recipients,
    'threads' => $threads,
    'defaultThreadKey' => $defaultThreadKey,
    'canSend' => !empty($identity['canSend']),
    'unreadTotal' => dlhsDashboardChatCountUnreadMessages($connection, $identity),
));
