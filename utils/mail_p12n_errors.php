#!/usr/bin/env php
<?php

/* This file is used in a cronjob in case there are errors on products/channel
 * that I need to track.
 */
date_default_timezone_set('Europe/Rome');

$to = 'yourmailaddress@example.com';
$from = 'yourmailaddress@example.com';

if (php_sapi_name() != 'cli') {
    die('This command can only be used in CLI mode.');
}

$file_name = '/home/flod/public_html/p12n/errors.json';
$json_file = file_get_contents($file_name);
$json_data = json_decode($json_file, true);

// Supported channels
$channels = [
    'trunk'   => 'Nightly',
    'aurora'  => 'Developer Edition',
    'beta'    => 'Beta',
];

//Supported products
$products = [
    'browser' => 'Firefox',
    'mobile'  => 'Firefox for Android',
];

$output = '';
foreach ($json_data['locales'] as $locale_id => $locale_data) {
    foreach($locale_data as $product_id => $product_data) {
        if (array_key_exists($product_id, $products)) {
            // Check only if is a product I care about
            foreach($product_data as $channel_id => $channel_data) {
                // Check only if is a channel I care about
                if (array_key_exists($channel_id, $channels)) {
                    $output .= "<h2>{$locale_id}</h2>" .
                               '<p>There are errors in ' .
                               $products[$product_id] . ' (' .
                               $channels[$channel_id] . ' channel)</p>' .
                               '<ul>';
                    foreach ($channel_data as $type => $error_group) {
                        foreach ($error_group as $error_message) {
                            $output .= "<li><strong>{$type}</strong>: {$error_message}</li>";
                        }
                    }
                    $output .= '</ul>';
                }
            }
        }
    }
}

if ($output != '') {
    // $to and $from are defined at the beginning of the file
    $output = '<html><head><title>Mozilla Productization Errors</title></head><body>' .
              $output .
              '</body></html>';
    $subject = 'Mozilla Productization Errors - Notification for ' . date('Y-m-d');
    $headers = "From: {$from}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, $output, $headers);
}
