<?php
// Exp/includes/handlers/record_handlers.php
// Logic strictly mirrored from PROJECT E
function handle_get_records($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    if ($is_admin && isset($_GET['target_user_id'])) {
        $uid = decode_id(sanitize_input($_GET['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif ($is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Target user ID is required for admin']);
        return;
    } elseif (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($_GET['category_id'] ?? '');
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $id = sanitize_input($input['id'] ?? '');
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $id = sanitize_input($input['id'] ?? '');
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
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    // Security: limit import payload to 2MB to prevent server memory overload
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 2 * 1024 * 1024) {
        echo json_encode(['status' => 'error', 'message' => 'Import file is too large. Maximum allowed size is 2MB.']);
        return;
    }

    $input = json_decode($raw, true);
    if (empty($input['categories']) || !is_array($input['categories'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid import data format']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $mode = sanitize_input($input['mode'] ?? 'replace');
        $skipped_duplicates = 0;
        $pdo->beginTransaction();

        if ($mode === 'replace') {
            // Clear existing data to prevent duplication during import
            try {
                $pdo->prepare("DELETE FROM user_notes WHERE user_id = ?")->execute([$uid]);
            } catch (PDOException $e) {} // Ignore if table doesn't exist
            
            $pdo->prepare("DELETE FROM expenses WHERE user_id = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM category_monthly_budgets WHERE user_id = ?")->execute([$uid]);
            $pdo->prepare("DELETE FROM user_categories WHERE user_id = ?")->execute([$uid]);
        }

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
                    // Optionally update budget if needed
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
                } catch (PDOException $e) {
                    // Ignore error if table doesn't exist yet
                }
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
                    $e_time = sanitize_input($rec['entry_time'] ?? date('H:i'));
                    $amt = floatval($rec['amount']);
                    $desc = sanitize_input($rec['description'] ?? '');

                    if ($mode === 'merge' && $stmt_check_dup) {
                        $stmt_check_dup->execute([$uid, $new_cat_id, $e_date, $e_time, $amt, $desc]);
                        if ($stmt_check_dup->fetch()) {
                            $skipped_duplicates++;
                            continue; // Skip exact duplicate record
                        }
                    }

                    if (!empty($rec['custom_data']) && is_array($rec['custom_data'])) {
                        array_walk_recursive($rec['custom_data'], function(&$val) {
                            if (is_string($val)) $val = sanitize_input($val);
                        });
                        $custom_data = json_encode($rec['custom_data']);
                    } else {
                        $custom_data = null;
                    }
                    $stmt_rec->execute([
                        $uid,
                        $new_cat_id,
                        $e_date,
                        $e_time,
                        $amt,
                        $desc,
                        $custom_data
                    ]);
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

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data imported successfully', 'skipped_duplicates' => $skipped_duplicates]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Import failed.']);
    }
}

function handle_export_data($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    try {
        $uid = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, category_name, budget FROM user_categories WHERE user_id = ?");
        $stmt->execute([$uid]);
        $categories = $stmt->fetchAll();

        $export_data = [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'categories' => []
        ];

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
            } catch (PDOException $e) {
                // Ignore if table doesn't exist
            }

            $stmt_budgets = $pdo->prepare("SELECT budget_month, budget FROM category_monthly_budgets WHERE user_id = ? AND category_id = ?");
            $stmt_budgets->execute([$uid, $cat['id']]);
            $cat_data['monthly_budgets'] = $stmt_budgets->fetchAll();

            $stmt_rec = $pdo->prepare("SELECT entry_date, entry_time, amount, description, custom_data FROM expenses WHERE user_id = ? AND category_id = ?");
            $stmt_rec->execute([$uid, $cat['id']]);
            $records = $stmt_rec->fetchAll();

            foreach ($records as $rec) {
                $rec['custom_data'] = $rec['custom_data'] ? json_decode($rec['custom_data'], true) : [];
                $cat_data['records'][] = $rec;
            }
            $export_data['categories'][] = $cat_data;
        }

        header('Content-Disposition: attachment; filename="expense_backup_' . date('Ymd_His') . '.json"');
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
