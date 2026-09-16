<?php
// includes/env.example.php — TEMPLATE
// Copy this file to env.php and fill in your actual credentials.
// env.php is gitignored and will NOT be pushed to the repository.
// Use define() for InfinityFree compatibility.

define('DB_HOST', 'your_db_host');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('SESSION_LIFETIME', 900);
define('BASE_URL', '/PROJECT M/');
define('APP_SECRET', 'generate_a_strong_random_key_here');
define('GOOGLE_SCRIPT_URL', 'your_google_apps_script_url_here');

define('VAPID_PUBLIC_KEY', 'your_vapid_public_key_here');
