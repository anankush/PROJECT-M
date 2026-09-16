<?php

function handle_get_categories($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $uid = $_SESSION['user_id'];
    $month = sanitize_input($_GET['month'] ?? '');

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);
        if (!empty($month)) {
            $current_month = date('Y-m');
            $query = "
                SELECT c.id, c.user_id, c.category_name,
                       CASE 
                           WHEN mb.budget IS NOT NULL THEN mb.budget
                           WHEN ? > ? THEN c.budget
                           ELSE NULL
                       END as budget,
                       mb.budget as monthly_budget,
                       c.created_at
                FROM user_categories c
                LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
                WHERE c.user_id = ?
                ORDER BY c.id ASC
            ";
            $categories = $catModel->customQuery($query, [$month, $current_month, $month, $uid]);
        } else {
            $query = "
                SELECT c.id, c.user_id, c.category_name,
                       COALESCE(SUM(mb.budget), c.budget) as budget,
                       c.created_at
                FROM user_categories c
                LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id
                WHERE c.user_id = ?
                GROUP BY c.id, c.user_id, c.category_name, c.budget, c.created_at
                ORDER BY c.id ASC
            ";
            $categories = $catModel->customQuery($query, [$uid]);
        }

        echo json_encode(['status' => 'success', 'data' => $categories]);
    } catch (PDOException $e) {
        if ($e->getCode() == '42S02') {
            $categories = $catModel->getAll([], 'id ASC');
            echo json_encode(['status' => 'success', 'data' => $categories]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch categories.']);
        }
    }
}

function handle_add_category($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'add_category');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_name = sanitize_input($input['category_name'] ?? '');
    if (empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Section name is required']);
        return;
    }

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);
        $newId = $catModel->insert(['category_name' => $category_name]);
        echo json_encode(['status' => 'success', 'message' => 'Section added successfully', 'category_id' => $newId]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add section.']);
    }
}

function handle_rename_category($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'rename_category');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'rename_category');
    $category_name = sanitize_input($input['category_name'] ?? '');

    if (empty($category_id) || empty($category_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID and new name are required']);
        return;
    }

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);
        $catModel->update($category_id, ['category_name' => $category_name]);
        echo json_encode(['status' => 'success', 'message' => 'Section renamed successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to rename section.']);
    }
}

function handle_delete_category($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'delete_category');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'delete_category');
    if (empty($category_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        return;
    }

    try {
        $pdo->beginTransaction();
        
        $expModel = new Model($pdo, 'expenses', $uid);
        $expModel->deleteWhere(['category_id' => $category_id]);
        
        $budgetModel = new Model($pdo, 'category_monthly_budgets', $uid);
        $budgetModel->deleteWhere(['category_id' => $category_id]);
        
        $catModel = new Model($pdo, 'user_categories', $uid);
        $catModel->delete($category_id);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Section deleted successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete section.']);
    }
}

