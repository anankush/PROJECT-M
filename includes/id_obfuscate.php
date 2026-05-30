<?php
// includes/id_obfuscate.php
// APP_SECRET is set by secrets.php (live) or env.php (local dev)
// The workflow defines it as APP_SECRET_VAL to avoid naming collisions.
if (!defined('APP_SECRET')) {
    if (defined('APP_SECRET_VAL')) {
        define('APP_SECRET', APP_SECRET_VAL);
    } else {
        define('APP_SECRET', 'CHANGE_ME_IN_PRODUCTION');
    }
}

function encode_id($id) {
    $id = (string)(int)$id;
    $hmac = hash_hmac('sha256', $id, APP_SECRET);
    return $id . '.' . $hmac;
}

function decode_id($token) {
    if (empty($token) || !is_string($token)) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    [$id, $hmac] = $parts;
    if (!is_numeric($id)) return null;
    $expected = hash_hmac('sha256', $id, APP_SECRET);
    if (!hash_equals($expected, $hmac)) return null;
    return (int)$id;
}
