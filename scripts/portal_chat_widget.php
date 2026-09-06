<?php
if (defined('DLHS_PORTAL_CHAT_WIDGET_RENDERED')) {
    return;
}

define('DLHS_PORTAL_CHAT_WIDGET_RENDERED', true);

$chatRole = '';
$chatUserId = 0;
$chatUserName = 'User';
$chatCanSend = false;

if (isset($_SESSION['studentLoggedIn'])) {
    return;
}

if (isset($_SESSION['adminLoggedIn']) && isset($_SESSION['adminId'])) {
    $chatRole = 'admin';
    $chatUserId = (int) $_SESSION['adminId'];
    $chatUserName = isset($_SESSION['adminName']) && trim((string) $_SESSION['adminName']) !== ''
        ? trim((string) $_SESSION['adminName'])
        : (isset($_SESSION['adminEmail']) ? trim((string) $_SESSION['adminEmail']) : 'Administrator');
    $chatCanSend = true;
} elseif (isset($_SESSION['staffLoggedIn']) && isset($_SESSION['staffId'])) {
    $chatRole = 'staff';
    $chatUserId = (int) $_SESSION['staffId'];
    $chatUserName = isset($_SESSION['staffName']) && trim((string) $_SESSION['staffName']) !== ''
        ? trim((string) $_SESSION['staffName'])
        : 'Staff';
    $chatCanSend = true;
} elseif (isset($_SESSION['studentLoggedIn']) && isset($_SESSION['studentId'])) {
    return;
}

