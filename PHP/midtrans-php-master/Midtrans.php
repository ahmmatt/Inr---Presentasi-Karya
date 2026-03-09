<?php
/** 
 * Check PHP version.
 */
if (version_compare(PHP_VERSION, '5.4', '<')) {
    throw new Exception('PHP version >= 5.4 required');
}

// Check PHP Curl & json decode capabilities.
if (!function_exists('curl_init') || !function_exists('curl_exec')) {
    throw new Exception('Midtrans needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
    throw new Exception('Midtrans needs the JSON PHP extension.');
}

// Configurations
require_once 'midtrans-php-master/Midtrans/Config.php';

// Midtrans API Resources
require_once 'midtrans-php-master/Midtrans/Transaction.php';

// Plumbing
require_once 'midtrans-php-master/Midtrans/ApiRequestor.php';
require_once 'midtrans-php-master/Midtrans/Notification.php';
require_once 'midtrans-php-master/Midtrans/CoreApi.php';
require_once 'midtrans-php-master/Midtrans/Snap.php';
require_once 'midtrans-php-master/SnapBi/SnapBi.php';
require_once 'midtrans-php-master/SnapBi/SnapBiApiRequestor.php';
require_once 'midtrans-php-master/SnapBi/SnapBiConfig.php';

// Sanitization
require_once 'midtrans-php-master/Midtrans/Sanitizer.php';
