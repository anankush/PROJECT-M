<?php
// Exp/includes/handlers/category_handlers.php
// Logic strictly mirrored from PROJECT E

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
            $query = "
                SELECT c.id, c.user_id, c.category_name,
                       COALESCE(mb.budget, c.budget) as budget,
                       c.created_at
                FROM user_categories c
                LEFT JOIN category_monthly_budgets mb ON c.id = mb.category_id AND mb.budget_month = ?
                WHERE c.user_id = ?
                ORDER BY c.id ASC
            ";
            $categories = $catModel->customQuery($query, [$month, $uid]);
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
            // Table doesn't exist yet — fallback to basic query without monthly_budgets join
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
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
        $uid = decode_id(sanitize_input($input['target_user_id']));
        if (!$uid) { http_response_code(404); echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']); return; }
    } elseif (!$uid && !$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $category_id = sanitize_input($input['category_id'] ?? '');
    $budget = floatval($input['budget'] ?? 0);
    $month = sanitize_input($input['month'] ?? '');

    if (empty($category_id) || !isset($input['budget'])) {
        echo json_encode(['status' => 'error', 'message' => 'Category ID and budget are required']);
        return;
    }

    try {
        $catModel = new Model($pdo, 'user_categories', $uid);
        if (!empty($month)) {
            $query = "INSERT INTO category_monthly_budgets (user_id, category_id, budget_month, budget) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE budget = VALUES(budget)";
            $catModel->executeQuery($query, [$uid, $category_id, $month, $budget]);
        } else {
            $catModel->update($category_id, ['budget' => $budget]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Section budget updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update budget.']);
    }
}

function handle_get_user_categories_admin($pdo) {
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        return;
    }

    $encoded_uid = sanitize_input($_GET['target_user_id'] ?? '');
    $uid = decode_id($encoded_uid);
    $month = sanitize_input($_GET['month'] ?? '');

    if (!$uid) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
        return;
    }

    try {
        if (!empty($month)) {
            $stmt = $pdo->prepare("
                SELECT c.id, c.category_name,
                       COALESCE((SELECT budget FROM category_monthly_budgets WHERE category_id = c.id AND budget_month = ?), c.budget) as budget,
                       COALESCE((SELECT SUM(amount) FROM expenses WHERE category_id = c.id AND DATE_FORMAT(entry_date, '%Y-%m') = ?), 0) as spent
                FROM user_categories c
                WHERE c.user_id = ?
                ORDER BY c.id ASC
            ");
            $stmt->execute([$month, $month, $uid]);
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
        if ($e->getCode() == '42S02') { // Table doesn't exist yet
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
