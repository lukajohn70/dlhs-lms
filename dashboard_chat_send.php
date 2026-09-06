<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_connection/dlhs_db_connection.php';
require_once __DIR__ . '/scripts/dashboard_chat_helper.php';

$identity = dlhsDashboardChatResolveIdentity($connection);
if ($identity === null) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Unauthorised'));
    exit;
}

if (empty($identity['canSend'])) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Students cannot send messages'));
    exit;
}

dlhsDashboardChatEnsureTable($connection);
dlhsDashboardChatMarkIncomingDelivered($connection, $identity);

$message = isset($_POST['message']) ? trim((string) $_POST['message']) : '';
$message = preg_replace("/\r\n?/", "\n", $message);
$targetType = isset($_POST['targetType']) ? trim((string) $_POST['targetType']) : '';
$targetRole = isset($_POST['targetRole']) ? trim((string) $_POST['targetRole']) : '';
$targetId = isset($_POST['targetId']) ? (int) $_POST['targetId'] : 0;

if ($message === '') {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'message' => 'Message is required'));
    exit;
}

if (mb_strlen($message) > 2000) {
    $message = mb_substr($message, 0, 2000);
}

$saved = dlhsDashboardChatInsertMessage($connection, $identity, $targetType, $targetRole, $targetId, $message);

if (!$saved) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'Unable to send message'));
    exit;
}

dlhsDashboardChatTouchPresence($connection, $identity, $saved['threadKey'], '');

echo json_encode(array(
    'ok' => true,
    'threadKey' => $saved['threadKey'],
    'unreadTotal' => dlhsDashboardChatCountUnreadMessages($connection, $identity),
));
