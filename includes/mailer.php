<?php
// includes/mailer.php
if (!defined('GOOGLE_SCRIPT_URL')) {
    define('GOOGLE_SCRIPT_URL', getenv('GOOGLE_SCRIPT_URL') ?: '');
}

function send_email($to, $subject, $body) {
    if (empty(GOOGLE_SCRIPT_URL)) {
        error_log('GOOGLE_SCRIPT_URL not configured. Email not sent to: ' . $to);
        return false;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => GOOGLE_SCRIPT_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POSTFIELDS     => http_build_query(['email' => $to, 'subject' => $subject, 'body' => $body])
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($httpCode >= 200 && $httpCode < 400);
}
