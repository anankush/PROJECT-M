<?php
$pageTitle = 'Savings Management | PROJECT S';
require_once '../../includes/config.php';
require_once '../../includes/db.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Handle Goal Creation
if (isset($_POST['add_goal'])) {
    $goal_name = htmlspecialchars($_POST['goal_name']);
    $target_amount = floatval($_POST['target_amount']);
    $target_date = $_POST['target_date'];
    
    $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, target_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $goal_name, $target_amount, $target_date]);
    header('Location: dashboard.php');
    exit;
}

// Handle Transaction
if (isset($_POST['add_transaction'])) {
    $goal_id = $_POST['goal_id'] ?: null;
    $amount = floatval($_POST['amount']);
    $type = $_POST['transaction_type'];
    $date = $_POST['transaction_date'];
    $notes = htmlspecialchars($_POST['notes']);
    
    $stmt = $pdo->prepare("INSERT INTO savings_transactions (user_id, goal_id, amount, transaction_type, transaction_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $goal_id, $amount, $type, $date, $notes]);
    header('Location: dashboard.php');
    exit;
}

// Fetch Goals
$goals = $pdo->prepare("
    SELECT g.*, 
    (SELECT SUM(CASE WHEN transaction_type='deposit' THEN amount ELSE -amount END) FROM savings_transactions t WHERE t.goal_id = g.id) as current_amount
    FROM savings_goals g WHERE user_id = ?
");
$goals->execute([$user_id]);
$goalsList = $goals->fetchAll();

// Chart Data (Overall Savings by Month)
$chartStmt = $pdo->prepare("
    SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month, 
           SUM(CASE WHEN transaction_type='deposit' THEN amount ELSE -amount END) as net_saving
    FROM savings_transactions 
    WHERE user_id = ? 
    GROUP BY month 
    ORDER BY month ASC
");
$chartStmt->execute([$user_id]);
$chartData = $chartStmt->fetchAll();

$months = [];
$netSavings = [];
foreach ($chartData as $row) {
    $months[] = $row['month'];
    $netSavings[] = $row['net_saving'];
}

require_once '../../includes/header.php';
?>

<div class="glass-panel" style="margin-bottom: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <h2>Savings Dashboard</h2>
        <div>
            <button onclick="document.getElementById('goalModal').style.display='block'" class="btn btn-primary">+ Add Goal</button>
            <button onclick="document.getElementById('transModal').style.display='block'" class="btn btn-glass">+ Add Transaction</button>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Goals List -->
    <div class="glass-panel" style="max-height: 500px; overflow-y: auto;">
        <h3>Your Savings Goals</h3>
        <?php if (count($goalsList) == 0): ?>
            <p style="text-align: center; margin-top: 20px;">No goals yet. Start saving!</p>
        <?php else: ?>
            <?php foreach ($goalsList as $goal): 
                $current = $goal['current_amount'] ?? 0;
                $target = $goal['target_amount'];
                $percent = $target > 0 ? min(100, round(($current / $target) * 100)) : 0;
            ?>
                <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid var(--glass-border);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <strong><?= htmlspecialchars($goal['goal_name']) ?></strong>
                        <span>₹<?= number_format($current, 2) ?> / ₹<?= number_format($target, 2) ?></span>
                    </div>
                    <!-- Progress Bar -->
                    <div style="width: 100%; background: rgba(255,255,255,0.1); border-radius: 10px; height: 10px; overflow: hidden;">
                        <div style="width: <?= $percent ?>%; background: linear-gradient(90deg, var(--secondary-color), var(--success-color)); height: 100%; transition: width 0.5s;"></div>
                    </div>
                    <div style="text-align: right; font-size: 0.8rem; margin-top: 5px; color: var(--text-secondary);"><?= $percent ?>% Achieved</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Chart -->
    <div class="glass-panel">
        <h3>Savings Trend</h3>
        <div class="chart-container" style="height: 300px;">
            <canvas id="savingsChart"></canvas>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Goal Modal -->
<div id="goalModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width: 400px; max-width: 90%; margin: 100px auto; position: relative;">
        <span onclick="document.getElementById('goalModal').style.display='none'" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:1.5rem;">&times;</span>
        <h3>Create Savings Goal</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Goal Name</label>
                <input type="text" name="goal_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Target Amount (₹)</label>
                <input type="number" step="0.01" name="target_amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Target Date</label>
                <input type="date" name="target_date" class="form-control" required>
            </div>
            <button type="submit" name="add_goal" class="btn btn-primary" style="width: 100%;">Create Goal</button>
        </form>
    </div>
</div>

<!-- Transaction Modal -->
<div id="transModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div class="glass-panel" style="width: 400px; max-width: 90%; margin: 100px auto; position: relative;">
        <span onclick="document.getElementById('transModal').style.display='none'" style="position:absolute; right:20px; top:20px; cursor:pointer; font-size:1.5rem;">&times;</span>
        <h3>Add Transaction</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="transaction_type" class="form-control">
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Amount (₹)</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Date</label>
                <input type="date" name="transaction_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Assign to Goal (Optional)</label>
                <select name="goal_id" class="form-control">
                    <option value="">-- No Specific Goal --</option>
                    <?php foreach ($goalsList as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['goal_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control">
            </div>
            <button type="submit" name="add_transaction" class="btn btn-success" style="width: 100%; background: var(--success-color); color: #fff; border-radius: 12px; padding: 12px; border:none; font-weight: bold;">Save Transaction</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('savingsChart').getContext('2d');
    
    // Gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 206, 201, 0.5)');
    gradient.addColorStop(1, 'rgba(0, 206, 201, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Net Savings (₹)',
                data: <?= json_encode($netSavings) ?>,
                borderColor: 'var(--secondary-color)',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: 'var(--secondary-color)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#fff' } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.1)' },
                    ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255, 255, 255, 0.7)' }
                }
            }
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
