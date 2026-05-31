<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_categories';
$_GET['month'] = '2026-05';

require_once '../../includes/db.php';
session_start();
$_SESSION['user_id'] = 19; 

ob_start();
require_once 'api.php';
$output = ob_get_clean();

echo "OUTPUT:\n" . $output;
