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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical.AI | Compact Assistant</title>
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
            align-items: center; /* Vertical Center */
            overflow-x: hidden;
        }

        /* Compact Background Orbs */
        .bg-wrapper {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 50% 50%, #0f172a 0%, #030712 100%);
        }
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.2; animation: move 15s infinite alternate; }
        .orb-1 { width: 300px; height: 300px; background: var(--primary); top: -5%; left: -5%; }
        .orb-2 { width: 250px; height: 250px; background: var(--accent); bottom: -5%; right: -5%; }
        
        @keyframes move { from { transform: translate(0, 0); } to { transform: translate(50px, 50px); } }

        .container { max-width: 1100px; margin: 0 auto; padding: 20px; width: 100%; }

        /* Modern Small Nav */
        nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .logo { font-size: 20px; font-weight: 800; cursor: pointer; color: #fff; text-decoration: none; }
        .logo span { color: var(--accent); }

        /* Sleek Hero Grid */
        .hero { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 40px; align-items: center; }

        .hero-text h1 { font-size: 44px; font-weight: 800; line-height: 1.15; margin-bottom: 15px; letter-spacing: -1.5px; }
        .hero-text h1 span { color: var(--accent); }
        .tagline { font-size: 16px; color: #9ca3af; margin-bottom: 30px; line-height: 1.5; }

        /* Compact Cards */
        .features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .f-card {
            background: var(--glass); border: 1px solid var(--border); padding: 18px;
            border-radius: 18px; backdrop-filter: blur(10px); cursor: pointer; transition: 0.3s;
        }
        .f-card:hover { transform: translateY(-5px); border-color: var(--accent); }
        .f-icon { font-size: 24px; margin-bottom: 8px; display: block; }
        .f-card h3 { font-size: 15px; margin-bottom: 4px; }
        .f-card p { font-size: 12px; color: #6b7280; }

        /* Compact Panel */
        .login-panel {
            background: var(--glass); border: 1px solid var(--border); 
            padding: 35px; border-radius: 30px; backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3); text-align: center;
        }
        .login-panel h2 { font-size: 24px; margin-bottom: 10px; }
        .login-panel p { font-size: 13px; color: #9ca3af; margin-bottom: 25px; }
        
        .btn-group { display: flex; flex-direction: column; gap: 12px; }
        .btn { padding: 14px; border-radius: 14px; font-weight: 700; font-size: 14px; text-decoration: none; transition: 0.3s; }
        .btn-main { background: var(--primary); color: white; }
        .btn-main:hover { background: #4f46e5; transform: scale(1.02); }
        .btn-alt { border: 1px solid var(--border); color: #fff; }
        .btn-alt:hover { background: rgba(255,255,255,0.05); }

        .disclaimer { margin-top: 20px; font-size: 11px; color: #f87171; background: rgba(239, 68, 68, 0.05); padding: 12px; border-radius: 12px; }

        /* Tight Stats */
        .stats { display: flex; gap: 30px; margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px; }
        .stat-box h4 { font-size: 22px; color: var(--accent); }
        .stat-box p { font-size: 10px; color: #6b7280; text-transform: uppercase; }

        @media (max-width: 900px) {
            body { align-items: flex-start; overflow-y: auto; }
            .hero { grid-template-columns: 1fr; text-align: center; }
            .features { justify-content: center; }
            .stats { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="bg-wrapper">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="container">
        <nav>
            <div class="logo" onclick="redirectToLogin()">🧬 Medical<span>.AI</span></div>
            <div style="font-size: 10px; border: 1px solid var(--accent); padding: 2px 8px; border-radius: 6px; color: var(--accent);">V2.4 ONLINE</div>
        </nav>

        <div class="hero">
            <div class="hero-text">
                <div style="background: rgba(34, 211, 238, 0.1); color: var(--accent); padding: 4px 12px; border-radius: 100px; display: inline-block; font-size: 11px; font-weight: 700; margin-bottom: 15px;">
                    ✦ AI DIAGNOSTICS
                </div>
                <h1>Smart Care for <span>Your Health</span></h1>
                <p class="tagline">The ultimate clinical dashboard for symptom checking and health profile management.</p>
                
                <div class="features">
                    <div class="f-card" onclick="redirectToLogin()">
                        <span class="f-icon">🩺</span>
                        <h3>Symptom AI</h3>
                        <p>Analyze with Gemini Pro.</p>
                    </div>
                    <div class="f-card" onclick="redirectToLogin()">
                        <span class="f-icon">📊</span>
                        <h3>Secure Logs</h3>
                        <p>End-to-end encryption.</p>
                    </div>
                </div>

                <div class="stats">
                    <div class="stat-box"><h4>99.4%</h4><p>Accuracy</p></div>
                    <div class="stat-box"><h4>&lt; 1s</h4><p>Speed</p></div>
                    <div class="stat-box"><h4>24/7</h4><p>Live</p></div>
                </div>
            </div>

            <div class="login-panel">
                <h2>Welcome Back</h2>
                <p>Access your private medical dashboard</p>
                
                <div class="btn-group">
                    <a href="login.php" class="btn btn-main">Sign In</a>
                    <a href="register.php" class="btn btn-alt">Create Account</a>
                </div>

                <div class="disclaimer">
                    <strong>Note:</strong> Informational only. Not a medical diagnosis.
                </div>
            </div>
        </div>
    </div>

    <script>
        function redirectToLogin() { window.location.href = 'login.php'; }
    </script>
</body>
</html>