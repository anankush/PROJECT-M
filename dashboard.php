<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Fetch Total Expenses
$stmt = $pdo->prepare("SELECT SUM(amount) as total_expenses FROM expenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$expense_data = $stmt->fetch();
$total_expenses = $expense_data['total_expenses'] ?? 0;

// Fetch Total Savings (Deposits - Withdrawals)
$stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) - 
        SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) as total_savings 
    FROM savings_transactions 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$savings_data = $stmt->fetch();
$total_savings = $savings_data['total_savings'] ?? 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-panel" style="margin-bottom: 40px; text-align: center;">
    <h2 style="color: var(--primary-color);">Welcome back!</h2>
    <p>This is your unified financial dashboard.</p>
</div>

<div class="grid grid-2">
    <!-- Expense Card -->
    <a href="PROJECT E/user/dashboard.php" style="text-decoration: none;">
        <div class="glass-panel stat-card" style="border-left: 4px solid var(--accent-color); cursor: pointer;">
            <h3>Expense Management</h3>
            <p>Manage categories, track daily expenses, and view detailed reports.</p>
            <div class="stat-value" style="background: linear-gradient(to right, var(--accent-color), #ff9a9e); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                ₹<?= number_format($total_expenses, 2) ?>
            </div>
            <p style="margin-top: 10px; font-weight: 600; color: var(--accent-color);">Go to PROJECT E &rarr;</p>
        </div>
    </a>

    <!-- Savings Card -->
    <a href="PROJECT S/user/dashboard.php" style="text-decoration: none;">
        <div class="glass-panel stat-card" style="border-left: 4px solid var(--success-color); cursor: pointer;">
            <h3>Savings Management</h3>
            <p>Set financial goals, track progress, and secure your future.</p>
            <div class="stat-value" style="background: linear-gradient(to right, var(--success-color), #55efc4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                ₹<?= number_format($total_savings, 2) ?>
            </div>
            <p style="margin-top: 10px; font-weight: 600; color: var(--success-color);">Go to PROJECT S &rarr;</p>
        </div>
    </a>
</div>

<!-- Global Chart -->
<div class="glass-panel" style="margin-top: 40px;">
    <h3>Overall Financial Statistics</h3>
    <div class="chart-container">
        <canvas id="financialChart"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financialChart').getContext('2d');
    
    // Gradient for Expenses
    let expenseGradient = ctx.createLinearGradient(0, 0, 0, 400);
    expenseGradient.addColorStop(0, 'rgba(255, 118, 117, 0.8)');
    expenseGradient.addColorStop(1, 'rgba(255, 118, 117, 0.2)');

    // Gradient for Savings
    let savingsGradient = ctx.createLinearGradient(0, 0, 0, 400);
    savingsGradient.addColorStop(0, 'rgba(0, 184, 148, 0.8)');
    savingsGradient.addColorStop(1, 'rgba(0, 184, 148, 0.2)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Expenses', 'Savings'],
            datasets: [{
                label: 'Amount (₹)',
                data: [<?= $total_expenses ?>, <?= $total_savings ?>],
                backgroundColor: [expenseGradient, savingsGradient],
                borderColor: ['var(--accent-color)', 'var(--success-color)'],
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#fff' }
                }
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