function handle_update_category_budget($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'update_category_budget');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'update_category_budget');
    $budget = floatval($input['budget'] ?? 0);
    $month = sanitize_input($input['month'] ?? '');
    $months = $input['months'] ?? null;

    $overall_default = isset($input['overall_default']) && $input['overall_default'] === true;

    if (empty($category_id) || !isset($input['budget'])) {
        echo json_encode(['status' => 'error', 'message' => 'Category ID and budget are required']);
        return;
    }

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);
        if (!empty($months) && is_array($months)) {
            $pdo->beginTransaction();
            $query = "INSERT INTO category_monthly_budgets (user_id, category_id, budget_month, budget) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE budget = VALUES(budget)";
            foreach ($months as $m) {
                $m_sanitized = sanitize_input($m);
                $catModel->executeQuery($query, [$uid, $category_id, $m_sanitized, $budget]);
            }
            if ($overall_default) {
                $catModel->update($category_id, ['budget' => $budget]);
            }
            $pdo->commit();
        } elseif (!empty($month)) {
            $query = "INSERT INTO category_monthly_budgets (user_id, category_id, budget_month, budget) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE budget = VALUES(budget)";
            $catModel->executeQuery($query, [$uid, $category_id, $month, $budget]);
        } else {
            $catModel->update($category_id, ['budget' => $budget]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Section budget updated successfully']);
    } catch (PDOException $e) {
        if (!empty($months) && is_array($months) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Failed to update budget.']);
    }
}

function handle_clear_category_budget($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    $input = json_decode(file_get_contents('php://input'), true);

    if ($is_admin && isset($input['target_user_id'])) {
        $uid = verify_decoded_id($pdo, sanitize_input($input['target_user_id']), 'clear_category_budget');
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    $month = sanitize_input($input['month'] ?? '');
    $start_month = sanitize_input($input['start_month'] ?? '');
    $end_month = sanitize_input($input['end_month'] ?? '');
    $clear_all_section = isset($input['clear_all_section']) && $input['clear_all_section'] === true;

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);

        if (!empty($category_id)) {
            verify_ownership($pdo, 'user_categories', $category_id, $uid, 'clear_category_budget');

            if ($clear_all_section) {
                $stmt_check = $pdo->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM category_monthly_budgets WHERE user_id = ? AND category_id = ?) as custom_count,
                        (SELECT budget FROM user_categories WHERE id = ? AND user_id = ?) as default_budget
                ");
                $stmt_check->execute([$uid, $category_id, $category_id, $uid]);
                $chk = $stmt_check->fetch();
                $has_custom = intval($chk['custom_count'] ?? 0) > 0;
                $has_default = ($chk['default_budget'] !== null);
                if (!$has_custom && !$has_default) {
                    echo json_encode(['status' => 'error', 'message' => 'No budget configuration found to clear for this section']);
                    return;
                }
                $pdo->beginTransaction();
                $query_monthly = "DELETE FROM category_monthly_budgets WHERE user_id = ? AND category_id = ?";
                $catModel->executeQuery($query_monthly, [$uid, $category_id]);
                $catModel->update($category_id, ['budget' => null]);
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'All budgets cleared successfully']);
            } elseif (!empty($start_month) && !empty($end_month)) {
                $current_month = date('Y-m');
                if ($start_month > $current_month || $end_month > $current_month) {
                    echo json_encode(['status' => 'error', 'message' => 'Clear range cannot exceed the current month']);
                    return;
                }
                $stmt = $pdo->prepare("DELETE FROM category_monthly_budgets WHERE user_id = ? AND category_id = ? AND budget_month BETWEEN ? AND ?");
                $stmt->execute([$uid, $category_id, $start_month, $end_month]);
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'No custom budget records found in the selected range to clear']);
                    return;
                }
                echo json_encode(['status' => 'success', 'message' => 'Range budgets cleared successfully']);
            } elseif (!empty($month)) {
                $stmt = $pdo->prepare("DELETE FROM category_monthly_budgets WHERE user_id = ? AND category_id = ? AND budget_month = ?");
                $stmt->execute([$uid, $category_id, $month]);
                if ($stmt->rowCount() === 0) {
                    echo json_encode(['status' => 'error', 'message' => 'No custom budget record found for the selected month to clear']);
                    return;
                }
                echo json_encode(['status' => 'success', 'message' => 'Monthly budget cleared successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid clear request']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Required parameters missing']);
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Failed to clear budget.']);
    }
}

