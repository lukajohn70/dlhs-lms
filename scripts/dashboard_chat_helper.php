<?php
if (!function_exists('dlhsDashboardChatHasColumn')) {
    function dlhsDashboardChatHasColumn($connection, $table, $column)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $column);

        if ($table === '' || $column === '') {
            return false;
        }

        $result = $connection->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('dlhsDashboardChatEnsureTable')) {
    function dlhsDashboardChatEnsureTable($connection)
    {
        $sql = "CREATE TABLE IF NOT EXISTS dashboard_chat_messages (
                    id INT NOT NULL AUTO_INCREMENT,
                    roomKey VARCHAR(80) NOT NULL DEFAULT '',
                    threadKey VARCHAR(190) NOT NULL DEFAULT '',
                    messageScope VARCHAR(20) NOT NULL DEFAULT 'direct',
                    senderRole VARCHAR(20) NOT NULL,
                    senderId INT NOT NULL DEFAULT 0,
                    senderName VARCHAR(190) NOT NULL,
                    recipientRole VARCHAR(20) NOT NULL DEFAULT '',
                    recipientId INT NOT NULL DEFAULT 0,
                    recipientName VARCHAR(190) NOT NULL DEFAULT '',
                    messageText TEXT NOT NULL,
                    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_dashboard_chat_thread (threadKey),
                    KEY idx_dashboard_chat_recipient (recipientRole, recipientId),
                    KEY idx_dashboard_chat_created (createdAt)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$connection->query($sql)) {
            return false;
        }

        $columnDefinitions = array(
            'threadKey' => "ALTER TABLE dashboard_chat_messages ADD COLUMN threadKey VARCHAR(190) NOT NULL DEFAULT '' AFTER roomKey",
            'messageScope' => "ALTER TABLE dashboard_chat_messages ADD COLUMN messageScope VARCHAR(20) NOT NULL DEFAULT 'direct' AFTER threadKey",
            'recipientRole' => "ALTER TABLE dashboard_chat_messages ADD COLUMN recipientRole VARCHAR(20) NOT NULL DEFAULT '' AFTER senderName",
            'recipientId' => "ALTER TABLE dashboard_chat_messages ADD COLUMN recipientId INT NOT NULL DEFAULT 0 AFTER recipientRole",
            'recipientName' => "ALTER TABLE dashboard_chat_messages ADD COLUMN recipientName VARCHAR(190) NOT NULL DEFAULT '' AFTER recipientId",
            'deliveredAt' => "ALTER TABLE dashboard_chat_messages ADD COLUMN deliveredAt TIMESTAMP NULL DEFAULT NULL AFTER createdAt",
            'readAt' => "ALTER TABLE dashboard_chat_messages ADD COLUMN readAt TIMESTAMP NULL DEFAULT NULL AFTER deliveredAt",
        );

        foreach ($columnDefinitions as $column => $alterSql) {
            if (!dlhsDashboardChatHasColumn($connection, 'dashboard_chat_messages', $column)) {
                $connection->query($alterSql);
            }
        }

        $connection->query("UPDATE dashboard_chat_messages
                            SET threadKey='broadcast:all',
                                messageScope='broadcast',
                                recipientRole='all',
                                recipientId=0,
                                recipientName='All users'
                            WHERE (threadKey='' OR threadKey IS NULL)");

        $presenceSql = "CREATE TABLE IF NOT EXISTS dashboard_chat_presence (
                            id INT NOT NULL AUTO_INCREMENT,
                            userRole VARCHAR(20) NOT NULL,
                            userId INT NOT NULL DEFAULT 0,
                            userName VARCHAR(190) NOT NULL DEFAULT '',
                            activeThreadKey VARCHAR(190) NOT NULL DEFAULT '',
                            typingThreadKey VARCHAR(190) NOT NULL DEFAULT '',
                            lastSeenAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            typingUpdatedAt TIMESTAMP NULL DEFAULT NULL,
                            createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (id),
                            UNIQUE KEY uniq_dashboard_chat_presence_user (userRole, userId),
                            KEY idx_dashboard_chat_presence_seen (lastSeenAt)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $connection->query($presenceSql);

        return true;
    }
}

