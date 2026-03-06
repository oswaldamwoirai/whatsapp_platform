<?php

if (!function_exists('format_phone')) {
    function format_phone($phone) {
        // Format phone number for WhatsApp
        return preg_replace('/[^0-9]/', '', $phone);
    }
}

if (!function_exists('generate_webhook_token')) {
    function generate_webhook_token() {
        return bin2hex(random_bytes(16));
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
