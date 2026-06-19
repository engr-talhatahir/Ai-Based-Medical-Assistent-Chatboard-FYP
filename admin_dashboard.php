<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$stats = array();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$stats['total_admins'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_messages");
$stats['total_messages'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM chat_messages WHERE DATE(created_at) = CURDATE()");
$stats['today_messages'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as active FROM chat_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['active_users'] = $stmt->fetch()['active'];

// Messages per day (last 30 days)
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                      FROM chat_messages 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                      GROUP BY DATE(created_at) 
                      ORDER BY date");
$chartData = $stmt->fetchAll();

$dates = array();
$dailyCounts = array();
foreach ($chartData as $row) {
    $dates[] = $row['date'];
    $dailyCounts[] = $row['count'];
}

// Top health topics
$stmt = $pdo->query("SELECT LOWER(message) as msg FROM chat_messages");
$topics = array(
    'fever' => 0, 'cough' => 0, 'headache' => 0, 'pain' => 0, 
    'diabetes' => 0, 'blood pressure' => 0, 'cold' => 0, 'flu' => 0
);
while ($row = $stmt->fetch()) {
    foreach ($topics as $topic => $count) {
        if (strpos($row['msg'], $topic) !== false) $topics[$topic]++;
    }
}

// User registration trend
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                      FROM users 
                      WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                      GROUP BY DATE(created_at) 
                      ORDER BY date");
$userRegData = $stmt->fetchAll();

$regDates = array();
$regCounts = array();
foreach ($userRegData as $row) {
    $regDates[] = $row['date'];
    $regCounts[] = $row['count'];
}

// Most active users
$stmt = $pdo->query("SELECT u.username, COUNT(cm.id) as message_count 
                      FROM users u 
                      JOIN chat_messages cm ON u.id = cm.user_id 
                      GROUP BY u.id 
                      ORDER BY message_count DESC 
                      LIMIT 10");
$activeUsers = $stmt->fetchAll();

$activeUserNames = array();
$activeUserCounts = array();
foreach ($activeUsers as $user) {
    $activeUserNames[] = $user['username'];
    $activeUserCounts[] = $user['message_count'];
}

// Messages by hour
$stmt = $pdo->query("SELECT HOUR(created_at) as hour, COUNT(*) as count 
                      FROM chat_messages 
                      GROUP BY HOUR(created_at) 
                      ORDER BY hour");
$hourlyData = $stmt->fetchAll();

$hourlyCounts = array_fill(0, 24, 0);
foreach ($hourlyData as $row) {
    $hourlyCounts[$row['hour']] = $row['count'];
}

// Symptom checks
$hasSymptomTable = false;
$symptomStats = array('total' => 0, 'avg_severity' => 0);
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM symptom_checks");
    $symptomStats['total'] = $stmt->fetch()['total'];
    $stmt = $pdo->query("SELECT AVG(severity) as avg_severity FROM symptom_checks");
    $avgResult = $stmt->fetch();
    $symptomStats['avg_severity'] = round(isset($avgResult['avg_severity']) ? $avgResult['avg_severity'] : 0, 1);
    $hasSymptomTable = true;
} catch (PDOException $e) { $hasSymptomTable = false; }

$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_messages = $pdo->query("SELECT cm.*, u.username FROM chat_messages cm JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 10")->fetchAll();
$all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$all_messages = $pdo->query("SELECT cm.*, u.username FROM chat_messages cm JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC LIMIT 50")->fetchAll();

// Get admin email
$adminStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$adminStmt->execute(array($_SESSION['user_id']));
$adminEmail = $adminStmt->fetch()['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Panel | Medical.AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --card-bg: #ffffff;
            --card-text: #1e293b;
            --card-text-muted: #64748b;
            --body-bg: #f1f5f9;
            --border-color: #e2e8f0;
        }
        
        body.dark-mode {
            --dark-bg: #030712;
            --sidebar-bg: rgba(13, 20, 38, 0.98);
            --sidebar-border: rgba(255, 255, 255, 0.08);
            --sidebar-text: #f1f5f9;
            --sidebar-text-muted: #94a3b8;
            --sidebar-hover: rgba(99, 102, 241, 0.2);
            --card-bg: #1e293b;
            --card-text: #f1f5f9;
            --card-text-muted: #94a3b8;
            --body-bg: #0f172a;
            --border-color: #334155;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--body-bg);
            min-height: 100vh;
            transition: background-color 0.3s ease;
            overflow-x: hidden;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 201;
            background: var(--primary);
            color: white;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Sidebar Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 199;
        }
        .sidebar-overlay.active {
            display: block;
        }

        .sidebar { 
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            backdrop-filter: blur(20px);
            z-index: 200;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-header { padding: 16px 20px; border-bottom: 1px solid var(--sidebar-border); }
        .sidebar-header h2 { font-size: 16px; font-weight: 700; color: var(--sidebar-text); }
        .sidebar-header h2 i { color: var(--accent); margin-right: 6px; }
        .sidebar-header p { font-size: 10px; color: var(--sidebar-text-muted); margin-top: 4px; }

        .nav-menu { padding: 16px 0; }
        .nav-item { 
            padding: 10px 20px; display: flex; align-items: center; gap: 12px; 
            cursor: pointer; transition: 0.2s; border-left: 3px solid transparent;
            color: var(--sidebar-text-muted); font-size: 13px; font-weight: 500;
        }
        .nav-item i { width: 20px; font-size: 14px; }
        .nav-item:hover, .nav-item.active { 
            background: var(--sidebar-hover); 
            border-left-color: var(--primary); 
            color: var(--sidebar-text);
        }

        .main-content { 
            margin-left: 260px; 
            padding: 20px 24px; 
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            width: calc(100% - 260px);
        }
        
        .top-bar { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
        }
        .page-title { font-size: 22px; font-weight: 700; color: var(--card-text); }
        .admin-info { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .admin-email { font-size: 12px; color: var(--card-text-muted); background: var(--card-bg); padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-color); }
        .theme-btn, .refresh-btn, .logout-btn { 
            padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500;
            cursor: pointer; border: none; transition: 0.2s; text-decoration: none; display: inline-flex;
            align-items: center; gap: 6px;
        }
        .theme-btn { background: var(--primary); color: white; }
        .refresh-btn { background: var(--card-bg); color: var(--card-text); border: 1px solid var(--border-color); }
        .logout-btn { background: #ef4444; color: white; }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(6, 1fr); 
            gap: 16px; 
            margin-bottom: 24px;
        }
        .stat-card { 
            background: var(--card-bg); border-radius: 16px; padding: 16px; 
            border: 1px solid var(--border-color); transition: 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { font-size: 28px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--card-text); }
        .stat-label { font-size: 11px; color: var(--card-text-muted); margin-top: 4px; }

        .section { 
            background: var(--card-bg); border-radius: 16px; padding: 20px; 
            margin-bottom: 24px; border: 1px solid var(--border-color);
        }
        .section-title { 
            font-size: 15px; font-weight: 700; color: var(--card-text); 
            margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .view-all { font-size: 11px; color: var(--primary); cursor: pointer; }

        .chart-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
        canvas { max-height: 200px; width: 100%; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .data-table th, .data-table td { padding: 12px 8px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table th { color: var(--card-text-muted); font-weight: 600; }
        .data-table td { color: var(--card-text); }
        .data-table tr:hover { background: rgba(99, 102, 241, 0.05); }

        .badge { padding: 3px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-admin { background: var(--primary); color: white; }
        .badge-user { background: #64748b; color: white; }
        .badge-active { background: #10b981; color: white; }

        .action-btn { padding: 4px 12px; border-radius: 6px; font-size: 11px; text-decoration: none; display: inline-block; cursor: pointer; }
        .btn-view { background: var(--primary); color: white; border: none; cursor: pointer; }
        .btn-view:hover { background: var(--primary-dark); }
        
        .user-role-select { padding: 4px 8px; border-radius: 6px; font-size: 11px; background: var(--card-bg); border: 1px solid var(--border-color); color: var(--card-text); }

        .modal { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.7); 
            z-index: 1000; 
            justify-content: center; 
            align-items: center; 
        }
        .modal-content { 
            background: var(--card-bg); 
            border-radius: 20px; 
            padding: 24px; 
            max-width: 600px; 
            width: 90%; 
            max-height: 85vh; 
            overflow-y: auto; 
            border: 1px solid var(--border-color);
        }
        .modal-content h3 { 
            color: var(--card-text); 
            margin-bottom: 16px; 
            font-size: 18px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
        }
        .modal-content p { 
            color: var(--card-text-muted); 
            font-size: 12px; 
            margin: 12px 0 6px 0;
            font-weight: 600;
        }
        .modal-message-box { 
            background: rgba(0,0,0,0.05); 
            padding: 14px; 
            border-radius: 12px; 
            margin: 5px 0 10px 0; 
            max-height: 250px; 
            overflow-y: auto; 
            white-space: pre-wrap; 
            word-wrap: break-word; 
            font-size: 13px; 
            color: var(--card-text); 
            line-height: 1.5;
            border: 1px solid var(--border-color);
        }
        .modal-close-btn {
            background: var(--primary);
            color: white;
            padding: 8px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
        }
        .modal-close-btn:hover {
            background: var(--primary-dark);
        }

        .export-btn { background: var(--primary); color: white; padding: 6px 12px; border: none; border-radius: 8px; font-size: 11px; cursor: pointer; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        /* ========== RESPONSIVE DESIGN ========== */
        /* Large Desktop (1200px+) - No scroll, everything fits */
        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            .main-content {
                overflow-x: hidden;
            }
        }

        /* Desktop (992px - 1199px) */
        @media (min-width: 992px) and (max-width: 1199px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .chart-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Tablet (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .chart-row {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile (below 768px) */
        @media (max-width: 767px) {
            .mobile-menu-btn {
                display: block;
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
                padding-top: 70px;
                overflow-x: auto;
            }
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            .admin-info {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .stat-value {
                font-size: 22px;
            }
            .stat-icon {
                font-size: 22px;
            }
            .chart-row {
                grid-template-columns: 1fr;
            }
            .data-table {
                font-size: 10px;
                min-width: 500px;
            }
            .data-table th, .data-table td {
                padding: 8px 6px;
            }
            .section-title {
                font-size: 13px;
                flex-direction: column;
                align-items: flex-start;
            }
            .admin-email {
                font-size: 10px;
            }
            .theme-btn, .refresh-btn, .logout-btn {
                padding: 5px 10px;
                font-size: 10px;
            }
            .section {
                padding: 14px;
            }
        }

        /* Small Mobile (below 480px) */
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .stat-card {
                padding: 12px;
            }
            .stat-value {
                font-size: 20px;
            }
            .modal-content {
                padding: 16px;
                width: 95%;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            <p>Medical.AI Dashboard</p>
        </div>
        <div class="nav-menu">
            <div class="nav-item active" onclick="showSection('dashboard')"><i class="fas fa-chart-line"></i><span>Dashboard</span></div>
            <div class="nav-item" onclick="showSection('analytics')"><i class="fas fa-chart-pie"></i><span>Analytics</span></div>
            <div class="nav-item" onclick="showSection('users')"><i class="fas fa-users"></i><span>Users</span></div>
            <div class="nav-item" onclick="showSection('messages')"><i class="fas fa-comments"></i><span>Messages</span></div>
            <?php if ($hasSymptomTable): ?>
            <div class="nav-item" onclick="showSection('symptoms')"><i class="fas fa-stethoscope"></i><span>Symptom Checks</span></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title" id="pageTitle">Dashboard</h1>
            <div class="admin-info">
                <span class="admin-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($adminEmail); ?></span>
                <button class="theme-btn" onclick="toggleTheme()"><i class="fas fa-moon"></i> Dark</button>
                <button class="refresh-btn" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Refresh</button>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboardSection">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?php echo $stats['total_users']; ?></div><div class="stat-label">Total Users</div></div>
                <div class="stat-card"><div class="stat-icon">👨‍💼</div><div class="stat-value"><?php echo $stats['total_admins']; ?></div><div class="stat-label">Admins</div></div>
                <div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value"><?php echo $stats['total_messages']; ?></div><div class="stat-label">Messages</div></div>
                <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-value"><?php echo $stats['today_messages']; ?></div><div class="stat-label">Today</div></div>
                <div class="stat-card"><div class="stat-icon">🟢</div><div class="stat-value"><?php echo $stats['active_users']; ?></div><div class="stat-label">Active (7d)</div></div>
                <?php if ($hasSymptomTable): ?>
                <div class="stat-card"><div class="stat-icon">🩺</div><div class="stat-value"><?php echo $symptomStats['total']; ?></div><div class="stat-label">Symptom Checks</div></div>
                <?php endif; ?>
            </div>

            <div class="chart-row">
                <div class="section">
                    <div class="section-title">📈 Messages (Last 7 Days)</div>
                    <canvas id="miniMessagesChart" height="150"></canvas>
                </div>
                <div class="section">
                    <div class="section-title">🏥 Top Health Topics</div>
                    <canvas id="miniTopicsChart" height="150"></canvas>
                </div>
            </div>

            <div class="section">
                <div class="section-title">📝 Recent Users <span class="view-all" onclick="showSection('users')">View All →</span></div>
                <div style="overflow-x: auto;">
                    <table class="data-table"><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                    <tbody><?php foreach ($recent_users as $user): ?> 
                    <tr><td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>"><?php echo $user['role']; ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <div class="section-title">💬 Recent Messages <span class="view-all" onclick="showSection('messages')">View All →</span></div>
                <div style="overflow-x: auto;">
                    <table class="data-table"><thead><tr><th>User</th><th>Message</th><th>Time</th></tr></thead>
                    <tbody><?php foreach ($recent_messages as $msg): ?>
                    <tr><td><?php echo htmlspecialchars($msg['username']); ?></td>
                        <td><?php echo htmlspecialchars(substr($msg['message'], 0, 45)) . (strlen($msg['message']) > 45 ? '...' : ''); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div id="analyticsSection" style="display:none;">
            <div class="chart-row">
                <div class="section"><div class="section-title">📊 Messages per Day (30 Days)</div><canvas id="messagesChart" height="200"></canvas></div>
                <div class="section"><div class="section-title">👥 New User Registrations</div><canvas id="userRegChart" height="200"></canvas></div>
            </div>
            <div class="chart-row">
                <div class="section"><div class="section-title">🏥 Most Discussed Topics</div><canvas id="topicsChart" height="200"></canvas></div>
                <div class="section"><div class="section-title">⭐ Most Active Users</div><canvas id="activeUsersChart" height="200"></canvas></div>
            </div>
            <div class="chart-row">
                <div class="section"><div class="section-title">⏰ Activity by Hour</div><canvas id="hourlyChart" height="160"></canvas></div>
                <div class="section"><div class="section-title">📈 Platform Summary</div>
                    <div style="padding: 15px; text-align: center;">
                        <div style="font-size: 42px; font-weight: 800; color: var(--primary);"><?php echo $stats['total_messages']; ?></div>
                        <div style="font-size: 11px; color: var(--card-text-muted);">Total Conversations</div>
                        <hr style="margin: 12px 0; border-color: var(--border-color);">
                        <div style="font-size: 32px; font-weight: 800; color: #10b981;"><?php echo round($stats['total_messages'] / max(1, $stats['total_users']), 1); ?></div>
                        <div style="font-size: 11px; color: var(--card-text-muted);">Avg Msgs/User</div>
                        <hr style="margin: 12px 0; border-color: var(--border-color);">
                        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;"><?php echo round(($stats['active_users'] / max(1, $stats['total_users'])) * 100, 1); ?>%</div>
                        <div style="font-size: 11px; color: var(--card-text-muted);">Engagement Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Section -->
        <div id="usersSection" style="display:none;">
            <div class="section">
                <div class="section-title">👥 All Users Management</div>
                <div style="overflow-x: auto;">
                    <table class="data-table"><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach ($all_users as $user): ?>
                    <tr id="user-row-<?php echo $user['id']; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><form method="POST" action="admin_update_role.php" style="display: inline;"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><select name="role" onchange="this.form.submit()" class="user-role-select" <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>><option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option><option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option></select></form></td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td><?php echo $user['last_login'] ? date('M d, H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td><?php if ($user['id'] != $_SESSION['user_id']): ?><a href="admin_delete_user.php?id=<?php echo $user['id']; ?>" class="action-btn btn-view" style="background:#ef4444;" onclick="return confirm('Delete this user? All their chats will be deleted too.')">Delete</a><?php else: ?><span style="color:#999;">Current</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Messages Section -->
        <div id="messagesSection" style="display:none;">
            <div class="section">
                <div class="section-title">💬 All Chat Messages <button class="export-btn" onclick="exportToCSV()"><i class="fas fa-download"></i> Export</button></div>
                <div style="overflow-x: auto;">
                    <table class="data-table" id="messagesTable">
                        <thead><tr><th>User</th><th>Message</th><th>AI Response</th><th>Time</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($all_messages as $msg): ?>
                        <tr id="msg-row-<?php echo $msg['id']; ?>">
                            <td><?php echo htmlspecialchars($msg['username']); ?></td>
                            <td><?php echo htmlspecialchars(substr($msg['message'], 0, 40)) . (strlen($msg['message']) > 40 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars(substr($msg['response'], 0, 40)) . (strlen($msg['response']) > 40 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></td>
                            <td><button class="action-btn btn-view" onclick="showMessage(<?php echo $msg['id']; ?>)">View</button></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($hasSymptomTable): 
            $symptomChecks = $pdo->query("SELECT sc.*, u.username FROM symptom_checks sc JOIN users u ON sc.user_id = u.id ORDER BY sc.created_at DESC LIMIT 30")->fetchAll();
        ?>
        <div id="symptomsSection" style="display:none;">
            <div class="section">
                <div class="section-title">🩺 Recent Symptom Checks</div>
                <div style="overflow-x: auto;">
                    <table class="data-table"><thead><tr><th>User</th><th>Age/Gender</th><th>Symptoms</th><th>Duration</th><th>Severity</th><th>Time</th></tr></thead>
                    <tbody><?php foreach ($symptomChecks as $check): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($check['username']); ?></td>
                        <td><?php echo $check['age'] . ' / ' . $check['gender']; ?></td>
                        <td><?php echo htmlspecialchars(substr($check['symptoms'], 0, 40)); ?></td>
                        <td><?php echo $check['duration']; ?> days</td>
                        <td><span class="badge" style="background: <?php echo $check['severity'] > 7 ? '#ef4444' : ($check['severity'] > 4 ? '#f59e0b' : '#10b981'); ?>"><?php echo $check['severity']; ?>/10</span></td>
                        <td><?php echo date('M d, H:i', strtotime($check['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Message View Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-envelope"></i> Message Details</h3>
            <p><strong>👤 User Message:</strong></p>
            <div id="modalMessage" class="modal-message-box"></div>
            <p><strong>🤖 AI Response:</strong></p>
            <div id="modalResponse" class="modal-message-box"></div>
            <div style="display: flex; justify-content: flex-end;">
                <button onclick="closeModal()" class="modal-close-btn">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const mobileBtn = document.getElementById('mobileMenuBtn');
        
        function toggleSidebar() {
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            } else {
                sidebar.classList.add('open');
                overlay.classList.add('active');
            }
        }
        
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
        
        if (mobileBtn) {
            mobileBtn.onclick = toggleSidebar;
        }
        overlay.onclick = closeSidebar;
        
        // Close sidebar when clicking nav items on mobile
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 767) {
                    setTimeout(closeSidebar, 300);
                }
            });
        });
        
        // Store messages data
        var messagesData = {};
        <?php foreach ($all_messages as $msg): ?>
        messagesData[<?php echo $msg['id']; ?>] = {
            message: <?php echo json_encode($msg['message']); ?>,
            response: <?php echo json_encode($msg['response']); ?>
        };
        <?php endforeach; ?>
        
        function showMessage(id) {
            var data = messagesData[id];
            if (data) {
                var modal = document.getElementById('messageModal');
                var msgDiv = document.getElementById('modalMessage');
                var resDiv = document.getElementById('modalResponse');
                msgDiv.innerHTML = data.message.replace(/\n/g, '<br>');
                resDiv.innerHTML = data.response.replace(/\n/g, '<br>');
                modal.style.display = 'flex';
            } else {
                alert('Message not found');
            }
        }
        
        function closeModal() { 
            document.getElementById('messageModal').style.display = 'none'; 
        }
        
        window.onclick = function(event) { 
            var modal = document.getElementById('messageModal'); 
            if (event.target == modal) modal.style.display = 'none'; 
        }
        
        // Charts
        var last7Dates = <?php echo json_encode(array_slice($dates, -7)); ?>;
        var last7Counts = <?php echo json_encode(array_slice($dailyCounts, -7)); ?>;
        
        if (document.getElementById('miniMessagesChart')) {
            new Chart(document.getElementById('miniMessagesChart'), {
                type: 'line', 
                data: { labels: last7Dates, datasets: [{ label: 'Messages', data: last7Counts, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 }] },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
            });
        }
        
        if (document.getElementById('miniTopicsChart')) {
            new Chart(document.getElementById('miniTopicsChart'), {
                type: 'pie', 
                data: { labels: <?php echo json_encode(array_keys($topics)); ?>, datasets: [{ data: <?php echo json_encode(array_values($topics)); ?>, backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6'] }] },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 9 } } } } }
            });
        }
        
        if (document.getElementById('messagesChart')) {
            new Chart(document.getElementById('messagesChart'), {
                type: 'line', data: { labels: <?php echo json_encode($dates); ?>, datasets: [{ label: 'Messages', data: <?php echo json_encode($dailyCounts); ?>, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', fill: true, tension: 0.3 }] },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }
        
        if (document.getElementById('userRegChart')) {
            new Chart(document.getElementById('userRegChart'), {
                type: 'bar', data: { labels: <?php echo json_encode($regDates); ?>, datasets: [{ label: 'New Users', data: <?php echo json_encode($regCounts); ?>, backgroundColor: '#8b5cf6', borderRadius: 6 }] },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }
        
        if (document.getElementById('topicsChart')) {
            new Chart(document.getElementById('topicsChart'), {
                type: 'pie', data: { labels: <?php echo json_encode(array_keys($topics)); ?>, datasets: [{ data: <?php echo json_encode(array_values($topics)); ?>, backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e', '#14b8a6'] }] },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'right', labels: { font: { size: 10 } } } } }
            });
        }
        
        if (document.getElementById('activeUsersChart')) {
            new Chart(document.getElementById('activeUsersChart'), {
                type: 'bar', data: { labels: <?php echo json_encode($activeUserNames); ?>, datasets: [{ label: 'Messages', data: <?php echo json_encode($activeUserCounts); ?>, backgroundColor: '#22c55e', borderRadius: 6 }] },
                options: { responsive: true, maintainAspectRatio: true, indexAxis: 'y', plugins: { legend: { position: 'bottom' } } }
            });
        }
        
        if (document.getElementById('hourlyChart')) {
            new Chart(document.getElementById('hourlyChart'), {
                type: 'line', data: { labels: ['12a','1a','2a','3a','4a','5a','6a','7a','8a','9a','10a','11a','12p','1p','2p','3p','4p','5p','6p','7p','8p','9p','10p','11p'], datasets: [{ label: 'Messages', data: <?php echo json_encode($hourlyCounts); ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', fill: true }] },
                options: { responsive: true, maintainAspectRatio: true }
            });
        }
        
        function showSection(section) {
            var sections = ['dashboardSection', 'analyticsSection', 'usersSection', 'messagesSection', 'symptomsSection'];
            for (var i = 0; i < sections.length; i++) { var el = document.getElementById(sections[i]); if (el) el.style.display = 'none'; }
            var targetSection = document.getElementById(section + 'Section'); if (targetSection) targetSection.style.display = 'block';
            var titles = { 'dashboard': 'Dashboard', 'analytics': 'Analytics', 'users': 'User Management', 'messages': 'Messages', 'symptoms': 'Symptom Checks' };
            document.getElementById('pageTitle').innerText = titles[section] || 'Dashboard';
            var navItems = document.querySelectorAll('.nav-item');
            for (var i = 0; i < navItems.length; i++) navItems[i].classList.remove('active');
            if (event && event.currentTarget) event.currentTarget.classList.add('active');
            setTimeout(function() { window.dispatchEvent(new Event('resize')); }, 100);
        }
        
        function exportToCSV() {
            var csv = [], rows = document.querySelectorAll('#messagesTable tr');
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll('td, th');
                for (var j = 0; j < cols.length - 1; j++) { 
                    var text = cols[j].innerText; 
                    text = text.replace(/"/g, '""'); 
                    row.push('"' + text + '"'); 
                }
                csv.push(row.join(','));
            }
            var blob = new Blob([csv.join('\n')], { type: 'text/csv' }), url = URL.createObjectURL(blob), a = document.createElement('a');
            a.href = url; a.download = 'chat_messages_export.csv'; a.click(); URL.revokeObjectURL(url);
        }
        
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('adminTheme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            var btn = document.querySelector('.theme-btn');
            if (document.body.classList.contains('dark-mode')) btn.innerHTML = '<i class="fas fa-sun"></i> Light';
            else btn.innerHTML = '<i class="fas fa-moon"></i> Dark';
        }
        
        if (localStorage.getItem('adminTheme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-btn').innerHTML = '<i class="fas fa-sun"></i> Light';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                closeSidebar();
            }
        });
    </script>
</body>
</html>