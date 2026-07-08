<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/admin/index.php');
}

$settings = getSetting();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } elseif (loginUser($email, $password)) {
        redirect(SITE_URL . '/admin/index.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #981c4e;
            --primary-dark: #740633;
            --primary-light: #c22562;
            --accent: #fff8d1;
            --dark-blue: #003a6b;
            --body-text: #696969;
            --heading-color: #212121;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-premium: 0 20px 48px rgba(0, 0, 0, 0.05), 0 8px 24px rgba(152, 28, 78, 0.04), 0 0 0 1px rgba(152, 28, 78, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f4f7fa 0%, #fffef7 50%, #fef5f8 100%);
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient glowing circles */
        .ambient-glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(152, 28, 78, 0.05) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(0, 58, 107, 0.04) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* Subtle grid background */
        .background-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(152, 28, 78, 0.015) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(152, 28, 78, 0.015) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
            z-index: 0;
        }

        .login-card {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: var(--shadow-premium);
            width: 440px;
            max-width: 100%;
            padding: 48px 44px 40px;
            z-index: 1;
            transition: var(--transition);
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.06), 0 12px 32px rgba(152, 28, 78, 0.05), 0 0 0 1px rgba(152, 28, 78, 0.08);
        }

        .login-logo-header {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }

        .login-logo-header .logo {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .login-logo-header .logo-img {
            height: 50px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }

        .login-logo-header .logo-svg-fallback {
            width: 44px;
            height: 44px;
            color: var(--primary);
            flex-shrink: 0;
        }

        .login-logo-header .logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.1;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .login-logo-header .logo-text span:first-child {
            color: var(--primary);
        }

        .login-logo-header .logo-text span:last-child {
            color: var(--dark-blue);
        }

        .login-card-divider {
            height: 3px;
            width: 60px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 2px;
            margin: 0 auto 28px;
        }

        .login-error {
            background: #fff5f5;
            border: 1px solid #ffe3e3;
            border-left: 4px solid #e53e3e;
            color: #c53030;
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: shake 0.4s ease-in-out;
        }

        .login-error i {
            color: #e53e3e;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-5px); }
            40% { transform: translateX(5px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }

        .form-group {
            margin-bottom: 22px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: var(--transition);
            z-index: 2;
            line-height: 1;
        }

        .input-wrapper .toggle-pwd {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            cursor: pointer;
            background: none;
            border: none;
            padding: 10px 12px;
            z-index: 2;
            line-height: 1;
            transition: var(--transition);
        }

        .input-wrapper .toggle-pwd:hover {
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 14px 48px 14px 46px;
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #1e293b;
            background: #f8fafc;
            transition: var(--transition);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(152, 28, 78, 0.1);
        }

        .form-control:focus ~ i,
        .input-wrapper:focus-within i {
            color: var(--primary);
        }

        .form-control::placeholder {
            color: #abb5c2;
            font-weight: 400;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            font-family: inherit;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(152, 28, 78, 0.25);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(152, 28, 78, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(152, 28, 78, 0.2);
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .login-footer a {
            color: #64748b;
            font-size: 0.88rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .login-footer a:hover {
            color: var(--primary);
            transform: translateX(-2px);
        }

        .login-footer a i {
            font-size: 0.8rem;
            transition: var(--transition);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 40px 24px 32px;
            }

            .login-logo-header .logo-img {
                height: 44px;
            }

            .login-logo-header .logo-svg-fallback {
                width: 38px;
                height: 38px;
            }

            .login-logo-header .logo-text {
                font-size: 0.9rem;
            }

            .form-control {
                padding: 13px 14px 13px 40px;
                font-size: 0.9rem;
            }

            .btn-login {
                padding: 14px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background styling components -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>
    <div class="background-grid"></div>

    <div class="login-card">
        <div class="login-logo-header">
            <a href="<?= SITE_URL ?>" class="logo">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?= SITE_URL . '/' . sanitizeInput($settings['logo']) ?>" alt="<?= sanitizeInput($settings['website_name'] ?? 'Almas Hospital') ?>" class="logo-img">
                <?php else: ?>
                    <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="logo-svg-fallback">
                        <path d="M 45,85 C 20,85 10,65 10,45 C 10,25 25,10 45,10 C 65,10 75,30 75,45 C 75,65 60,80 45,85 Z" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M 75,85 C 100,85 110,65 110,45 C 110,25 95,10 75,10 C 55,10 45,30 45,45 C 45,65 60,80 75,85 Z" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                <?php endif; ?>
                <span class="logo-text"><span>ALMAS</span><span>HOSPITAL</span></span>
            </a>
        </div>
        <div class="login-card-divider"></div>

        <?php if ($error): ?>
        <div class="login-error">
            <i class="fas fa-circle-exclamation"></i>
            <span><?= sanitizeInput($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control" placeholder="admin@almashospital.com" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="login-password" class="form-control" placeholder="Enter password" required>
                    <button type="button" class="toggle-pwd" id="toggle-pwd-btn" tabindex="-1" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket"></i>
                Sign In
            </button>
        </form>

        <div class="login-footer">
            <a href="<?= SITE_URL ?>"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

    <script>
    document.getElementById('toggle-pwd-btn').addEventListener('click', function() {
        var pwd = document.getElementById('login-password');
        var icon = this.querySelector('i');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'fas fa-eye';
        }
    });
    </script>
</body>
</html>
