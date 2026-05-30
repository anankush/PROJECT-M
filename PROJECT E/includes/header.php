<?php
require_once __DIR__ . '/security.php';
validate_domain_access();
session_start_secure();
generate_csrf_token();
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Expense Management'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo $base; ?>assets/images/logo.png">
    <?php echo get_csrf_meta_tag(); ?>
    <script src="<?php echo $base; ?>assets/js/input_validation.js"></script>
    <script src="<?php echo $base; ?>assets/js/page_transition.js"></script>
</head>
