<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: chat.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND password = ?");
        $stmt->execute(array($username, $username, $password));
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute(array($user['id']));
            
            if ($user['role'] == 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: chat.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Medical.AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #22d3ee;
            --dark-bg: #030712;
            --glass: rgba(17, 24, 39, 0.7);
            --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--dark-bg);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Compact Background Orbs */
        .bg-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 50% 50%, #0f172a 0%, #030712 100%);
        }
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.2; }
        .orb-1 { width: 250px; height: 250px; background: var(--primary); top: -5%; left: -5%; }
        .orb-2 { width: 200px; height: 200px; background: var(--accent); bottom: -5%; right: -5%; }

        .login-card {
            width: 100%;
            max-width: 380px; /* Width kam kar di */
            background: var(--glass);
            border: 1px solid var(--border);
            padding: 30px; /* Padding kam kar di */
            border-radius: 24px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            z-index: 10;
        }

        .logo {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(to right, #fff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            cursor: pointer;
        }

        h2 { font-size: 20px; text-align: center; margin-bottom: 5px; }
        .subtitle { color: #9ca3af; text-align: center; font-size: 12px; margin-bottom: 25px; }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px; }
        
        input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus { outline: none; border-color: var(--accent); background: rgba(255, 255, 255, 0.08); }

        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 10px;
            border-radius: 10px;
            font-size: 12px;
            text-align: center;
            margin-bottom: 15px;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 5px;
        }

        .btn-login:hover { background: #4f46e5; transform: translateY(-2px); }

        .footer-links { margin-top: 20px; text-align: center; font-size: 13px; color: #9ca3af; }
        .footer-links a { color: var(--accent); text-decoration: none; font-weight: 600; }

        .back-home { display: block; text-align: center; margin-top: 15px; font-size: 11px; color: #6b7280; text-decoration: none; }
        .back-home:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="bg-wrapper">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-card">
        <div class="logo" onclick="window.location.href='index.php'">🧬 Medical.AI</div>
        
        <h2>Welcome Back</h2>
        <p class="subtitle">Securely login to your dashboard</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="footer-links">
            New here? <a href="register.php">Create Account</a>
        </div>
        <a href="index.php" class="back-home">← Back to Home</a>
    </div>
</body>
</html>