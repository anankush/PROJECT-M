<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO {$table} (email, password) VALUES (?, ?)");
                $stmt->execute([$email, $hashedPassword]);
                $success = 'Registration successful! You can now login.';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { 
                    $error = 'Email is already registered.';
                } else {
                    $error = 'An error occurred during registration.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
$token = generate_csrf_token();
?>

<div class="glass-panel auth-container">
    <div class="auth-header">
        <h2>Create Account</h2>
        <p>Join PROJECT M and manage your finances</p>
    </div>
    
    <form method="POST" action="" id="registerForm">
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
        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
    </form>
    
    <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<script>
<?php if ($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Registration Failed',
        text: '<?= addslashes($error) ?>',
        background: 'rgba(255, 255, 255, 0.95)',
        color: '#2b2d42',
        confirmButtonColor: '#ef233c'
    });
<?php endif; ?>

<?php if ($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?= addslashes($success) ?>',
        background: 'rgba(255, 255, 255, 0.95)',
        color: '#2b2d42',
        confirmButtonColor: '#2a9d8f'
    }).then(() => {
        window.location.href = 'login.php';
    });
<?php endif; ?>

document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    
    if(password.length < 6) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Weak Password',
            text: 'Password must be at least 6 characters long.',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#2b2d42'
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