if (!function_exists('dlhsDashboardChatFormatRelativeTime')) {
    function dlhsDashboardChatFormatRelativeTime($value)
    {
        if (empty($value)) {
            return 'Offline';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return 'Offline';
        }

        $delta = time() - $timestamp;
        if ($delta < 0) {
            $delta = 0;
        }

        if ($delta <= 45) {
            return 'Online now';
        }

        if ($delta < 90) {
            return 'Last seen 1 minute ago';
        }

        if ($delta < 3600) {
            return 'Last seen ' . floor($delta / 60) . ' minutes ago';
        }

        if ($delta < 7200) {
            return 'Last seen 1 hour ago';
        }

        if ($delta < 86400) {
            return 'Last seen ' . floor($delta / 3600) . ' hours ago';
        }

        if ($delta < 172800) {
            return 'Last seen yesterday';
        }

        return 'Last seen ' . date('j M, g:i a', $timestamp);
    }
}

if (!function_exists('dlhsDashboardChatTouchPresence')) {
    function dlhsDashboardChatTouchPresence($connection, $identity, $activeThreadKey = '', $typingThreadKey = '')
    {
        if (!is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return false;
        }

        $role = $connection->real_escape_string($identity['role']);
        $userId = (int) $identity['id'];
        $userName = $connection->real_escape_string(isset($identity['name']) ? $identity['name'] : 'User');
        $activeThreadKey = $connection->real_escape_string(trim((string) $activeThreadKey));
        $typingThreadKey = $connection->real_escape_string(trim((string) $typingThreadKey));
        $typingUpdatedAtSql = $typingThreadKey !== '' ? 'NOW()' : 'NULL';

        $query = "INSERT INTO dashboard_chat_presence
                    (userRole, userId, userName, activeThreadKey, typingThreadKey, lastSeenAt, typingUpdatedAt)
                  VALUES
                    ('{$role}', {$userId}, '{$userName}', '{$activeThreadKey}', '{$typingThreadKey}', NOW(), {$typingUpdatedAtSql})
                  ON DUPLICATE KEY UPDATE
                    userName = VALUES(userName),
                    activeThreadKey = VALUES(activeThreadKey),
                    typingThreadKey = VALUES(typingThreadKey),
                    lastSeenAt = NOW(),
                    typingUpdatedAt = {$typingUpdatedAtSql}";

        return $connection->query($query);
    }
}

if (!function_exists('dlhsDashboardChatMarkIncomingDelivered')) {
    function dlhsDashboardChatMarkIncomingDelivered($connection, $identity)
    {
        if (!is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return 0;
        }

        $role = $connection->real_escape_string($identity['role']);
        $userId = (int) $identity['id'];

        $query = "UPDATE dashboard_chat_messages
                  SET deliveredAt = IFNULL(deliveredAt, NOW())
                  WHERE messageScope = 'direct'
                    AND recipientRole = '{$role}'
                    AND recipientId = {$userId}
                    AND deliveredAt IS NULL";

        $connection->query($query);
        return (int) $connection->affected_rows;
    }
}

if (!function_exists('dlhsDashboardChatMarkThreadRead')) {
    function dlhsDashboardChatMarkThreadRead($connection, $identity, $threadKey)
    {
        $threadKey = trim((string) $threadKey);
        if ($threadKey === '' || !is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return 0;
        }

        $role = $connection->real_escape_string($identity['role']);
        $userId = (int) $identity['id'];
        $threadKeySql = $connection->real_escape_string($threadKey);

        $query = "UPDATE dashboard_chat_messages
                  SET deliveredAt = IFNULL(deliveredAt, NOW()),
                      readAt = IFNULL(readAt, NOW())
                  WHERE messageScope = 'direct'
                    AND threadKey = '{$threadKeySql}'
                    AND recipientRole = '{$role}'
                    AND recipientId = {$userId}
                    AND readAt IS NULL";

        $connection->query($query);
        return (int) $connection->affected_rows;
    }
}

