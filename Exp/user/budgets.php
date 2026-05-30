<!-- Exp/user/budgets.php -->
<div class="dashboard-header">
    <div class="header-left">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <h1>Manage Budgets</h1>
    </div>
</div>

<div class="table-container" style="display:block;">
    <p style="margin-bottom:1rem; color:var(--text-muted);">Set or update your monthly budgets for each section.</p>
    
    <table id="budgetsTable">
        <thead>
            <tr>
                <th>Section Name</th>
                <th>Current Budget</th>
                <th style="text-align:right;">Action</th>
            </tr>
        </thead>
        <tbody id="budgetsTableBody">
            <!-- Populated by JS -->
        </tbody>
    </table>
</div>

<script>
    function renderBudgetsTable() {
        const tbody = document.getElementById('budgetsTableBody');
        tbody.innerHTML = '';
        if (categories.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No sections found. Create one first.</td></tr>';
            return;
        }

        categories.forEach(cat => {
            const tr = document.createElement('tr');
            const budgetVal = parseFloat(cat.budget) || 0;
            tr.innerHTML = `
                <td>${escapeHtml(cat.category_name)}</td>
                <td style="color:var(--aurora-2); font-weight:600;">${userCurrency}${budgetVal.toFixed(2)}</td>
                <td style="text-align:right;">
                    <button class="btn btn-ghost" onclick="triggerEditBudget(${cat.id}, '${escapeHtml(cat.category_name)}')">
                        <i class="fas fa-edit"></i> Edit Budget
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function triggerEditBudget(id, name) {
        currentCategoryId = id;
        currentCategoryName = name;
        editSectionBudget().then(() => {
            // After SweetAlert closes and fetchCategories completes, re-render
            setTimeout(renderBudgetsTable, 500);
        });
    }

    // Initialize the table
    renderBudgetsTable();
</script>