function handle_get_user_categories_admin($pdo) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $encoded_uid = sanitize_input($_GET['target_user_id'] ?? '');
    $uid = verify_decoded_id($pdo, $encoded_uid, 'get_user_categories_admin');
    $month = sanitize_input($_GET['month'] ?? '');

    try {
        if (!empty($month)) {
            $current_month = date('Y-m');
            $stmt = $pdo->prepare("
                SELECT c.id, c.category_name,
                       CASE
                           WHEN (SELECT budget FROM category_monthly_budgets WHERE category_id = c.id AND budget_month = ?) IS NOT NULL 
                                THEN (SELECT budget FROM category_monthly_budgets WHERE category_id = c.id AND budget_month = ?)
                           WHEN ? > ? THEN c.budget
                           ELSE NULL
                       END as budget,
                       COALESCE((SELECT SUM(amount) FROM expenses WHERE category_id = c.id AND DATE_FORMAT(entry_date, '%Y-%m') = ?), 0) as spent
                FROM user_categories c
                WHERE c.user_id = ?
                ORDER BY c.id ASC
            ");
            $stmt->execute([$month, $month, $month, $current_month, $month, $uid]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.id, c.category_name,
                       COALESCE((SELECT SUM(budget) FROM category_monthly_budgets WHERE category_id = c.id), c.budget) as budget,
                       COALESCE((SELECT SUM(amount) FROM expenses WHERE category_id = c.id), 0) as spent
                FROM user_categories c
                WHERE c.user_id = ?
                ORDER BY c.id ASC
            ");
            $stmt->execute([$uid]);
        }
        $categories = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $categories]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch categories.']);
    }
}

function handle_get_note($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $uid = $_SESSION['user_id'];
    $category_id = sanitize_input($_GET['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'get_note');

    if (empty($category_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        return;
    }

    try {
        $noteModel = new Model($pdo, 'user_notes', $uid);
        $result = $noteModel->getAll(['category_id' => $category_id]);
        $noteContent = !empty($result) ? $result[0]['note_content'] : '';
        echo json_encode(['status' => 'success', 'note' => $noteContent]);
    } catch (PDOException $e) {
        if ($e->getCode() == '42S02') {
            echo json_encode(['status' => 'success', 'note' => '']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch note.']);
        }
    }
}

function handle_save_note($pdo) {
    $uid = $_SESSION['user_id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    verify_ownership($pdo, 'user_categories', $category_id, $uid, 'save_note');
    $note = isset($input['note']) ? $input['note'] : '';

    if (strlen($note) > 1000) {
        echo json_encode(['status' => 'error', 'message' => 'Note exceeds 1000 characters.']);
        return;
    }

    if (empty($category_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        return;
    }

    try {
        $noteModel = new Model($pdo, 'user_notes', $uid);
        $query = "INSERT INTO user_notes (user_id, category_id, note_content) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE note_content = VALUES(note_content)";
        $noteModel->executeQuery($query, [$uid, $category_id, htmlspecialchars($note, ENT_QUOTES, 'UTF-8')]);
        echo json_encode(['status' => 'success', 'message' => 'Note saved successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save note.']);
    }
}

function handle_get_overall_budget($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    $uid = $_SESSION['user_id'];
    $month = sanitize_input($_GET['month'] ?? '');
    if (empty($month)) {
        echo json_encode(['status' => 'error', 'message' => 'Month is required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT budget FROM monthly_overall_budgets WHERE user_id = ? AND budget_month = ?");
        $stmt->execute([$uid, $month]);
        $budget = $stmt->fetchColumn();
        echo json_encode([
            'status' => 'success',
            'budget' => $budget !== false ? floatval($budget) : 0
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to fetch overall budget.']);
    }
}

function handle_update_overall_budget($pdo) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }
    $uid = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    $month = sanitize_input($input['month'] ?? '');
    $budget = floatval($input['budget'] ?? 0);

    if (empty($month)) {
        echo json_encode(['status' => 'error', 'message' => 'Month is required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO monthly_overall_budgets (user_id, budget_month, budget)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE budget = VALUES(budget)
        ");
        $stmt->execute([$uid, $month, $budget]);
        echo json_encode(['status' => 'success', 'message' => 'Overall budget updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update overall budget.']);
    }
}
