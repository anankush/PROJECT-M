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
            $success = 'Registration successful! You can now <a href="login.php">login</a>.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate entry)
                $error = 'Email is already registered.';
            } else {
                $error = 'An error occurred during registration.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="max-width: 400px; margin: 40px auto;">
    <h2 style="text-align: center; color: var(--primary-color);">Register</h2>
    
    <?php if ($error): ?>
        <div style="background: rgba(214, 48, 49, 0.2); border: 1px solid var(--danger-color); padding: 10px; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="background: rgba(0, 184, 148, 0.2); border: 1px solid var(--success-color); padding: 10px; border-radius: 8px; margin-bottom: 20px;">
            <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Account Type</label>
            <select name="type" class="form-control">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
    </form>
    <p style="text-align: center; margin-top: 20px;">Already have an account? <a href="login.php" style="color: var(--secondary-color);">Login here</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