if (!function_exists('dlhsDashboardChatCountUnreadMessages')) {
    function dlhsDashboardChatCountUnreadMessages($connection, $identity)
    {
        if (!is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return 0;
        }

        $role = $connection->real_escape_string($identity['role']);
        $userId = (int) $identity['id'];
        $result = $connection->query("SELECT COUNT(*) AS total
                                      FROM dashboard_chat_messages
                                      WHERE messageScope = 'direct'
                                        AND recipientRole = '{$role}'
                                        AND recipientId = {$userId}
                                        AND readAt IS NULL");

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return isset($row['total']) ? (int) $row['total'] : 0;
    }
}

if (!function_exists('dlhsDashboardChatCountUnreadForThread')) {
    function dlhsDashboardChatCountUnreadForThread($connection, $identity, $threadKey)
    {
        $threadKey = trim((string) $threadKey);
        if ($threadKey === '' || !is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return 0;
        }

        if ($threadKey === dlhsDashboardChatBuildBroadcastThreadKey()) {
            return 0;
        }

        $role = $connection->real_escape_string($identity['role']);
        $userId = (int) $identity['id'];
        $threadKeySql = $connection->real_escape_string($threadKey);

        $result = $connection->query("SELECT COUNT(*) AS total
                                      FROM dashboard_chat_messages
                                      WHERE threadKey = '{$threadKeySql}'
                                        AND messageScope = 'direct'
                                        AND recipientRole = '{$role}'
                                        AND recipientId = {$userId}
                                        AND readAt IS NULL");

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return isset($row['total']) ? (int) $row['total'] : 0;
    }
}

if (!function_exists('dlhsDashboardChatGetPresenceMeta')) {
    function dlhsDashboardChatGetPresenceMeta($connection, $participant, $threadKey = '')
    {
        if (!is_array($participant) || empty($participant['role']) || (int) $participant['id'] <= 0) {
            return array(
                'isOnline' => false,
                'isTyping' => false,
                'statusLabel' => 'Offline',
            );
        }

        $role = $connection->real_escape_string($participant['role']);
        $userId = (int) $participant['id'];
        $result = $connection->query("SELECT lastSeenAt, typingThreadKey, typingUpdatedAt
                                      FROM dashboard_chat_presence
                                      WHERE userRole = '{$role}' AND userId = {$userId}
                                      LIMIT 1");

        if (!$result || $result->num_rows === 0) {
            return array(
                'isOnline' => false,
                'isTyping' => false,
                'statusLabel' => 'Offline',
            );
        }

        $row = $result->fetch_assoc();
        $lastSeenAt = isset($row['lastSeenAt']) ? $row['lastSeenAt'] : '';
        $typingThreadKey = isset($row['typingThreadKey']) ? trim((string) $row['typingThreadKey']) : '';
        $typingUpdatedAt = isset($row['typingUpdatedAt']) ? $row['typingUpdatedAt'] : '';
        $lastSeenTimestamp = !empty($lastSeenAt) ? strtotime($lastSeenAt) : false;
        $typingTimestamp = !empty($typingUpdatedAt) ? strtotime($typingUpdatedAt) : false;
        $isOnline = $lastSeenTimestamp !== false && (time() - $lastSeenTimestamp) <= 45;
        $isTyping = $threadKey !== ''
            && $typingThreadKey === $threadKey
            && $typingTimestamp !== false
            && (time() - $typingTimestamp) <= 12
            && $isOnline;

        return array(
            'isOnline' => $isOnline,
            'isTyping' => $isTyping,
            'statusLabel' => $isTyping ? 'Typing...' : dlhsDashboardChatFormatRelativeTime($lastSeenAt),
        );
    }
}

if (!function_exists('dlhsDashboardChatDeliveryLabel')) {
    function dlhsDashboardChatDeliveryLabel($state)
    {
        if ($state === 'read') {
            return 'Read';
        }

        if ($state === 'delivered') {
            return 'Delivered';
        }

        if ($state === 'sent') {
            return 'Sent';
        }

        return '';
    }
}

if (!function_exists('dlhsDashboardChatRoleLabel')) {
    function dlhsDashboardChatRoleLabel($role)
    {
        if ($role === 'admin') {
            return 'Admin';
        }

        if ($role === 'staff') {
            return 'Staff';
        }

        if ($role === 'student') {
            return 'Student';
        }

        return 'User';
    }
}

if (!function_exists('dlhsDashboardChatBuildPersonName')) {
    function dlhsDashboardChatBuildPersonName($firstName, $middleName, $surname, $fallback)
    {
        $parts = array();
        foreach (array($firstName, $middleName, $surname) as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        if (!empty($parts)) {
            return trim(implode(' ', $parts));
        }

        $fallback = trim((string) $fallback);
        return $fallback !== '' ? $fallback : 'User';
    }
}

if (!function_exists('dlhsDashboardChatBuildInitials')) {
    function dlhsDashboardChatBuildInitials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'DL';
        }

        $parts = preg_split('/\s+/', $name);
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'DL';
    }
}

if (!function_exists('dlhsDashboardChatResolveUserMeta')) {
    function dlhsDashboardChatResolveUserMeta($connection, $role, $id)
    {
        $role = trim((string) $role);
        $id = (int) $id;

        if ($id <= 0) {
            return null;
        }

        if ($role === 'admin') {
            $result = $connection->query("SELECT adminId, username FROM admintable WHERE adminId={$id} LIMIT 1");
            if (!$result || $result->num_rows === 0) {
                return null;
            }

            $row = $result->fetch_assoc();
            $name = isset($row['username']) && trim((string) $row['username']) !== ''
                ? trim((string) $row['username'])
                : 'Administrator';

            return array(
                'role' => 'admin',
                'id' => (int) $row['adminId'],
                'name' => $name,
                'description' => 'Admin Portal',
                'initials' => dlhsDashboardChatBuildInitials($name),
            );
        }

        if ($role === 'staff') {
            $result = $connection->query("SELECT staffId, surname, firstName, middleName, username
                                          FROM stafflogin
                                          WHERE staffId={$id}
                                          LIMIT 1");
            if (!$result || $result->num_rows === 0) {
                return null;
            }

            $row = $result->fetch_assoc();
            $name = dlhsDashboardChatBuildPersonName(
                isset($row['firstName']) ? $row['firstName'] : '',
                isset($row['middleName']) ? $row['middleName'] : '',
                isset($row['surname']) ? $row['surname'] : '',
                isset($row['username']) ? $row['username'] : 'Staff'
            );

            return array(
                'role' => 'staff',
                'id' => (int) $row['staffId'],
                'name' => $name,
                'description' => 'Staff Portal',
                'initials' => dlhsDashboardChatBuildInitials($name),
            );
        }

        if ($role === 'student') {
            $result = $connection->query("SELECT s.studentId, s.surname, s.firstName, s.middleName, c.className, y.yearGroupName
                                          FROM studentlogin s
                                          LEFT JOIN classes c ON s.classId = c.classId
                                          LEFT JOIN yeargroup y ON s.yearGroupId = y.yearGroupId
                                          WHERE s.studentId={$id}
                                          LIMIT 1");
            if (!$result || $result->num_rows === 0) {
                return null;
            }

            $row = $result->fetch_assoc();
            $name = dlhsDashboardChatBuildPersonName(
                isset($row['firstName']) ? $row['firstName'] : '',
                isset($row['middleName']) ? $row['middleName'] : '',
                isset($row['surname']) ? $row['surname'] : '',
                'Student'
            );

            $descriptionParts = array();
            if (!empty($row['yearGroupName'])) {
                $descriptionParts[] = $row['yearGroupName'];
            }
            if (!empty($row['className'])) {
                $descriptionParts[] = $row['className'];
            }

            return array(
                'role' => 'student',
                'id' => (int) $row['studentId'],
                'name' => $name,
                'description' => !empty($descriptionParts) ? implode(' - ', $descriptionParts) : 'Student',
                'initials' => dlhsDashboardChatBuildInitials($name),
            );
        }

        return null;
    }
}

if (!function_exists('dlhsDashboardChatResolveIdentity')) {
    function dlhsDashboardChatResolveIdentity($connection = null)
    {
        if (isset($_SESSION['adminLoggedIn']) && isset($_SESSION['adminId'])) {
            $identity = $connection ? dlhsDashboardChatResolveUserMeta($connection, 'admin', (int) $_SESSION['adminId']) : null;
            if ($identity === null) {
                $fallbackName = isset($_SESSION['adminEmail']) && trim((string) $_SESSION['adminEmail']) !== ''
                    ? trim((string) $_SESSION['adminEmail'])
                    : 'Administrator';

                $identity = array(
                    'role' => 'admin',
                    'id' => (int) $_SESSION['adminId'],
                    'name' => $fallbackName,
                    'description' => 'Admin Portal',
                    'initials' => dlhsDashboardChatBuildInitials($fallbackName),
                );
            }

            $identity['canSend'] = true;
            return $identity;
        }

        if (isset($_SESSION['staffLoggedIn']) && isset($_SESSION['staffId'])) {
            $identity = $connection ? dlhsDashboardChatResolveUserMeta($connection, 'staff', (int) $_SESSION['staffId']) : null;
            if ($identity === null) {
                $fallbackName = isset($_SESSION['staffName']) && trim((string) $_SESSION['staffName']) !== ''
                    ? trim((string) $_SESSION['staffName'])
                    : 'Staff';

                $identity = array(
                    'role' => 'staff',
                    'id' => (int) $_SESSION['staffId'],
                    'name' => $fallbackName,
                    'description' => 'Staff Portal',
                    'initials' => dlhsDashboardChatBuildInitials($fallbackName),
                );
            }

            $identity['canSend'] = true;
            return $identity;
        }

        if (isset($_SESSION['studentLoggedIn']) && isset($_SESSION['studentId'])) {
            $identity = $connection ? dlhsDashboardChatResolveUserMeta($connection, 'student', (int) $_SESSION['studentId']) : null;
            if ($identity === null) {
                $fallbackName = isset($_SESSION['studentName']) && trim((string) $_SESSION['studentName']) !== ''
                    ? trim((string) $_SESSION['studentName'])
                    : 'Student';

                $identity = array(
                    'role' => 'student',
                    'id' => (int) $_SESSION['studentId'],
                    'name' => $fallbackName,
                    'description' => 'Student',
                    'initials' => dlhsDashboardChatBuildInitials($fallbackName),
                );
            }

            $identity['canSend'] = false;
            return $identity;
        }

        return null;
    }
}

if (!function_exists('dlhsDashboardChatParticipantToken')) {
    function dlhsDashboardChatParticipantToken($role, $id)
    {
        return trim((string) $role) . '-' . (int) $id;
    }
}

if (!function_exists('dlhsDashboardChatBuildBroadcastThreadKey')) {
    function dlhsDashboardChatBuildBroadcastThreadKey()
    {
        return 'broadcast:all';
    }
}

if (!function_exists('dlhsDashboardChatBuildThreadKey')) {
    function dlhsDashboardChatBuildThreadKey($leftParticipant, $rightParticipant)
    {
        $tokens = array(
            dlhsDashboardChatParticipantToken($leftParticipant['role'], $leftParticipant['id']),
            dlhsDashboardChatParticipantToken($rightParticipant['role'], $rightParticipant['id']),
        );

        sort($tokens, SORT_STRING);
        return 'direct:' . implode('__', $tokens);
    }
}

if (!function_exists('dlhsDashboardChatParseThreadKey')) {
    function dlhsDashboardChatParseThreadKey($threadKey)
    {
        $threadKey = trim((string) $threadKey);

        if ($threadKey === dlhsDashboardChatBuildBroadcastThreadKey()) {
            return array(
                'type' => 'broadcast',
                'participants' => array(),
            );
        }

        if (strpos($threadKey, 'direct:') !== 0) {
            return null;
        }

        $participantTokens = explode('__', substr($threadKey, 7));
        if (count($participantTokens) !== 2) {
            return null;
        }

        $participants = array();
        foreach ($participantTokens as $participantToken) {
            if (!preg_match('/^(admin|staff|student)\-(\d+)$/', $participantToken, $matches)) {
                return null;
            }

            $participants[] = array(
                'role' => $matches[1],
                'id' => (int) $matches[2],
            );
        }

        return array(
            'type' => 'direct',
            'participants' => $participants,
        );
    }
}

if (!function_exists('dlhsDashboardChatCanViewThread')) {
    function dlhsDashboardChatCanViewThread($identity, $threadKey)
    {
        if (!is_array($identity) || empty($identity['role']) || empty($identity['id'])) {
            return false;
        }

        if ($threadKey === dlhsDashboardChatBuildBroadcastThreadKey()) {
            return true;
        }

        $parsed = dlhsDashboardChatParseThreadKey($threadKey);
        if (!$parsed || $parsed['type'] !== 'direct') {
            return false;
        }

        foreach ($parsed['participants'] as $participant) {
            if ($participant['role'] === $identity['role'] && (int) $participant['id'] === (int) $identity['id']) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('dlhsDashboardChatResolveTarget')) {
    function dlhsDashboardChatResolveTarget($connection, $identity, $targetType, $targetRole, $targetId)
    {
        if (!is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return null;
        }

        if ($identity['role'] === 'student') {
            return null;
        }

        $targetType = trim((string) $targetType);
        $targetRole = trim((string) $targetRole);
        $targetId = (int) $targetId;

        if ($targetType === 'broadcast') {
            if ($identity['role'] !== 'admin' || $targetRole !== 'all') {
                return null;
            }

            return array(
                'threadKey' => dlhsDashboardChatBuildBroadcastThreadKey(),
                'messageScope' => 'broadcast',
                'recipientRole' => 'all',
                'recipientId' => 0,
                'recipientName' => 'All users',
                'label' => 'All users',
                'description' => 'Broadcast announcement',
            );
        }

        if ($targetType !== 'direct' || !in_array($targetRole, array('admin', 'staff', 'student'), true) || $targetId <= 0) {
            return null;
        }

        if ($identity['role'] === 'staff' && !in_array($targetRole, array('admin', 'staff', 'student'), true)) {
            return null;
        }

        $recipient = dlhsDashboardChatResolveUserMeta($connection, $targetRole, $targetId);
        if ($recipient === null) {
            return null;
        }

        if ($recipient['role'] === $identity['role'] && (int) $recipient['id'] === (int) $identity['id']) {
            return null;
        }

        return array(
            'threadKey' => dlhsDashboardChatBuildThreadKey($identity, $recipient),
            'messageScope' => 'direct',
            'recipientRole' => $recipient['role'],
            'recipientId' => (int) $recipient['id'],
            'recipientName' => $recipient['name'],
            'label' => $recipient['name'],
            'description' => isset($recipient['description']) ? $recipient['description'] : dlhsDashboardChatRoleLabel($recipient['role']),
        );
    }
}

if (!function_exists('dlhsDashboardChatBuildRecipientOptions')) {
    function dlhsDashboardChatBuildRecipientOptions($connection, $identity)
    {
        $options = array();

        if (!is_array($identity) || empty($identity['canSend'])) {
            return $options;
        }

        if ($identity['role'] === 'admin') {
            $options[] = array(
                'group' => 'Broadcast',
                'threadKey' => dlhsDashboardChatBuildBroadcastThreadKey(),
                'targetType' => 'broadcast',
                'targetRole' => 'all',
                'targetId' => 0,
                'label' => 'All users',
                'description' => 'Send to admin, staff, and students',
                'isOnline' => false,
                'isTyping' => false,
                'statusLabel' => 'Broadcast announcement',
            );
        }

        $adminResult = $connection->query("SELECT adminId FROM admintable ORDER BY username ASC");
        if ($adminResult) {
            while ($row = $adminResult->fetch_assoc()) {
                $adminId = (int) $row['adminId'];
                if ($identity['role'] === 'admin' && $adminId === (int) $identity['id']) {
                    continue;
                }

                $recipient = dlhsDashboardChatResolveUserMeta($connection, 'admin', $adminId);
                if ($recipient === null) {
                    continue;
                }

                $threadKey = dlhsDashboardChatBuildThreadKey($identity, $recipient);
                $presence = dlhsDashboardChatGetPresenceMeta($connection, $recipient, $threadKey);

                $options[] = array(
                    'group' => 'Admins',
                    'threadKey' => $threadKey,
                    'targetType' => 'direct',
                    'targetRole' => 'admin',
                    'targetId' => $adminId,
                    'label' => $recipient['name'],
                    'description' => 'Admin Portal',
                    'isOnline' => !empty($presence['isOnline']),
                    'isTyping' => !empty($presence['isTyping']),
                    'statusLabel' => isset($presence['statusLabel']) ? $presence['statusLabel'] : 'Offline',
                );
            }
        }

        $staffResult = $connection->query("SELECT staffId FROM stafflogin ORDER BY firstName ASC, surname ASC");
        if ($staffResult) {
            while ($row = $staffResult->fetch_assoc()) {
                $staffId = (int) $row['staffId'];
                if ($identity['role'] === 'staff' && $staffId === (int) $identity['id']) {
                    continue;
                }

                $recipient = dlhsDashboardChatResolveUserMeta($connection, 'staff', $staffId);
                if ($recipient === null) {
                    continue;
                }

                $threadKey = dlhsDashboardChatBuildThreadKey($identity, $recipient);
                $presence = dlhsDashboardChatGetPresenceMeta($connection, $recipient, $threadKey);

                $options[] = array(
                    'group' => 'Staff',
                    'threadKey' => $threadKey,
                    'targetType' => 'direct',
                    'targetRole' => 'staff',
                    'targetId' => $staffId,
                    'label' => $recipient['name'],
                    'description' => 'Staff Portal',
                    'isOnline' => !empty($presence['isOnline']),
                    'isTyping' => !empty($presence['isTyping']),
                    'statusLabel' => isset($presence['statusLabel']) ? $presence['statusLabel'] : 'Offline',
                );
            }
        }

        $studentResult = $connection->query("SELECT studentId FROM studentlogin ORDER BY firstName ASC, surname ASC");
        if ($studentResult) {
            while ($row = $studentResult->fetch_assoc()) {
                $studentId = (int) $row['studentId'];
                $recipient = dlhsDashboardChatResolveUserMeta($connection, 'student', $studentId);
                if ($recipient === null) {
                    continue;
                }

                $threadKey = dlhsDashboardChatBuildThreadKey($identity, $recipient);
                $presence = dlhsDashboardChatGetPresenceMeta($connection, $recipient, $threadKey);

                $options[] = array(
                    'group' => 'Students',
                    'threadKey' => $threadKey,
                    'targetType' => 'direct',
                    'targetRole' => 'student',
                    'targetId' => $studentId,
                    'label' => $recipient['name'],
                    'description' => isset($recipient['description']) ? $recipient['description'] : 'Student',
                    'isOnline' => !empty($presence['isOnline']),
                    'isTyping' => !empty($presence['isTyping']),
                    'statusLabel' => isset($presence['statusLabel']) ? $presence['statusLabel'] : 'Offline',
                );
            }
        }

        return $options;
    }
}

if (!function_exists('dlhsDashboardChatDescribeThread')) {
    function dlhsDashboardChatDescribeThread($connection, $identity, $threadKey)
    {
        if ($threadKey === dlhsDashboardChatBuildBroadcastThreadKey()) {
            return array(
                'threadKey' => $threadKey,
                'label' => 'All users',
                'description' => $identity['role'] === 'admin' ? 'Broadcast announcement' : 'School-wide announcements',
                'canReply' => $identity['role'] === 'admin',
                'readOnly' => $identity['role'] !== 'admin',
                'isOnline' => false,
                'isTyping' => false,
                'statusLabel' => $identity['role'] === 'admin' ? 'Broadcast announcement' : 'School-wide announcements',
            );
        }

        $parsed = dlhsDashboardChatParseThreadKey($threadKey);
        if (!$parsed || $parsed['type'] !== 'direct') {
            return null;
        }

        $otherParticipant = null;
        foreach ($parsed['participants'] as $participant) {
            if ($participant['role'] === $identity['role'] && (int) $participant['id'] === (int) $identity['id']) {
                continue;
            }

            $otherParticipant = $participant;
            break;
        }

        if ($otherParticipant === null) {
            return null;
        }

        $otherMeta = dlhsDashboardChatResolveUserMeta($connection, $otherParticipant['role'], $otherParticipant['id']);
        if ($otherMeta === null) {
            return null;
        }

        $canReply = $identity['role'] !== 'student';
        $presence = dlhsDashboardChatGetPresenceMeta($connection, $otherMeta, $threadKey);

        return array(
            'threadKey' => $threadKey,
            'label' => $otherMeta['name'],
            'description' => isset($otherMeta['description']) ? $otherMeta['description'] : dlhsDashboardChatRoleLabel($otherMeta['role']),
            'canReply' => $canReply,
            'readOnly' => !$canReply,
            'participant' => $otherMeta,
            'isOnline' => !empty($presence['isOnline']),
            'isTyping' => !empty($presence['isTyping']),
            'statusLabel' => isset($presence['statusLabel']) ? $presence['statusLabel'] : 'Offline',
        );
    }
}

if (!function_exists('dlhsDashboardChatFetchVisibleThreads')) {
    function dlhsDashboardChatFetchVisibleThreads($connection, $identity, $limit = 40)
    {
        if (!is_array($identity) || empty($identity['role']) || (int) $identity['id'] <= 0) {
            return array();
        }

        $limit = max(1, min(100, (int) $limit));
        $role = $connection->real_escape_string($identity['role']);
        $id = (int) $identity['id'];

        $query = "SELECT id, threadKey, messageText, createdAt
                  FROM dashboard_chat_messages
                  WHERE threadKey='broadcast:all'
                     OR (senderRole='{$role}' AND senderId={$id})
                     OR (recipientRole='{$role}' AND recipientId={$id})
                  ORDER BY id DESC
                  LIMIT 400";

        $result = $connection->query($query);
        if (!$result) {
            return array();
        }

        $threads = array();
        while ($row = $result->fetch_assoc()) {
            $threadKey = isset($row['threadKey']) ? trim((string) $row['threadKey']) : '';
            if ($threadKey === '' || isset($threads[$threadKey])) {
                continue;
            }

            $meta = dlhsDashboardChatDescribeThread($connection, $identity, $threadKey);
            if ($meta === null) {
                continue;
            }

            $threads[$threadKey] = array(
                'threadKey' => $threadKey,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'readOnly' => !empty($meta['readOnly']),
                'latestMessage' => isset($row['messageText']) ? $row['messageText'] : '',
                'latestAtLabel' => !empty($row['createdAt']) ? date('j M, g:i a', strtotime($row['createdAt'])) : '',
                'statusLabel' => isset($meta['statusLabel']) ? $meta['statusLabel'] : '',
                'isOnline' => !empty($meta['isOnline']),
                'isTyping' => !empty($meta['isTyping']),
                'unreadCount' => dlhsDashboardChatCountUnreadForThread($connection, $identity, $threadKey),
            );

            if (count($threads) >= $limit) {
                break;
            }
        }

        return array_values($threads);
    }
}

if (!function_exists('dlhsDashboardChatFetchMessages')) {
    function dlhsDashboardChatFetchMessages($connection, $identity, $threadKey, $sinceId = 0, $limit = 60)
    {
        $threadKey = trim((string) $threadKey);
        if ($threadKey === '' || !dlhsDashboardChatCanViewThread($identity, $threadKey)) {
            return null;
        }

        $threadKey = $connection->real_escape_string($threadKey);
        $sinceId = (int) $sinceId;
        $limit = max(1, min(120, (int) $limit));

        $query = "SELECT id, messageScope, senderRole, senderId, senderName, recipientRole, recipientId, recipientName, messageText, createdAt, deliveredAt, readAt
                  FROM dashboard_chat_messages
                  WHERE threadKey='{$threadKey}'";

        if ($sinceId > 0) {
            $query .= " AND id > {$sinceId}";
        }

        $query .= " ORDER BY id ASC LIMIT {$limit}";
        $result = $connection->query($query);
        if (!$result) {
            return array();
        }

        $messages = array();
        while ($row = $result->fetch_assoc()) {
            $messages[] = array(
                'id' => (int) $row['id'],
                'messageScope' => isset($row['messageScope']) ? $row['messageScope'] : 'direct',
                'senderRole' => isset($row['senderRole']) ? $row['senderRole'] : 'staff',
                'senderId' => isset($row['senderId']) ? (int) $row['senderId'] : 0,
                'senderName' => isset($row['senderName']) ? $row['senderName'] : 'User',
                'recipientRole' => isset($row['recipientRole']) ? $row['recipientRole'] : '',
                'recipientId' => isset($row['recipientId']) ? (int) $row['recipientId'] : 0,
                'recipientName' => isset($row['recipientName']) ? $row['recipientName'] : '',
                'message' => isset($row['messageText']) ? $row['messageText'] : '',
                'createdAtLabel' => !empty($row['createdAt']) ? date('j M, g:i a', strtotime($row['createdAt'])) : '',
                'initials' => dlhsDashboardChatBuildInitials(isset($row['senderName']) ? $row['senderName'] : ''),
                'deliveryState' => '',
                'deliveryLabel' => '',
            );

            $index = count($messages) - 1;
            $isMine = $messages[$index]['senderRole'] === $identity['role']
                && (int) $messages[$index]['senderId'] === (int) $identity['id'];
            if ($isMine && $messages[$index]['messageScope'] === 'direct') {
                if (!empty($row['readAt'])) {
                    $messages[$index]['deliveryState'] = 'read';
                } elseif (!empty($row['deliveredAt'])) {
                    $messages[$index]['deliveryState'] = 'delivered';
                } else {
                    $messages[$index]['deliveryState'] = 'sent';
                }
                $messages[$index]['deliveryLabel'] = dlhsDashboardChatDeliveryLabel($messages[$index]['deliveryState']);
            }
        }

        return $messages;
    }
}

if (!function_exists('dlhsDashboardChatInsertMessage')) {
    function dlhsDashboardChatInsertMessage($connection, $identity, $targetType, $targetRole, $targetId, $message)
    {
        $message = trim((string) $message);
        if ($message === '') {
            return null;
        }

        $target = dlhsDashboardChatResolveTarget($connection, $identity, $targetType, $targetRole, $targetId);
        if ($target === null) {
            return null;
        }

        $threadKey = $connection->real_escape_string($target['threadKey']);
        $messageScope = $connection->real_escape_string($target['messageScope']);
        $senderRole = $connection->real_escape_string($identity['role']);
        $senderId = (int) $identity['id'];
        $senderName = $connection->real_escape_string($identity['name']);
        $recipientRole = $connection->real_escape_string($target['recipientRole']);
        $recipientId = (int) $target['recipientId'];
        $recipientName = $connection->real_escape_string($target['recipientName']);
        $messageText = $connection->real_escape_string($message);

        $query = "INSERT INTO dashboard_chat_messages(roomKey, threadKey, messageScope, senderRole, senderId, senderName, recipientRole, recipientId, recipientName, messageText)
                  VALUES('{$threadKey}', '{$threadKey}', '{$messageScope}', '{$senderRole}', {$senderId}, '{$senderName}', '{$recipientRole}', {$recipientId}, '{$recipientName}', '{$messageText}')";

        if (!$connection->query($query)) {
            return null;
        }

        return array(
            'threadKey' => $target['threadKey'],
            'label' => $target['label'],
            'description' => $target['description'],
        );
    }
}
