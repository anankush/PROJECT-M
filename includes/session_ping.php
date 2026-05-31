<?php
require_once __DIR__ . '/auth_check.php';
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'valid' => true]);
