<?php
require_once 'includes/db.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
set_security_headers();

if (isset($_SESSION['admin_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration | Money Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/glassmorphism.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo time(); ?>">
    <style>
        .portal-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }
        .portal-card {
            max-width: 480px;
            width: 100%;
            padding: 3.5rem 2.5rem;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(239, 68, 68, 0.15);
            background: rgba(10, 10, 25, 0.65);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            animation: scaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes scaleIn {
            0% { transform: scale(0.95); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .portal-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            margin: 0 auto 2rem;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.2);
            animation: pulseGlow 3s infinite alternate;
        }
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.25); }
            100% { box-shadow: 0 0 35px rgba(239, 68, 68, 0.35); border-color: rgba(239, 68, 68, 0.5); }
        }
        .portal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 0.8rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 40%, #fca5a5);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .portal-desc {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }
        .portal-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .btn-portal-primary {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            color: white !important;
            border: none;
            padding: 1rem 2rem;
            font-weight: 700;
            border-radius: 100px;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 4px 25px rgba(239, 68, 68, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-portal-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(239, 68, 68, 0.6);
            background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
        }
        .btn-portal-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text-secondary) !important;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 100px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-portal-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: white !important;
            transform: translateY(-1px);
        }
        .portal-warning {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        @media (max-width: 480px) {
            .portal-wrapper {
                padding: 1.5rem 1rem;
            }
            .portal-card {
                padding: 2.5rem 1.5rem;
                border-radius: 20px;
            }
            .portal-icon {
                width: 75px;
                height: 75px;
                font-size: 2.2rem;
                margin-bottom: 1.5rem;
            }
            .portal-title {
                font-size: 1.75rem;
            }
            .portal-desc {
                font-size: 0.88rem;
                margin-bottom: 2rem;
            }
            .btn-portal-primary, .btn-portal-secondary {
                padding: 0.85rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="color-combo-bg" style="background: linear-gradient(45deg, #0f0c29, #ef4444, #1e1b4b, #0f0c29); opacity: 0.12;"></div>
    <div class="aurora-bg">
        <div class="orb orb-1" style="background: radial-gradient(circle, rgba(239,68,68,0.2) 0%, rgba(0,0,0,0) 70%);"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="noise-overlay"></div>

    <div class="portal-wrapper">
        <div class="glass-card portal-card">
            <div class="portal-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="portal-title">System Administration</h1>
            <p class="portal-desc">
                Welcome to the Money Management administrative command gateway. Only authorized personnel are permitted to access the database monitor and security logs.
            </p>
            
            <div class="portal-actions">
                <a href="auth/admin_login.php" class="btn-portal-primary">
                    <i class="fas fa-lock-open"></i> Secure Access Login
                </a>
                <a href="index.php" class="btn-portal-secondary">
                    <i class="fas fa-arrow-left"></i> Return Home
                </a>
            </div>

            <div class="portal-warning">
                <i class="fas fa-exclamation-triangle" style="margin-right: 3px;"></i> Restricted Zone
            </div>
        </div>
    </div>
</body>
</html>