if ($chatRole === '' || $chatUserId <= 0) {
    return;
}
?>
<style>
    .dlhs-chat-widget-fab {
        position: fixed;
        right: 22px;
        bottom: 22px;
        width: 62px;
        height: 62px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #00AEEF, #66D7FF);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 18px 38px rgba(0, 174, 239, 0.28);
        cursor: pointer;
        z-index: 1400;
    }

    .dlhs-chat-widget-fab-badge {
        position: absolute;
        top: -4px;
        right: -2px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ff4f7a;
        color: #fff;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        border: 2px solid rgba(255, 255, 255, 0.96);
        box-shadow: 0 8px 18px rgba(233, 30, 99, 0.24);
    }

    .dlhs-chat-widget-window {
        position: fixed;
        right: 22px;
        bottom: 96px;
        width: min(400px, calc(100vw - 28px));
        height: 540px;
        border-radius: 26px;
        overflow: hidden;
        display: none;
        flex-direction: column;
        background: rgba(255, 255, 255, 0.74);
        border: 1px solid rgba(255, 255, 255, 0.78);
        box-shadow: 0 26px 60px rgba(9, 27, 53, 0.24);
        backdrop-filter: blur(22px) saturate(160%);
        -webkit-backdrop-filter: blur(22px) saturate(160%);
        z-index: 1399;
    }

    .dlhs-chat-widget-window.is-open {
        display: flex;
    }

    .dlhs-chat-widget-header {
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        background: rgba(2, 11, 26, 0.94);
        color: #fff;
    }

    .dlhs-chat-widget-header h3,
    .dlhs-chat-widget-header p {
        margin: 0;
    }

    .dlhs-chat-widget-header h3 {
        font-size: 12px;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        font-weight: 800;
    }

    .dlhs-chat-widget-header p {
        margin-top: 6px;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.72);
    }

    .dlhs-chat-widget-close {
        appearance: none;
        background: transparent;
        border: 0;
        color: rgba(255, 255, 255, 0.76);
        font-size: 18px;
        cursor: pointer;
    }

    .dlhs-chat-widget-toolbar {
        padding: 14px 16px 10px;
        background: rgba(255, 255, 255, 0.56);
        border-bottom: 1px solid rgba(17, 39, 63, 0.06);
    }

    .dlhs-chat-widget-label {
        display: block;
        margin-bottom: 8px;
        color: #405870;
        font-size: 11px;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-weight: 800;
    }

    .dlhs-chat-widget-search,
    .dlhs-chat-widget-select {
        width: 100%;
        min-height: 44px;
        border-radius: 14px;
        border: 1px solid rgba(17, 39, 63, 0.1);
        padding: 0 14px;
        background: rgba(255, 255, 255, 0.94);
        color: #16314D;
        font: inherit;
    }

    .dlhs-chat-widget-search {
        margin-bottom: 10px;
    }

    .dlhs-chat-widget-hint {
        margin-top: 8px;
        color: #5A7086;
        font-size: 11px;
        line-height: 1.45;
        font-weight: 600;
        min-height: 16px;
    }

    .dlhs-chat-widget-state {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #90F0BB;
    }

    .dlhs-chat-widget-state-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #35D07F;
    }

    .dlhs-chat-widget-state.is-connecting,
    .dlhs-chat-widget-state.is-error {
        color: #FFD49D;
    }

    .dlhs-chat-widget-state.is-connecting .dlhs-chat-widget-state-dot,
    .dlhs-chat-widget-state.is-error .dlhs-chat-widget-state-dot {
        background: #FFB44F;
    }

    .dlhs-chat-widget-messages {
        flex: 1;
        padding: 18px;
        overflow-y: auto;
        background: rgba(255, 255, 255, 0.34);
        display: grid;
        gap: 12px;
    }

    .dlhs-chat-widget-empty {
        padding: 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.76);
        color: #50667A;
        font-size: 13px;
        line-height: 1.6;
    }

    .dlhs-chat-widget-message {
        display: flex;
        gap: 10px;
        align-items: flex-end;
        max-width: 92%;
    }

    .dlhs-chat-widget-message.is-mine {
        margin-left: auto;
        flex-direction: row-reverse;
    }

    .dlhs-chat-widget-avatar {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.9);
        color: #17314D;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        flex-shrink: 0;
        border: 1px solid rgba(17, 39, 63, 0.08);
    }

    .dlhs-chat-widget-bubble {
        padding: 12px 14px;
        border-radius: 18px 18px 18px 6px;
        background: rgba(255, 255, 255, 0.9);
        color: #14304C;
        font-size: 13px;
        line-height: 1.55;
        border: 1px solid rgba(17, 39, 63, 0.07);
        box-shadow: 0 10px 20px rgba(9, 27, 53, 0.08);
    }

    .dlhs-chat-widget-message.is-mine .dlhs-chat-widget-bubble {
        background: linear-gradient(135deg, #00AEEF, #61D5FF);
        color: #fff;
        border-radius: 18px 18px 6px 18px;
        border-color: transparent;
    }

    .dlhs-chat-widget-meta {
        margin-top: 6px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        opacity: 0.82;
        font-weight: 700;
    }

    .dlhs-chat-widget-compose {
        padding: 16px;
        background: rgba(255, 255, 255, 0.62);
        border-top: 1px solid rgba(17, 39, 63, 0.06);
    }

    .dlhs-chat-widget-compose-row {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .dlhs-chat-widget-input {
        width: 100%;
        resize: none;
        min-height: 52px;
        max-height: 120px;
        border-radius: 18px;
        border: 1px solid rgba(17, 39, 63, 0.09);
        padding: 14px 16px;
        font: inherit;
        color: #16314D;
        background: rgba(255, 255, 255, 0.94);
        outline: none;
    }

    .dlhs-chat-widget-send {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        border: 0;
        background: linear-gradient(135deg, #00AEEF, #60D3FF);
        color: #fff;
        cursor: pointer;
        box-shadow: 0 16px 24px rgba(0, 174, 239, 0.22);
    }

    .dlhs-chat-widget-input:disabled,
    .dlhs-chat-widget-send:disabled,
    .dlhs-chat-widget-select:disabled,
    .dlhs-chat-widget-search:disabled {
        opacity: 0.58;
        cursor: not-allowed;
    }

    .dlhs-chat-widget-help {
        margin-top: 8px;
        color: #5A7086;
        font-size: 11px;
        font-weight: 600;
    }

    @media (max-width: 767px) {
        .dlhs-chat-widget-window {
            right: 12px;
            left: 12px;
            width: auto;
            bottom: 86px;
            height: 70vh;
        }

        .dlhs-chat-widget-fab {
            right: 16px;
            bottom: 16px;
        }
    }
</style>
<button type="button" id="dlhsPortalChatFab" class="dlhs-chat-widget-fab" aria-label="Open chat">
    <i class="fa fa-comments"></i>
    <span id="dlhsPortalChatFabBadge" class="dlhs-chat-widget-fab-badge">0</span>
</button>
<section id="dlhsPortalChatWindow" class="dlhs-chat-widget-window" aria-live="polite">
    <div class="dlhs-chat-widget-header">
        <div>
            <h3 id="dlhsPortalChatTitle">School Chat</h3>
            <p id="dlhsPortalChatSubtitle">Loading your conversations...</p>
        </div>
        <div style="display:flex; align-items:center; gap:12px;">
            <div id="dlhsPortalChatState" class="dlhs-chat-widget-state">
                <span class="dlhs-chat-widget-state-dot"></span>
                <span>Connecting</span>
            </div>
            <button type="button" id="dlhsPortalChatClose" class="dlhs-chat-widget-close" aria-label="Close chat">
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <div class="dlhs-chat-widget-toolbar">
        <label class="dlhs-chat-widget-label" for="dlhsPortalChatThreadSelect">Conversation</label>
        <input type="search" id="dlhsPortalChatSearch" class="dlhs-chat-widget-search" placeholder="Search for a person...">
        <select id="dlhsPortalChatThreadSelect" class="dlhs-chat-widget-select"></select>
        <div id="dlhsPortalChatHint" class="dlhs-chat-widget-hint">Loading conversations...</div>
    </div>
    <div id="dlhsPortalChatMessages" class="dlhs-chat-widget-messages">
        <div class="dlhs-chat-widget-empty">Loading your messages...</div>
    </div>
    <div id="dlhsPortalChatCompose" class="dlhs-chat-widget-compose">
        <div id="dlhsPortalChatComposeRow" class="dlhs-chat-widget-compose-row">
            <textarea id="dlhsPortalChatInput" class="dlhs-chat-widget-input" placeholder="Type your message..." aria-label="Chat message"></textarea>
            <button type="button" id="dlhsPortalChatSend" class="dlhs-chat-widget-send" aria-label="Send message">
                <i class="fa fa-paper-plane"></i>
            </button>
        </div>
        <div id="dlhsPortalChatHelp" class="dlhs-chat-widget-help">Pick a conversation to get started.</div>
    </div>
</section>
<script>
    (function () {
        if (window.dlhsPortalChatWidgetLoaded) {
            return;
        }
        window.dlhsPortalChatWidgetLoaded = true;

        var chatConfig = {
            bootstrapUrl: <?php echo json_encode('../../dashboard_chat_bootstrap.php'); ?>,
            fetchUrl: <?php echo json_encode('../../dashboard_chat_fetch.php'); ?>,
            sendUrl: <?php echo json_encode('../../dashboard_chat_send.php'); ?>,
            presenceUrl: <?php echo json_encode('../../dashboard_chat_presence.php'); ?>,
            currentUserRole: <?php echo json_encode($chatRole); ?>,
            currentUserId: <?php echo (int) $chatUserId; ?>,
            currentUserName: <?php echo json_encode($chatUserName); ?>,
            canSend: <?php echo $chatCanSend ? 'true' : 'false'; ?>,
            recipients: {},
            allRecipients: [],
            threads: {},
            allThreads: [],
            currentTarget: null,
            currentThreadKey: '',
            bootstrapLoaded: false,
            unreadTotal: 0,
            typingTimer: null
        };

        function getNode(id) {
            return document.getElementById(id);
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setChatState(mode, label) {
            var stateNode = getNode('dlhsPortalChatState');
            if (!stateNode) {
                return;
            }

            stateNode.className = 'dlhs-chat-widget-state' + (mode === 'ok' ? '' : ' is-' + mode);
            stateNode.innerHTML = '<span class="dlhs-chat-widget-state-dot"></span><span>' + escapeHtml(label) + '</span>';
        }

        function setChatCopy(title, subtitle) {
            var titleNode = getNode('dlhsPortalChatTitle');
            var subtitleNode = getNode('dlhsPortalChatSubtitle');

            if (titleNode) {
                titleNode.textContent = title || 'School Chat';
            }
            if (subtitleNode) {
                subtitleNode.textContent = subtitle || '';
            }
        }

        function setChatHint(text) {
            var hintNode = getNode('dlhsPortalChatHint');
            if (hintNode) {
                hintNode.textContent = text || '';
            }
        }

        function updateUnreadBadge(total) {
            var badgeNode = getNode('dlhsPortalChatFabBadge');
            var count = parseInt(total, 10) || 0;
            chatConfig.unreadTotal = count;

            if (!badgeNode) {
                return;
            }

            badgeNode.textContent = count > 99 ? '99+' : String(count);
            badgeNode.style.display = count > 0 ? 'inline-flex' : 'none';
        }

        function getActiveMap() {
            return chatConfig.canSend ? chatConfig.recipients : chatConfig.threads;
        }

        function formatThreadSubtitle(item) {
            if (!item) {
                return 'Choose a conversation to get started.';
            }

            return item.statusLabel || item.description || '';
        }

        function formatOptionLabel(item) {
            var parts = [item.label || 'Conversation'];

            if (item.description) {
                parts.push(item.description);
            }

            if (item.statusLabel) {
                parts.push(item.statusLabel);
            }

            if (item.unreadCount) {
                parts.push(item.unreadCount + ' new');
            }

            return parts.join(' - ');
        }

        function syncThreadState(item) {
            if (!item) {
                setChatCopy('School Chat', 'Choose a conversation to get started.');
                setChatHint(chatConfig.canSend ? 'Select who you want to message.' : 'Choose a conversation to read your messages.');
                return;
            }

            setChatCopy(item.label || 'School Chat', formatThreadSubtitle(item));

            if (chatConfig.canSend) {
                if (item.targetType === 'broadcast') {
                    setChatHint('This announcement goes to everyone.');
                } else if (item.isTyping) {
                    setChatHint(item.label + ' is typing...');
                } else if (item.isOnline) {
                    setChatHint(item.label + ' is online now.');
                } else {
                    setChatHint('Only people in this conversation can see these messages.');
                }
            } else if (item.readOnly) {
                setChatHint('This conversation is read only.');
            } else {
                setChatHint(item.statusLabel || item.description || '');
            }
        }

        function updateComposerState() {
            var compose = getNode('dlhsPortalChatCompose');
            var composeRow = getNode('dlhsPortalChatComposeRow');
            var input = getNode('dlhsPortalChatInput');
            var sendButton = getNode('dlhsPortalChatSend');
            var help = getNode('dlhsPortalChatHelp');
            var hasTarget = !!chatConfig.currentThreadKey;
            var isReadOnly = !chatConfig.canSend || !chatConfig.currentTarget;

            if (compose) {
                compose.style.display = 'block';
            }

            if (composeRow) {
                composeRow.style.display = chatConfig.canSend ? 'flex' : 'none';
            }

            if (input) {
                input.disabled = isReadOnly;
                if (!hasTarget) {
                    input.placeholder = 'Choose a conversation first...';
                } else if (chatConfig.currentTarget && chatConfig.currentTarget.targetType === 'broadcast') {
                    input.placeholder = 'Write a broadcast message to all users...';
                } else if (chatConfig.currentTarget) {
                    input.placeholder = 'Type your message to ' + chatConfig.currentTarget.label + '...';
                } else {
                    input.placeholder = 'Messages are read only here...';
                }
            }

            if (sendButton) {
                sendButton.disabled = isReadOnly;
                sendButton.style.display = chatConfig.canSend ? 'inline-flex' : 'none';
                sendButton.style.alignItems = 'center';
                sendButton.style.justifyContent = 'center';
            }

            if (help) {
                if (!hasTarget) {
                    help.textContent = 'Pick a conversation to get started.';
                } else if (!chatConfig.canSend) {
                    help.textContent = 'Students can read messages here, but cannot reply.';
                } else if (chatConfig.currentTarget && chatConfig.currentTarget.targetType === 'broadcast') {
                    help.textContent = 'This announcement will be visible to everyone.';
                } else {
                    help.textContent = 'Only people in this conversation can see these messages.';
                }
            }
        }

        function buildMessageNode(message) {
            var isMine = message.senderRole === chatConfig.currentUserRole && parseInt(message.senderId, 10) === parseInt(chatConfig.currentUserId, 10);
            var wrapper = document.createElement('div');
            wrapper.className = 'dlhs-chat-widget-message' + (isMine ? ' is-mine' : '');
            wrapper.setAttribute('data-message-id', message.id);

            var avatar = document.createElement('div');
            avatar.className = 'dlhs-chat-widget-avatar';
            avatar.textContent = message.initials || 'DL';

            var bubble = document.createElement('div');
            bubble.className = 'dlhs-chat-widget-bubble';

            var text = document.createElement('div');
            text.textContent = message.message || '';

            var meta = document.createElement('div');
            meta.className = 'dlhs-chat-widget-meta';
            var metaParts = [
                isMine ? 'You' : (message.senderName || 'User'),
                message.createdAtLabel || ''
            ];
            if (isMine && message.deliveryLabel) {
                metaParts.push(message.deliveryLabel);
            }
            meta.textContent = metaParts.join(' - ');

            bubble.appendChild(text);
            bubble.appendChild(meta);
            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);

            return wrapper;
        }

        function renderMessages(messages) {
            var container = getNode('dlhsPortalChatMessages');
            if (!container) {
                return;
            }

            if (!chatConfig.currentThreadKey) {
                container.innerHTML = '<div class="dlhs-chat-widget-empty">Choose a conversation to view messages.</div>';
                return;
            }

            container.innerHTML = '';

            if (!messages.length) {
                container.innerHTML = '<div class="dlhs-chat-widget-empty">No messages yet in this conversation.</div>';
                return;
            }

            for (var i = 0; i < messages.length; i += 1) {
                container.appendChild(buildMessageNode(messages[i]));
            }
            container.scrollTop = container.scrollHeight;
        }

        function setActiveThread(threadKey, shouldFetch) {
            var activeSource = getActiveMap();
            var item = activeSource[threadKey] || null;

            chatConfig.currentThreadKey = item ? threadKey : '';
            chatConfig.currentTarget = item || null;

            if (!item) {
                syncThreadState(null);
                renderMessages([]);
                updateComposerState();
                return;
            }

            syncThreadState(item);
            updateComposerState();

            if (shouldFetch !== false) {
                fetchMessages();
            }
        }

        function renderOptions(filterText, preferredThreadKey) {
            var select = getNode('dlhsPortalChatThreadSelect');
            var searchInput = getNode('dlhsPortalChatSearch');
            if (!select) {
                return;
            }

            select.innerHTML = '';
            filterText = (filterText || '').toLowerCase();

            if (chatConfig.canSend) {
                var groups = {};
                chatConfig.allRecipients.forEach(function (target) {
                    var haystack = ((target.label || '') + ' ' + (target.description || '') + ' ' + (target.group || '') + ' ' + (target.statusLabel || '')).toLowerCase();
                    if (filterText && haystack.indexOf(filterText) === -1) {
                        return;
                    }
                    var groupName = target.group || 'Conversations';
                    if (!groups[groupName]) {
                        groups[groupName] = [];
                    }
                    groups[groupName].push(target);
                });

                Object.keys(groups).forEach(function (groupName) {
                    var optgroup = document.createElement('optgroup');
                    optgroup.label = groupName;

                    groups[groupName].forEach(function (target) {
                        var option = document.createElement('option');
                        option.value = target.threadKey;
                        option.textContent = formatOptionLabel(target);
                        optgroup.appendChild(option);
                    });

                    select.appendChild(optgroup);
                });
            } else {
                chatConfig.allThreads.forEach(function (thread) {
                    var haystack = ((thread.label || '') + ' ' + (thread.description || '') + ' ' + (thread.statusLabel || '')).toLowerCase();
                    if (filterText && haystack.indexOf(filterText) === -1) {
                        return;
                    }
                    var option = document.createElement('option');
                    option.value = thread.threadKey;
                    option.textContent = formatOptionLabel(thread);
                    select.appendChild(option);
                });
            }

            if (!select.options.length) {
                var emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = chatConfig.canSend ? 'No conversations available' : 'No messages available';
                select.appendChild(emptyOption);
                select.disabled = true;
                if (searchInput) {
                    searchInput.disabled = false;
                }
                chatConfig.currentThreadKey = '';
                chatConfig.currentTarget = null;
                syncThreadState(null);
                renderMessages([]);
                updateComposerState();
                if (filterText) {
                    setChatHint('No match found for "' + filterText + '".');
                }
                return;
            }

            select.disabled = false;
            if (searchInput) {
                searchInput.disabled = false;
            }

            var activeMap = getActiveMap();
            var defaultThreadKey = preferredThreadKey && activeMap[preferredThreadKey]
                ? preferredThreadKey
                : select.options[0].value;

            select.value = defaultThreadKey;
            setActiveThread(defaultThreadKey, false);
            if (filterText && select.options.length > 1) {
                setChatHint(select.options.length + ' matches found. Showing the closest match first.');
            }
        }

        function populateOptions(response) {
            var activeThreadKey = chatConfig.currentThreadKey;
            chatConfig.recipients = {};
            chatConfig.threads = {};
            chatConfig.allRecipients = response.recipients || [];
            chatConfig.allThreads = response.threads || [];

            chatConfig.allRecipients.forEach(function (target) {
                chatConfig.recipients[target.threadKey] = target;
            });

            chatConfig.allThreads.forEach(function (thread) {
                chatConfig.threads[thread.threadKey] = thread;
            });

            renderOptions('', activeThreadKey || response.defaultThreadKey);
        }

        function loadBootstrap(callback, forceRefresh) {
            if (chatConfig.bootstrapLoaded && !forceRefresh) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            setChatState('connecting', 'Connecting');

            var bootstrapUrl = chatConfig.bootstrapUrl + '?threadKey=' + encodeURIComponent(chatConfig.currentThreadKey || '');
            fetch(bootstrapUrl, { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.ok) {
                        throw new Error('Unable to load chat');
                    }

                    chatConfig.bootstrapLoaded = true;
                    chatConfig.canSend = !!response.canSend;
                    if (response.identity && response.identity.id) {
                        chatConfig.currentUserId = response.identity.id;
                    }
                    if (response.identity && response.identity.name) {
                        chatConfig.currentUserName = response.identity.name;
                    }

                    updateUnreadBadge(response.unreadTotal || 0);
                    populateOptions(response);
                    if (chatConfig.currentThreadKey) {
                        syncThreadState(getActiveMap()[chatConfig.currentThreadKey] || chatConfig.currentTarget);
                    }
                    setChatState('ok', 'Connected');

                    if (typeof callback === 'function') {
                        callback();
                    }
                })
                .catch(function () {
                    setChatState('error', 'Offline');
                    setChatCopy('School Chat', 'We could not load chat right now.');
                    setChatHint('Please refresh the page and try again.');
                    renderMessages([], false);
                });
        }

        function fetchMessages() {
            if (!chatConfig.currentThreadKey) {
                return;
            }

            setChatState('connecting', 'Syncing');

            var url = chatConfig.fetchUrl + '?threadKey=' + encodeURIComponent(chatConfig.currentThreadKey) + '&sinceId=0';
            fetch(url, { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.ok) {
                        throw new Error('Unable to load conversation');
                    }

                    renderMessages(response.messages || []);
                    updateUnreadBadge(response.unreadTotal || 0);
                    if (response.thread) {
                        var activeMap = getActiveMap();
                        if (activeMap[chatConfig.currentThreadKey]) {
                            activeMap[chatConfig.currentThreadKey].statusLabel = response.thread.statusLabel || activeMap[chatConfig.currentThreadKey].statusLabel;
                            activeMap[chatConfig.currentThreadKey].isOnline = !!response.thread.isOnline;
                            activeMap[chatConfig.currentThreadKey].isTyping = !!response.thread.isTyping;
                            activeMap[chatConfig.currentThreadKey].unreadCount = 0;
                            chatConfig.currentTarget = activeMap[chatConfig.currentThreadKey];
                        }
                        syncThreadState(chatConfig.currentTarget || response.thread);
                    }
                    setChatState('ok', 'Connected');
                })
                .catch(function () {
                    setChatState('error', 'Offline');
                });
        }

        function refreshPresence(isTyping) {
            var url = chatConfig.presenceUrl
                + '?threadKey=' + encodeURIComponent(chatConfig.currentThreadKey || '')
                + '&typing=' + (isTyping ? '1' : '0');

            fetch(url, { credentials: 'same-origin' })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.ok) {
                        return;
                    }

                    updateUnreadBadge(response.unreadTotal || 0);
                    if (response.thread && chatConfig.currentThreadKey) {
                        var activeMap = getActiveMap();
                        if (activeMap[chatConfig.currentThreadKey]) {
                            activeMap[chatConfig.currentThreadKey].statusLabel = response.thread.statusLabel || activeMap[chatConfig.currentThreadKey].statusLabel;
                            activeMap[chatConfig.currentThreadKey].isOnline = !!response.thread.isOnline;
                            activeMap[chatConfig.currentThreadKey].isTyping = !!response.thread.isTyping;
                            chatConfig.currentTarget = activeMap[chatConfig.currentThreadKey];
                            syncThreadState(chatConfig.currentTarget);
                            updateComposerState();
                        }
                    }
                })
                .catch(function () {});
        }

        function sendMessage() {
            var input = getNode('dlhsPortalChatInput');
            if (!input || !chatConfig.currentTarget || !chatConfig.canSend) {
                return;
            }

            var message = input.value.replace(/\r\n/g, '\n').trim();
            if (!message) {
                input.focus();
                return;
            }

            var payload = new FormData();
            payload.append('message', message);
            payload.append('targetType', chatConfig.currentTarget.targetType);
            payload.append('targetRole', chatConfig.currentTarget.targetRole);
            payload.append('targetId', chatConfig.currentTarget.targetId);

            input.disabled = true;
            setChatState('connecting', 'Sending');

            fetch(chatConfig.sendUrl, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.ok) {
                        throw new Error('Unable to send message');
                    }

                    input.value = '';
                    input.disabled = false;
                    input.focus();
                    updateUnreadBadge(response.unreadTotal || chatConfig.unreadTotal);
                    loadBootstrap(null, true);
                    fetchMessages();
                })
                .catch(function () {
                    input.disabled = false;
                    setChatState('error', 'Retry');
                });
        }

        function toggleWindow(forceOpen) {
            var windowNode = getNode('dlhsPortalChatWindow');
            var fab = getNode('dlhsPortalChatFab');
            if (!windowNode || !fab) {
                return;
            }

            var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !windowNode.classList.contains('is-open');
            windowNode.classList.toggle('is-open', shouldOpen);
            fab.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

            if (shouldOpen) {
                loadBootstrap(function () {
                    var select = getNode('dlhsPortalChatThreadSelect');
                    if (select && select.value) {
                        setActiveThread(select.value, false);
                        fetchMessages();
                        refreshPresence(false);
                    }
                }, true);
            } else {
                refreshPresence(false);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var fab = getNode('dlhsPortalChatFab');
            var closeButton = getNode('dlhsPortalChatClose');
            var sendButton = getNode('dlhsPortalChatSend');
            var select = getNode('dlhsPortalChatThreadSelect');
            var searchInput = getNode('dlhsPortalChatSearch');
            var input = getNode('dlhsPortalChatInput');

            updateComposerState();
            loadBootstrap(null, true);

            if (fab) {
                fab.addEventListener('click', function () {
                    toggleWindow();
                });
            }

            if (closeButton) {
                closeButton.addEventListener('click', function () {
                    toggleWindow(false);
                });
            }

            if (sendButton) {
                sendButton.addEventListener('click', sendMessage);
            }

            if (select) {
                select.addEventListener('change', function () {
                    setActiveThread(this.value, true);
                    refreshPresence(false);
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    renderOptions(this.value, chatConfig.currentThreadKey);
                    var selectNode = getNode('dlhsPortalChatThreadSelect');
                    if (selectNode && selectNode.value) {
                        setActiveThread(selectNode.value, false);
                    }
                });
                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        var selectNode = getNode('dlhsPortalChatThreadSelect');
                        if (selectNode && selectNode.value) {
                            setActiveThread(selectNode.value, true);
                        }
                    }
                });
            }

            if (input) {
                input.addEventListener('input', function () {
                    if (!chatConfig.currentThreadKey || !chatConfig.canSend) {
                        return;
                    }

                    refreshPresence(true);
                    window.clearTimeout(chatConfig.typingTimer);
                    chatConfig.typingTimer = window.setTimeout(function () {
                        refreshPresence(false);
                    }, 1200);
                });
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    toggleWindow(false);
                }
            });

            window.setInterval(function () {
                var windowNode = getNode('dlhsPortalChatWindow');
                loadBootstrap(null, true);
                if (windowNode && windowNode.classList.contains('is-open') && chatConfig.currentThreadKey) {
                    fetchMessages();
                }
                refreshPresence(false);
            }, 8000);
        });
    })();
</script>
