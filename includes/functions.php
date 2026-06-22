<?php
// Base URL
$base_url = '/english-course/';

function url($path = '') {
    global $base_url;
    return $base_url . ltrim($path, '/');
}

function isActive($pages = []) {
    $current = $_SERVER['PHP_SELF'];
    foreach ($pages as $page) {
        if (strpos($current, $page) !== false) {
            return 'active';
        }
    }
    return '';
}
?>