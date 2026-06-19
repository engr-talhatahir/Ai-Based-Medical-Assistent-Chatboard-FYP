<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

// Get messages for sidebar history
$stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute(array($_SESSION['user_id']));
$messages = $stmt->fetchAll();

// Get user email from database - SHOW ACTUAL REGISTERED EMAIL
$userStmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
$userStmt->execute(array($_SESSION['user_id']));
$userData = $userStmt->fetch();

// ALWAYS SHOW EMAIL
$displayEmail = $userData['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Medical.AI | Smart Medical Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #22d3ee;
            --dark-bg: #030712;
            --sidebar-bg: rgba(13, 20, 38, 0.95);
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #f1f5f9;
            --sidebar-text-muted: #94a3b8;
            --sidebar-hover: rgba(99, 102, 241, 0.2);
            --sidebar-glass: rgba(255, 255, 255, 0.04);
            
            --chat-text: #0f172a;
            --chat-text-muted: #64748b;
            --user-bubble: #6366f1;
            --bot-bubble: #ffffff;
            --bot-text: #1e293b;
            --input-bg: rgba(0, 0, 0, 0.05);
            --body-bg: #f0f4f8;
        }
        
        body.chat-dark-mode {
            --dark-bg: #030712;
            --chat-text: #f1f5f9;
            --chat-text-muted: #94a3b8;
            --user-bubble: #6366f1;
            --bot-bubble: rgba(30, 41, 59, 0.95);
            --bot-text: #e2e8f0;
            --input-bg: rgba(255, 255, 255, 0.08);
            --body-bg: #030712;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--body-bg);
            height: 100vh;
            display: flex;
            overflow: hidden;
            transition: background-color 0.3s ease;
        }

        .bg-orbs {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none;
        }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.15; }
        .orb-1 { width: 300px; height: 300px; background: var(--primary); top: -10%; left: -5%; }
        .orb-2 { width: 250px; height: 250px; background: var(--accent); bottom: -5%; right: -5%; }

        /* Sidebar */
        .sidebar { 
            width: 280px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(20px);
            flex-shrink: 0;
            z-index: 100;
            transition: transform 0.3s ease;
            position: relative;
            overflow-y: auto;
        }

        /* Mobile menu button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 101;
            background: var(--primary);
            color: white;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            align-items: center;
            justify-content: center;
        }

        .sidebar-header { padding: 16px 16px; border-bottom: 1px solid var(--sidebar-border); }
        .sidebar-header h2 { font-size: 15px; font-weight: 700; color: var(--sidebar-text); }
        .sidebar-header h2 i { color: var(--accent); margin-right: 6px; }
        .sidebar-header p { font-size: 10px; color: var(--sidebar-text-muted); margin-top: 4px; }

        .quick-links { padding: 12px 14px; border-bottom: 1px solid var(--sidebar-border); }
        .quick-links a { 
            display: flex; align-items: center; gap: 10px; padding: 8px 12px; margin-bottom: 6px;
            background: var(--sidebar-glass); border-radius: 10px; color: var(--sidebar-text-muted);
            text-decoration: none; font-size: 12px; font-weight: 500; transition: 0.2s;
            border: 1px solid transparent;
        }
        .quick-links a i { width: 18px; font-size: 13px; }
        .quick-links a:hover { background: var(--sidebar-hover); border-color: var(--primary); color: #fff; }

        .new-chat-btn {
            margin: 12px 14px; padding: 9px; background: var(--primary);
            color: white; border: none; border-radius: 10px; font-weight: 600;
            font-size: 12px; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .new-chat-btn:hover { background: var(--primary-dark); transform: scale(0.98); }

        .history-section { flex: 1; overflow: hidden; display: flex; flex-direction: column; min-height: 0; }
        .history-header { padding: 12px 16px 6px; font-size: 10px; color: var(--sidebar-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .history { flex: 1; overflow-y: auto; padding: 0 12px; }

        .history-item {
            position: relative; padding: 9px 12px; margin-bottom: 6px;
            background: var(--sidebar-glass); border-radius: 10px; cursor: pointer;
            border: 1px solid transparent; transition: 0.2s;
        }
        .history-item:hover { background: var(--sidebar-hover); border-color: var(--primary); }
        .history-question { font-size: 11px; color: var(--sidebar-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 28px; }
        .history-time { font-size: 9px; color: var(--sidebar-text-muted); margin-top: 4px; }
        .delete-msg-btn { 
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%); 
            background: none; border: none; color: #ef4444; cursor: pointer; font-size: 11px; 
            opacity: 0.5; transition: 0.2s; width: 22px; height: 22px; border-radius: 6px;
        }
        .delete-msg-btn:hover { opacity: 1; background: rgba(239, 68, 68, 0.15); }

        .user-info { padding: 14px; background: rgba(0,0,0,0.25); border-top: 1px solid var(--sidebar-border); display: flex; flex-direction: column; gap: 8px; }
        .user-name { 
            font-size: 11px; 
            color: var(--sidebar-text-muted); 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            word-break: break-all;
            overflow-wrap: break-word;
            max-width: 100%;
        }
        .sidebar-theme-toggle, .clear-all-btn, .logout-btn { 
            width: 100%; padding: 7px 10px; border-radius: 8px; font-size: 11px; cursor: pointer; 
            text-align: center; text-decoration: none; border: 1px solid var(--sidebar-border); 
            transition: 0.2s; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .sidebar-theme-toggle { background: var(--sidebar-glass); color: var(--sidebar-text); }
        .sidebar-theme-toggle:hover { background: var(--sidebar-hover); border-color: var(--primary); }
        .clear-all-btn { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2); }
        .clear-all-btn:hover { background: rgba(239, 68, 68, 0.2); }
        .logout-btn { background: var(--sidebar-glass); color: var(--sidebar-text-muted); }
        .logout-btn:hover { background: var(--sidebar-hover); color: #fff; }

        /* Chat Container */
        .chat-container { 
            flex: 1; display: flex; flex-direction: column; 
            background: transparent;
            width: 100%;
        }
        
        .chat-header { 
            padding: 12px 20px; 
            background: transparent;
            display: flex;
            align-items: center;
        }
        
        .chat-header h1 { font-size: 18px; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .chat-header h1 i { color: var(--primary); margin-right: 6px; }
        .chat-header p { font-size: 10px; color: var(--chat-text-muted); margin-top: 3px; }

        .messages-area { 
            flex: 1; overflow-y: auto; padding: 16px 16px; 
            display: flex; flex-direction: column; gap: 12px;
            background: transparent;
        }
        
        .message { display: flex; gap: 8px; max-width: 85%; align-items: flex-start; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .user-message { align-self: flex-end; flex-direction: row-reverse; }
        
        .message-avatar { 
            width: 32px; height: 32px; border-radius: 10px; 
            display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0;
        }
        .user-message .message-avatar { background: var(--user-bubble); }
        .bot-message .message-avatar { background: rgba(0,0,0,0.05); color: var(--primary); }

        .message-content { 
            padding: 9px 14px; font-size: 13px; line-height: 1.45; border-radius: 16px;
            background: var(--bot-bubble);
            color: var(--bot-text);
            backdrop-filter: blur(4px);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .user-message .message-content { 
            background: var(--user-bubble); 
            border-bottom-right-radius: 4px; color: white;
        }
        .bot-message .message-content { border-bottom-left-radius: 4px; }

        .message-time { font-size: 9px; color: var(--chat-text-muted); margin-top: 5px; }

        .suggestion-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        .chip {
            background: rgba(0,0,0,0.05);
            backdrop-filter: blur(4px);
            border-radius: 30px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 500;
            color: var(--chat-text);
            cursor: pointer;
            transition: 0.2s;
        }
        .chip:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        .chip i {
            margin-right: 6px;
            font-size: 10px;
        }

        .input-area { padding: 12px 16px 20px; background: transparent; }
        .search-container { 
            display: flex; gap: 8px; background: var(--input-bg);
            backdrop-filter: blur(8px);
            border-radius: 14px; padding: 4px 6px 4px 14px;
            transition: 0.2s;
            border: 1px solid rgba(0,0,0,0.1);
        }
        body.chat-dark-mode .search-container {
            border: 1px solid rgba(255,255,255,0.1);
        }
        .search-container:focus-within { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2); }
        .search-input { 
            flex: 1; background: transparent; border: none; color: var(--chat-text); 
            padding: 8px 0; font-size: 13px; outline: none; 
        }
        .search-input::placeholder { color: var(--chat-text-muted); font-size: 12px; }
        .send-btn { 
            background: var(--primary); color: white; border: none; padding: 0 16px; 
            border-radius: 12px; font-size: 12px; font-weight: 600; cursor: pointer;
            transition: 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .send-btn:hover { background: var(--primary-dark); transform: scale(0.97); }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; opacity: 0.4; }

        .no-history-text { text-align: center; color: var(--sidebar-text-muted); padding: 30px 16px; font-size: 11px; }
        
        @keyframes blinkDot { 0%, 100% { opacity: 0.2; } 50% { opacity: 1; } }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }
        .welcome-icon {
            animation: pulse 2s ease-in-out infinite;
            display: inline-block;
        }

        /* Overlay for mobile when sidebar is open */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }
        .sidebar-overlay.active {
            display: block;
        }

        /* ========== RESPONSIVE DESIGN ========== */
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 200;
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .chat-container {
                width: 100%;
            }
            .chat-header {
                padding-left: 65px;
                padding-top: 8px;
                padding-bottom: 8px;
            }
            .chat-header h1 {
                font-size: 16px;
            }
            .chat-header p {
                font-size: 9px;
            }
            .message {
                max-width: 95%;
            }
            .message-content {
                font-size: 12px;
                padding: 8px 12px;
            }
            .message-avatar {
                width: 28px;
                height: 28px;
                font-size: 11px;
            }
            .suggestion-chips {
                gap: 6px;
            }
            .chip {
                padding: 4px 10px;
                font-size: 10px;
            }
            .search-input {
                font-size: 12px;
                padding: 6px 0;
            }
            .send-btn {
                padding: 0 14px;
                font-size: 11px;
            }
            .messages-area {
                padding: 12px;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .chat-header {
                padding-left: 58px;
            }
            .chat-header h1 {
                font-size: 14px;
            }
            .message-content {
                font-size: 11px;
                padding: 7px 10px;
            }
            .message-avatar {
                width: 26px;
                height: 26px;
                font-size: 10px;
            }
            .search-input {
                font-size: 11px;
            }
            .send-btn {
                padding: 0 12px;
                font-size: 10px;
            }
            .chip {
                padding: 3px 8px;
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-comments"></i> Chat History</h2>
            <p>Your medical conversations</p>
        </div>
        <div class="quick-links">
            <a href="symptom_checker.php"><i class="fas fa-stethoscope"></i> Symptom Checker</a>
            <a href="profile.php"><i class="fas fa-user-md"></i> Health Profile</a>
        </div>
        
        <button class="new-chat-btn" onclick="startNewChat()"><i class="fas fa-plus-circle"></i> New Chat</button>

        <div class="history-section">
            <div class="history-header">Recent</div>
            <div class="history" id="historyList">
                <?php foreach ($messages as $msg): ?>
                    <div class="history-item" data-id="<?php echo $msg['id']; ?>" data-message='<?php echo htmlspecialchars($msg['message'], ENT_QUOTES); ?>' data-response='<?php echo htmlspecialchars($msg['response'], ENT_QUOTES); ?>' data-msg-time="<?php echo date('h:i A', strtotime($msg['created_at'])); ?>" data-res-time="<?php echo date('h:i A', strtotime($msg['created_at'])); ?>">
                        <div class="history-question"><?php echo htmlspecialchars(substr($msg['message'], 0, 32)) . (strlen($msg['message']) > 32 ? '...' : ''); ?></div>
                        <div class="history-time"><i class="far fa-clock"></i> <?php echo date('M d, g:i A', strtotime($msg['created_at'])); ?></div>
                        <button class="delete-msg-btn" onclick="event.stopPropagation(); deleteHistoryItem(event, <?php echo $msg['id']; ?>)" title="Delete"><i class="fas fa-trash-alt"></i></button>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                    <div id="noHistoryText" class="no-history-text"><i class="fas fa-inbox"></i> No history yet</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="user-info">
            <button class="clear-all-btn" onclick="clearAllHistory()"><i class="fas fa-trash"></i> Clear History</button>
            <button class="sidebar-theme-toggle" id="themeToggleBtn" onclick="toggleTheme()"><i class="fas fa-moon"></i> Dark Mode</button>
            <div class="user-name"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($displayEmail); ?></div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="chat-container">
        <div class="chat-header">
            <div>
                <h1><i class="fas fa-microchip"></i> Medical.AI</h1>
                <p><i class="fas fa-shield-alt"></i> Llama 3.3 • Real-time Medical Analysis</p>
            </div>
        </div>
        
        <div class="messages-area" id="messagesArea"></div>

        <div class="input-area">
            <div class="search-container">
                <input type="text" class="search-input" id="messageInput" placeholder="Describe your symptoms or ask a health question..." autocomplete="off">
                <button class="send-btn" id="sendBtn"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </div>
    </div>

    <script>
        const messagesArea = document.getElementById('messagesArea');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');
        
        // Function to check if we're on mobile
        function isMobile() {
            return window.innerWidth <= 768;
        }
        
        // Toggle sidebar function - same button opens and closes
        function toggleSidebar() {
            if (sidebar.classList.contains('open')) {
                // Close sidebar
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('active');
            } else {
                // Open sidebar
                sidebar.classList.add('open');
                sidebarOverlay.classList.add('active');
            }
        }
        
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        }
        
        // Setup mobile menu toggle
        if (menuToggle) {
            // Remove any existing listeners to avoid duplicates
            menuToggle.removeEventListener('click', toggleSidebar);
            menuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }
        
        // Close sidebar when clicking overlay
        if (sidebarOverlay) {
            sidebarOverlay.onclick = function() {
                closeSidebar();
            };
        }
        
        // Close sidebar when clicking any link/button inside sidebar on mobile
        document.querySelectorAll('.quick-links a, .new-chat-btn, .logout-btn, .clear-all-btn, .sidebar-theme-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                if (isMobile()) {
                    setTimeout(closeSidebar, 200);
                }
            });
        });
        
        const chatStore = {};
        <?php foreach ($messages as $msg): ?>
        chatStore[<?php echo $msg['id']; ?>] = {
            message: <?php echo json_encode($msg['message']); ?>,
            response: <?php echo json_encode($msg['response']); ?>,
            msgTime: <?php echo json_encode(date('h:i A', strtotime($msg['created_at']))); ?>,
            resTime: <?php echo json_encode(date('h:i A', strtotime($msg['created_at']))); ?>
        };
        <?php endforeach; ?>
        
        function toggleTheme() {
            const isDark = document.body.classList.toggle('chat-dark-mode');
            localStorage.setItem('aiChatDarkTheme', isDark ? 'true' : 'false');
            const toggleBtn = document.getElementById('themeToggleBtn');
            if (isDark) {
                toggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }
        
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('aiChatDarkTheme');
            const toggleBtn = document.getElementById('themeToggleBtn');
            if (savedTheme === 'true') {
                document.body.classList.add('chat-dark-mode');
                toggleBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                document.body.classList.remove('chat-dark-mode');
                toggleBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }
        
        function loadWelcomeMessage() {
            messagesArea.innerHTML = `
                <div class="message bot-message">
                    <div class="message-avatar"><i class="fas fa-brain welcome-icon"></i></div>
                    <div>
                        <div class="message-content">
                            <strong style="font-size: 14px; color: var(--accent);">✨ Welcome to Medical.AI ✨</strong><br><br>
                            Your personal AI-powered medical assistant is here 24/7. I can analyze symptoms, provide health insights, and offer wellness guidance.<br><br>
                            <strong>💡 Quick Tips:</strong><br>
                            • Be specific about your symptoms<br>
                            • Mention duration and severity<br>
                            • For emergencies, call emergency services immediately
                        </div>
                        <div class="suggestion-chips">
                            <div class="chip" onclick="useSuggestion('What are common cold symptoms and how to treat them?')"><i class="fas fa-temperature-high"></i> Cold & Flu</div>
                            <div class="chip" onclick="useSuggestion('I have a headache and feel dizzy, what could be wrong?')"><i class="fas fa-head-side-vr"></i> Headache & Dizziness</div>
                            <div class="chip" onclick="useSuggestion('How can I lower my blood pressure naturally?')"><i class="fas fa-heartbeat"></i> Blood Pressure</div>
                            <div class="chip" onclick="useSuggestion('What foods should I eat for better immunity?')"><i class="fas fa-apple-alt"></i> Immunity Booster</div>
                        </div>
                        <div class="message-time"><i class="far fa-check-circle"></i> Active & Ready</div>
                    </div>
                </div>`;
        }
        
        function useSuggestion(suggestion) {
            messageInput.value = suggestion;
            sendMessage();
        }
        
        function startNewChat() {
            loadWelcomeMessage();
            messageInput.value = '';
            messageInput.focus();
            if (isMobile()) closeSidebar();
        }
        
        function formatMessage(content) {
            let formatted = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
            formatted = formatted.replace(/\n/g, '<br>');
            return formatted;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function addMessage(content, isUser, time = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + (isUser ? 'user-message' : 'bot-message');
            const icon = isUser ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
            const timeText = time || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${icon}</div>
                <div>
                    <div class="message-content">${formatMessage(escapeHtml(content))}</div>
                    <div class="message-time"><i class="far fa-clock"></i> ${timeText}</div>
                </div>
            `;
            messagesArea.appendChild(messageDiv);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
        
        function showTyping() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message';
            typingDiv.id = 'typingIndicator';
            typingDiv.innerHTML = `
                <div class="message-avatar"><i class="fas fa-brain"></i></div>
                <div>
                    <div class="message-content" style="display:flex; align-items:center; gap:8px;">
                        <span>🤔 Analyzing</span>
                        <span style="display:flex; gap:3px;">
                            <span style="animation:blinkDot 1.4s infinite">.</span>
                            <span style="animation:blinkDot 1.4s infinite 0.2s">.</span>
                            <span style="animation:blinkDot 1.4s infinite 0.4s">.</span>
                        </span>
                    </div>
                </div>
            `;
            messagesArea.appendChild(typingDiv);
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
        
        function hideTyping() {
            const t = document.getElementById('typingIndicator');
            if (t) t.remove();
        }
        
        function loadChat(messageId) {
            const chatData = chatStore[messageId];
            if (chatData && chatData.message && chatData.response) {
                messagesArea.innerHTML = '';
                addMessage(chatData.message, true, chatData.msgTime);
                addMessage(chatData.response, false, chatData.resTime);
            } else {
                addMessage('Chat data not found.', false);
            }
            if (isMobile()) closeSidebar();
        }
        
        function attachHistoryClickEvents() {
            document.querySelectorAll('.history-item').forEach(item => {
                const id = item.getAttribute('data-id');
                if (id) {
                    item.onclick = function(e) {
                        if (e.target.classList.contains('delete-msg-btn') || e.target.parentElement.classList.contains('delete-msg-btn')) {
                            return;
                        }
                        loadChat(parseInt(id));
                    };
                }
            });
        }
        
        function addToHistory(message, messageId, response, msgTime, resTime) {
            const historyList = document.getElementById('historyList');
            const noHist = document.getElementById('noHistoryText');
            if (noHist && noHist.parentNode) noHist.remove();
            const msgId = messageId || Date.now();
            if (document.getElementById('history-item-' + msgId)) return;
            
            chatStore[msgId] = {
                message: message,
                response: response,
                msgTime: msgTime,
                resTime: resTime
            };
            
            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';
            historyItem.id = 'history-item-' + msgId;
            historyItem.setAttribute('data-id', msgId);
            historyItem.innerHTML = `
                <div class="history-question"><i class="fas fa-comment"></i> ${escapeHtml(message.substring(0, 28))}${message.length > 28 ? '...' : ''}</div>
                <div class="history-time"><i class="far fa-clock"></i> Just now</div>
                <button class="delete-msg-btn" onclick="event.stopPropagation(); deleteHistoryItem(event, ${msgId})"><i class="fas fa-trash-alt"></i></button>
            `;
            historyItem.onclick = function(e) {
                if (e.target.classList.contains('delete-msg-btn')) return;
                loadChat(msgId);
            };
            historyList.insertBefore(historyItem, historyList.firstChild);
        }
        
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;
            addMessage(message, true);
            messageInput.value = '';
            sendBtn.disabled = true;
            showTyping();
            const formData = new FormData();
            formData.append('message', message);
            try {
                const response = await fetch('process.php', { method: 'POST', body: formData });
                const data = await response.json();
                hideTyping();
                if (data.success) {
                    addMessage(data.response, false);
                    const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    addToHistory(message, data.message_id, data.response, currentTime, currentTime);
                } else {
                    addMessage('I apologize, but I encountered an issue. Please try rephrasing your question.', false);
                }
            } catch (error) {
                hideTyping();
                addMessage('Network connection error. Please check your internet and try again.', false);
            } finally {
                sendBtn.disabled = false;
                messageInput.focus();
            }
        }

        async function deleteHistoryItem(event, messageId) {
            event.stopPropagation();
            if (!confirm('Delete this conversation?')) return;
            try {
                const response = await fetch('delete_message.php?id=' + messageId, { method: 'POST' });
                const data = await response.json();
                if (data.success) {
                    const item = document.getElementById('history-item-' + messageId);
                    if (item) item.remove();
                    delete chatStore[messageId];
                    const remaining = document.querySelectorAll('.history-item').length;
                    if (remaining === 0 && !document.getElementById('noHistoryText')) {
                        const historyList = document.getElementById('historyList');
                        historyList.innerHTML = '<div id="noHistoryText" class="no-history-text"><i class="fas fa-inbox"></i> No history yet</div>';
                    }
                    startNewChat();
                }
            } catch (error) { console.error(error); }
        }

        async function clearAllHistory() {
            if (!confirm('Clear ALL chat history? This cannot be undone.')) return;
            try {
                const response = await fetch('clear_all_messages.php', { method: 'POST' });
                const data = await response.json();
                if (data.success) {
                    const historyList = document.getElementById('historyList');
                    historyList.innerHTML = '<div id="noHistoryText" class="no-history-text"><i class="fas fa-inbox"></i> No history yet</div>';
                    for (let key in chatStore) {
                        delete chatStore[key];
                    }
                    startNewChat();
                }
            } catch (error) { console.error(error); }
        }
        
        loadSavedTheme();
        loadWelcomeMessage();
        attachHistoryClickEvents();
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') sendMessage(); });
        
        // Close sidebar on window resize if screen becomes larger
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>