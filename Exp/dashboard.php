<?php
// Exp/dashboard.php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_login();

header('Location: user/index.php');
exit;
