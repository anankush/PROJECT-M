<?php
function handle_get_records($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    if ($is_admin && isset($_GET['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($_GET['target_user_id']), 'get_records');
    } elseif ($is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Target user ID is required for admin']);
        return;
    } elseif (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($_GET['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'get_records');
    if (empty($category_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
        return;
    }

    try {
        $month = sanitize_input($_GET['month'] ?? '');
        if (!empty($month)) {
            $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? AND category_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ? ORDER BY id DESC");
            $stmt->execute([$uid, $category_id, $month]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? AND category_id = ? ORDER BY id DESC");
            $stmt->execute([$uid, $category_id]);
        }
        $records = $stmt->fetchAll();

        foreach ($records as &$rec) {
            $rec['custom_data'] = $rec['custom_data'] ? json_decode($rec['custom_data'], true) : [];
        }

        $stmt_schema = $pdo->prepare("SELECT custom_data FROM expenses WHERE user_id = ? AND category_id = ? AND custom_data IS NOT NULL ORDER BY id DESC LIMIT 10");
        $stmt_schema->execute([$uid, $category_id]);
        $schema_records = $stmt_schema->fetchAll();

        $schema = [];
        foreach ($schema_records as $sr) {
            if ($sr['custom_data']) {
                $cd = json_decode($sr['custom_data'], true);
                if (is_array($cd)) {
                    foreach ($cd as $k => $v) {
                        if (is_array($v) && isset($v['type'])) {
                            $schema[$k] = $v['type'];
                        } else {
                            if (!isset($schema[$k])) $schema[$k] = 'text';
                        }
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'data' => $records, 'schema' => empty($schema) ? new \stdClass() : $schema]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch records.']);
    }
}

function handle_add_record($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'add_record');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'add_record');
    $entry_date = sanitize_input($input['entry_date'] ?? '');
    $entry_time = sanitize_input($input['entry_time'] ?? '');
    $amount = floatval($input['amount'] ?? 0);
    $description = sanitize_input($input['description'] ?? '');
    if (strlen($description) > 255) $description = substr($description, 0, 255);
    $custom_data = null;
    if (isset($input['custom_data']) && is_array($input['custom_data'])) {
        array_walk_recursive($input['custom_data'], function(&$val) {
            if (is_string($val)) $val = sanitize_input($val);
        });
        $custom_data = json_encode($input['custom_data']);
    }

    if (empty($category_id) || empty($entry_date) || empty($entry_time) || $amount === '') {
        echo json_encode(['status' => 'error', 'message' => 'Date and Time are required']);
        return;
    }

    try {
        $recordModel = new Model($pdo, 'expenses', $uid);
        $recordModel->insert([
            'category_id' => $category_id,
            'entry_date' => $entry_date,
            'entry_time' => $entry_time,
            'amount' => $amount,
            'description' => $description,
            'custom_data' => $custom_data
        ]);

        // Trigger push notification budget check
        try {
            require_once __DIR__ . '/../../../includes/push_sender.php';
            checkAndTriggerBudgetAlert($pdo, $uid, intval($category_id), floatval($amount), $entry_date);
        } catch (Throwable $e) {}

        echo json_encode(['status' => 'success', 'message' => 'Record added successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add record.']);
    }
}

function handle_update_record($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'update_record');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $id = sanitize_input($input['id'] ?? '');
    verify_ownership($pdo, 'expenses', $id, $uid, 'update_record');
    $entry_date = sanitize_input($input['entry_date'] ?? '');
    $entry_time = sanitize_input($input['entry_time'] ?? '');
    $amount = floatval($input['amount'] ?? 0);
    $description = sanitize_input($input['description'] ?? '');
    if (strlen($description) > 255) $description = substr($description, 0, 255);
    $custom_data = null;
    if (isset($input['custom_data']) && is_array($input['custom_data'])) {
        array_walk_recursive($input['custom_data'], function(&$val) {
            if (is_string($val)) $val = sanitize_input($val);
        });
        $custom_data = json_encode($input['custom_data']);
    }

    if (empty($id) || empty($entry_date) || empty($entry_time) || $amount === '') {
        echo json_encode(['status' => 'error', 'message' => 'ID, Date and Time are required']);
        return;
    }

    try {
        $recordModel = new Model($pdo, 'expenses', $uid);
        $recordModel->update($id, [
            'entry_date' => $entry_date,
            'entry_time' => $entry_time,
            'amount' => $amount,
            'description' => $description,
            'custom_data' => $custom_data
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Record updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update record.']);
    }
}

function handle_delete_record($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'delete_record');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $id = sanitize_input($input['id'] ?? '');
    verify_ownership($pdo, 'expenses', $id, $uid, 'delete_record');
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID is required']);
        return;
    }

    try {
        $recordModel = new Model($pdo, 'expenses', $uid);
        $recordModel->delete($id);
        echo json_encode(['status' => 'success', 'message' => 'Record deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete record.']);
    }
}

function handle_import_data($pdo) {
    if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Only regular users are permitted to import data.']);
        return;
    }
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 4 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Import file is too large. Maximum allowed size is 4MB.']);
        return;
    }

    $input = json_decode($raw, true);
    if (!is_array($input) || (empty($input['categories']) && empty($input['savings_goals']) && empty($input['monthly_overall_budgets']))) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid import data format.']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $mode = sanitize_input($input['mode'] ?? $_GET['mode'] ?? 'replace');
        $skipped_duplicates = 0;
        $pdo->beginTransaction();

        if ($mode === 'replace') {
            try { $pdo->prepare("DELETE FROM user_notes WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM category_monthly_budgets WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM user_categories WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM savings_transactions WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM savings_goals WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
            try { $pdo->prepare("DELETE FROM monthly_overall_budgets WHERE user_id = ?")->execute([$uid]); } catch (PDOException $e) {}
        }
        if (!empty($input['categories']) && is_array($input['categories'])) {
            foreach ($input['categories'] as $cat) {
                if (empty($cat['category_name'])) continue;

                $cat_name = sanitize_input($cat['category_name']);
                $new_cat_id = null;

                if ($mode === 'merge') {
                    $stmt_check = $pdo->prepare("SELECT id FROM user_categories WHERE user_id = ? AND category_name = ?");
                    $stmt_check->execute([$uid, $cat_name]);
                    $existing = $stmt_check->fetch();
                    if ($existing) {
                        $new_cat_id = $existing['id'];
                        if (isset($cat['budget'])) {
                            $pdo->prepare("UPDATE user_categories SET budget = ? WHERE id = ?")->execute([$cat['budget'], $new_cat_id]);
                        }
                    }
                }

                if (!$new_cat_id) {
                    $stmt = $pdo->prepare("INSERT INTO user_categories (user_id, category_name, budget) VALUES (?, ?, ?)");
                    $stmt->execute([$uid, $cat_name, $cat['budget'] ?? null]);
                    $new_cat_id = $pdo->lastInsertId();
                }

                if (!empty($cat['note'])) {
                    try {
                        $stmt_note = $pdo->prepare("INSERT INTO user_notes (user_id, category_id, note_content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE note_content = VALUES(note_content)");
                        $stmt_note->execute([$uid, $new_cat_id, sanitize_input($cat['note'])]);
                    } catch (PDOException $e) {}
                }

                if (!empty($cat['records']) && is_array($cat['records'])) {
                    $stmt_rec = $pdo->prepare("INSERT INTO expenses (user_id, category_id, entry_date, entry_time, amount, description, custom_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt_check_dup = null;
                    if ($mode === 'merge') {
                        $stmt_check_dup = $pdo->prepare("SELECT id FROM expenses WHERE user_id = ? AND category_id = ? AND entry_date = ? AND entry_time = ? AND amount = ? AND description = ? LIMIT 1");
                    }

                    foreach ($cat['records'] as $rec) {
                        if (!isset($rec['amount']) || $rec['amount'] === '') continue;

                        $e_date = sanitize_input($rec['entry_date'] ?? date('Y-m-d'));
                        $e_time = sanitize_input($rec['entry_time'] ?? date('H:i:s'));
                        $amt = floatval($rec['amount']);
                        $desc = sanitize_input($rec['description'] ?? '');

                        if ($mode === 'merge' && $stmt_check_dup) {
                            $stmt_check_dup->execute([$uid, $new_cat_id, $e_date, $e_time, $amt, $desc]);
                            if ($stmt_check_dup->fetch()) {
                                $skipped_duplicates++;
                                continue;
                            }
                        }

                        $custom_data = null;
                        if (!empty($rec['custom_data'])) {
                            if (is_array($rec['custom_data'])) {
                                array_walk_recursive($rec['custom_data'], function(&$val) {
                                    if (is_string($val)) $val = sanitize_input($val);
                                });
                                $custom_data = json_encode($rec['custom_data']);
                            } else {
                                $custom_data = $rec['custom_data'];
                            }
                        }
                        
                        $stmt_rec->execute([$uid, $new_cat_id, $e_date, $e_time, $amt, $desc, $custom_data]);
                    }
                }

                if (!empty($cat['monthly_budgets']) && is_array($cat['monthly_budgets'])) {
                    $stmt_bud = $pdo->prepare("INSERT INTO category_monthly_budgets (user_id, category_id, budget_month, budget) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE budget = VALUES(budget)");
                    foreach ($cat['monthly_budgets'] as $bud) {
                        if (!empty($bud['budget_month']) && isset($bud['budget'])) {
                            $stmt_bud->execute([$uid, $new_cat_id, sanitize_input($bud['budget_month']), floatval($bud['budget'])]);
                        }
                    }
                }
            }
        }
        if (!empty($input['savings_goals']) && is_array($input['savings_goals'])) {
            foreach ($input['savings_goals'] as $goal) {
                if (empty($goal['goal_name'])) continue;

                $goal_name = sanitize_input($goal['goal_name']);
                $new_goal_id = null;

                if ($mode === 'merge') {
                    $stmt_check = $pdo->prepare("SELECT id FROM savings_goals WHERE user_id = ? AND goal_name = ?");
                    $stmt_check->execute([$uid, $goal_name]);
                    $existing = $stmt_check->fetch();
                    if ($existing) {
                        $new_goal_id = $existing['id'];
                        $pdo->prepare("UPDATE savings_goals SET target_amount = ?, deadline = ?, category = ?, theme_color = ?, priority = ? WHERE id = ? AND user_id = ?")
                            ->execute([
                                floatval($goal['target_amount'] ?? 0),
                                !empty($goal['deadline']) ? sanitize_input($goal['deadline']) : null,
                                sanitize_input($goal['category'] ?? 'others'),
                                sanitize_input($goal['theme_color'] ?? 'purple'),
                                sanitize_input($goal['priority'] ?? 'medium'),
                                $new_goal_id,
                                $uid
                            ]);
                    }
                }

                if (!$new_goal_id) {
                    $stmt = $pdo->prepare("INSERT INTO savings_goals (user_id, goal_name, target_amount, deadline, category, theme_color, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $uid,
                        $goal_name,
                        floatval($goal['target_amount'] ?? 0),
                        !empty($goal['deadline']) ? sanitize_input($goal['deadline']) : null,
                        sanitize_input($goal['category'] ?? 'others'),
                        sanitize_input($goal['theme_color'] ?? 'purple'),
                        sanitize_input($goal['priority'] ?? 'medium')
                    ]);
                    $new_goal_id = $pdo->lastInsertId();
                }

                if (!empty($goal['transactions']) && is_array($goal['transactions'])) {
                    $stmt_tx = $pdo->prepare("INSERT INTO savings_transactions (goal_id, user_id, amount, type, transaction_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    $stmt_check_tx = null;
                    if ($mode === 'merge') {
                        $stmt_check_tx = $pdo->prepare("
                            SELECT id FROM savings_transactions 
                            WHERE goal_id = ? AND user_id = ? AND amount = ? AND type = ? AND transaction_date = ? 
                              AND (notes = ? OR (notes IS NULL AND ? IS NULL)) 
                            LIMIT 1
                        ");
                    }

                    foreach ($goal['transactions'] as $tx) {
                        if (!isset($tx['amount']) || $tx['amount'] === '') continue;

                        $amt = floatval($tx['amount']);
                        $type = sanitize_input($tx['type'] ?? 'deposit');
                        $tx_date = sanitize_input($tx['transaction_date'] ?? date('Y-m-d'));
                        $notes = !empty($tx['notes']) ? sanitize_input($tx['notes']) : null;

                        if ($mode === 'merge' && $stmt_check_tx) {
                            $stmt_check_tx->execute([$new_goal_id, $uid, $amt, $type, $tx_date, $notes, $notes]);
                            if ($stmt_check_tx->fetch()) {
                                $skipped_duplicates++;
                                continue;
                            }
                        }

                        $stmt_tx->execute([$new_goal_id, $uid, $amt, $type, $tx_date, $notes]);
                    }
                }
            }
        }
        // Import Monthly Overall Budgets
        if (!empty($input['monthly_overall_budgets']) && is_array($input['monthly_overall_budgets'])) {
            $stmt_ob = $pdo->prepare("INSERT INTO monthly_overall_budgets (user_id, budget_month, budget) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE budget = VALUES(budget)");
            foreach ($input['monthly_overall_budgets'] as $ob) {
                if (!empty($ob['budget_month']) && isset($ob['budget'])) {
                    $stmt_ob->execute([$uid, sanitize_input($ob['budget_month']), floatval($ob['budget'])]);
                }
            }
        }

        // Import Push Preferences
        if (!empty($input['push_preferences']) && is_array($input['push_preferences'])) {
            $pp = $input['push_preferences'];
            $pdo->prepare("
                INSERT INTO push_preferences (user_id, budget_alert, budget_exceeded, savings_goal, monthly_summary, login_alert)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    budget_alert = VALUES(budget_alert),
                    budget_exceeded = VALUES(budget_exceeded),
                    savings_goal = VALUES(savings_goal),
                    monthly_summary = VALUES(monthly_summary),
                    login_alert = VALUES(login_alert)
            ")->execute([
                $uid,
                isset($pp['budget_alert']) ? (int)$pp['budget_alert'] : 1,
                isset($pp['budget_exceeded']) ? (int)$pp['budget_exceeded'] : 1,
                isset($pp['savings_goal']) ? (int)$pp['savings_goal'] : 1,
                isset($pp['monthly_summary']) ? (int)$pp['monthly_summary'] : 1,
                isset($pp['login_alert']) ? (int)$pp['login_alert'] : 1
            ]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data imported successfully', 'skipped_duplicates' => $skipped_duplicates]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Import failed: ' . $e->getMessage()]);
    }
}

function handle_export_data($pdo) {
    if (!isset($_SESSION['user_id']) || (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Only regular users are permitted to backup data.']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, category_name, budget FROM user_categories WHERE user_id = ?");
        $stmt->execute([$uid]);
        $categories = $stmt->fetchAll();

        $categories_export = [];
        foreach ($categories as $cat) {
            $cat_data = [
                'category_name' => $cat['category_name'],
                'budget' => $cat['budget'],
                'monthly_budgets' => [],
                'records' => [],
                'note' => ''
            ];

            try {
                $stmt_note = $pdo->prepare("SELECT note_content FROM user_notes WHERE user_id = ? AND category_id = ?");
                $stmt_note->execute([$uid, $cat['id']]);
                $note_row = $stmt_note->fetch();
                if ($note_row) {
                    $cat_data['note'] = $note_row['note_content'];
                }
            } catch (PDOException $e) {}

            try {
                $stmt_budgets = $pdo->prepare("SELECT budget_month, budget FROM category_monthly_budgets WHERE user_id = ? AND category_id = ?");
                $stmt_budgets->execute([$uid, $cat['id']]);
                $cat_data['monthly_budgets'] = $stmt_budgets->fetchAll();
            } catch (PDOException $e) {}

            try {
                $stmt_rec = $pdo->prepare("SELECT entry_date, entry_time, amount, description, custom_data FROM expenses WHERE user_id = ? AND category_id = ?");
                $stmt_rec->execute([$uid, $cat['id']]);
                $records = $stmt_rec->fetchAll();

                foreach ($records as $rec) {
                    $rec['custom_data'] = $rec['custom_data'] ? json_decode($rec['custom_data'], true) : [];
                    $cat_data['records'][] = $rec;
                }
            } catch (PDOException $e) {}
            
            $categories_export[] = $cat_data;
        }
        $savings_export = [];
        try {
            $stmt_goals = $pdo->prepare("SELECT id, goal_name, target_amount, deadline, category, theme_color, priority FROM savings_goals WHERE user_id = ?");
            $stmt_goals->execute([$uid]);
            $goals = $stmt_goals->fetchAll();

            foreach ($goals as $goal) {
                $goal_data = [
                    'goal_name' => $goal['goal_name'],
                    'target_amount' => $goal['target_amount'],
                    'deadline' => $goal['deadline'],
                    'category' => $goal['category'],
                    'theme_color' => $goal['theme_color'],
                    'priority' => $goal['priority'],
                    'transactions' => []
                ];

                $stmt_tx = $pdo->prepare("SELECT amount, type, transaction_date, notes FROM savings_transactions WHERE goal_id = ? AND user_id = ?");
                $stmt_tx->execute([$goal['id'], $uid]);
                $transactions = $stmt_tx->fetchAll();

                foreach ($transactions as $tx) {
                    $goal_data['transactions'][] = [
                        'amount' => $tx['amount'],
                        'type' => $tx['type'],
                        'transaction_date' => $tx['transaction_date'],
                        'notes' => $tx['notes']
                    ];
                }
                $savings_export[] = $goal_data;
            }
        } catch (PDOException $e) {}
        
        $overall_export = [];
        try {
            $stmt_overall = $pdo->prepare("SELECT budget_month, budget FROM monthly_overall_budgets WHERE user_id = ?");
            $stmt_overall->execute([$uid]);
            $overall_export = $stmt_overall->fetchAll();
        } catch (PDOException $e) {}

        $push_prefs_export = null;
        try {
            $stmt_push = $pdo->prepare("SELECT budget_alert, budget_exceeded, savings_goal, monthly_summary, login_alert FROM push_preferences WHERE user_id = ?");
            $stmt_push->execute([$uid]);
            $push_prefs_export = $stmt_push->fetch() ?: null;
        } catch (PDOException $e) {}

        $export_data = [
            'version' => '1.2',
            'exported_at' => date('Y-m-d H:i:s'),
            'categories' => $categories_export,
            'savings_goals' => $savings_export,
            'monthly_overall_budgets' => $overall_export,
            'push_preferences' => $push_prefs_export
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="money_management_backup_' . date('Ymd_His') . '.json"');
        echo json_encode($export_data, JSON_PRETTY_PRINT);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Export failed.']);
    }
}

function handle_get_total_expenditure($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $uid = $_SESSION['user_id'];
    $month = sanitize_input($_GET['month'] ?? '');

    try {
        if ($month) {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(entry_date, '%Y-%m') = ?");
            $stmt->execute([$uid, $month]);
        } else {
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ?");
            $stmt->execute([$uid]);
        }
        $result = $stmt->fetch();
        echo json_encode(['status' => 'success', 'total' => $result['total'] ?? 0]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch total.']);
    }
}

function handle_get_cumulative_stats($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $uid = $_SESSION['user_id'];
    $category_id = sanitize_input($_GET['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'get_cumulative_stats');
    $month = sanitize_input($_GET['month'] ?? '');

    if (empty($category_id) || empty($month)) {
        echo json_encode(['status' => 'error', 'message' => 'Category ID and Month are required']);
        return;
    }

    try {
        $stmt_budget = $pdo->prepare("SELECT SUM(budget) as total_budget FROM category_monthly_budgets WHERE user_id = ? AND category_id = ?");
        $stmt_budget->execute([$uid, $category_id]);
        $cumulative_budget = $stmt_budget->fetchColumn();

        if ($cumulative_budget === null || $cumulative_budget === false) {
            $stmt_def = $pdo->prepare("SELECT budget FROM user_categories WHERE id = ? AND user_id = ?");
            $stmt_def->execute([$category_id, $uid]);
            $cumulative_budget = $stmt_def->fetchColumn() ?: 0;
        }

        $stmt_exp = $pdo->prepare("SELECT SUM(amount) as total_exp FROM expenses WHERE user_id = ? AND category_id = ?");
        $stmt_exp->execute([$uid, $category_id]);
        $cumulative_expenditure = $stmt_exp->fetchColumn() ?: 0;

        echo json_encode([
            'status' => 'success',
            'cumulative_budget' => $cumulative_budget,
            'cumulative_expenditure' => $cumulative_expenditure
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch stats.']);
    }
}
