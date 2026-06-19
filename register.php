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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Server-side validation (Security ke liye zaroori hai)
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!$uppercase || !$lowercase || !$number || !$specialChars || strlen($password) < 8) {
        $error = 'Password does not meet requirements';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute(array($username, $email));
        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute(array($username, $email, $password))) {
                $success = 'Registration successful! <a href="login.php" style="color:var(--accent)">Login here</a>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Medical.AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1; --accent: #22d3ee; --dark-bg: #030712;
            --glass: rgba(17, 24, 39, 0.7); --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background-color: var(--dark-bg); color: #fff; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; overflow-x: hidden;
        }

        .bg-wrapper { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background: radial-gradient(circle at 50% 50%, #0f172a 0%, #030712 100%); }
        .orb { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.2; }
        .orb-1 { width: 300px; height: 300px; background: var(--primary); top: -5%; left: -5%; }
        .orb-2 { width: 250px; height: 250px; background: var(--accent); bottom: -5%; right: -5%; }

        .register-card { width: 100%; max-width: 400px; background: var(--glass); border: 1px solid var(--border); padding: 30px; border-radius: 24px; backdrop-filter: blur(20px); z-index: 10; }

        .logo { text-align: center; font-size: 22px; font-weight: 800; margin-bottom: 20px; background: linear-gradient(to right, #fff, var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; cursor: pointer; }

        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 11px; color: #9ca3af; margin-bottom: 5px; text-transform: uppercase; }
        
        input { width: 100%; padding: 10px 15px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border); border-radius: 12px; color: #fff; font-size: 13px; transition: 0.3s; }
        input:focus { outline: none; border-color: var(--accent); }

        /* Real-time Validation Styles */
        .requirements { margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px; }
        .requirement { font-size: 10px; color: #f87171; display: flex; align-items: center; gap: 5px; transition: 0.3s; }
        .requirement.valid { color: #4ade80; }
        .requirement i { font-style: normal; }

        .btn-reg { width: 100%; padding: 12px; background: var(--primary); color: #fff; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 15px; }
        .btn-reg:hover { background: #4f46e5; transform: translateY(-2px); }

        .alert { padding: 10px; border-radius: 10px; font-size: 11px; text-align: center; margin-bottom: 15px; border: 1px solid; }
        .error { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2); }
        .success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border-color: rgba(34, 197, 94, 0.2); }
    </style>
</head>
<body>
    <div class="bg-wrapper"><div class="orb orb-1"></div><div class="orb orb-2"></div></div>

    <div class="register-card">
        <div class="logo" onclick="window.location.href='index.php'">🧬 Medical.AI</div>
        
        <?php if ($error): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Choose username" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="email@example.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="psw" placeholder="Enter strong password" required>
                
                <div class="requirements">
                    <div id="lower" class="requirement"><i>●</i> Small Letter</div>
                    <div id="upper" class="requirement"><i>●</i> Capital Letter</div>
                    <div id="number" class="requirement"><i>●</i> Number</div>
                    <div id="special" class="requirement"><i>●</i> Special Char</div>
                    <div id="length" class="requirement"><i>●</i> Min 8 Chars</div>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required>
            </div>

            <button type="submit" class="btn-reg">Create Account</button>
        </form>

        <div style="text-align:center; font-size:12px; margin-top:15px;">
            Already a member? <a href="login.php" style="color:var(--accent); text-decoration:none;">Sign In</a>
        </div>
    </div>

    <script>
        const pswInput = document.getElementById("psw");
        const lower = document.getElementById("lower");
        const upper = document.getElementById("upper");
        const number = document.getElementById("number");
        const special = document.getElementById("special");
        const length = document.getElementById("length");

        pswInput.onkeyup = function() {
            // Check lowercase
            if(pswInput.value.match(/[a-z]/g)) { lower.classList.add("valid"); } 
            else { lower.classList.remove("valid"); }

            // Check uppercase
            if(pswInput.value.match(/[A-Z]/g)) { upper.classList.add("valid"); } 
            else { upper.classList.remove("valid"); }

            // Check numbers
            if(pswInput.value.match(/[0-9]/g)) { number.classList.add("valid"); } 
            else { number.classList.remove("valid"); }

            // Check special characters
            if(pswInput.value.match(/[^\w]/g)) { special.classList.add("valid"); } 
            else { special.classList.remove("valid"); }

            // Check length
            if(pswInput.value.length >= 8) { length.classList.add("valid"); } 
            else { length.classList.remove("valid"); }
        }
    </script>
</body>
</html>