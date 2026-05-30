<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verify_csrf_token($csrf_token)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $type = $_POST['type'] ?? 'user';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            $table = ($type === 'admin') ? 'admin_users' : 'users';
            $stmt = $pdo->prepare("SELECT id, password FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = $type;
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$token = generate_csrf_token();
?>

<div class="glass-panel auth-container">
    <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Login to your unified dashboard</p>
    </div>
    
    <form method="POST" action="" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Account Type</label>
            <select name="type" class="form-control">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Secure Login</button>
    </form>
    
    <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

<script>
<?php if ($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Authentication Failed',
        text: '<?= addslashes($error) ?>',
        background: 'rgba(20, 14, 50, 0.9)',
        color: '#fff',
        confirmButtonColor: '#d63031'
    });
<?php endif; ?>

// Basic JS validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if(!email || !password) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Missing Fields',
            text: 'Please fill in both email and password.',
            background: 'rgba(20, 14, 50, 0.9)',
            color: '#fff'
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
