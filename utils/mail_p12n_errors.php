#!/usr/bin/env php
<?php

/* This file is used in a cronjob in case there are errors on products/channel
 * that I need to track.
 */
date_default_timezone_set('Europe/Rome');

// You should only need to adapt the following variables.
$to = 'yourmailaddress@example.com';
$from = 'yourmailaddress@example.com';
// Path where .json files are stored
$base_path = '/home/flod/public_html/p12n';
$url_path = 'https://transvision.mozfr.org/p12n';

if (php_sapi_name() != 'cli') {
    die("This command can only be used in CLI mode.\n");
}

// Supported channels
$channels = [
    'trunk'   => 'Nightly',
    'aurora'  => 'Developer Edition',
    'beta'    => 'Beta',
];

// Supported products
$products = [
    'browser' => 'Firefox',
    'mobile'  => 'Firefox for Android',
];


// Search for errors
$file_name = "{$base_path}/errors.json";
if (! file_exists($file_name)) {
    die("{$file_name} does not exist.\n");
}
$json_file = file_get_contents($file_name);
$json_data = json_decode($json_file, true);

$main_output = '';
$output = '';
foreach ($json_data['locales'] as $locale_id => $locale_data) {
    foreach($locale_data as $product_id => $product_data) {
        if (isset($products[$product_id])) {
            // Check only if it's a product I care about
            foreach($product_data as $channel_id => $channel_data) {
                // Check only if is a channel I care about
                if (isset($channels[$channel_id])) {
                    $output .= "<h2>{$locale_id}</h2>" .
                               "<p>There are errors in {$products[$product_id]} ({$channels[$channel_id]} channel):</p>\n<ul>\n";
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
    $main_output .= '<h1>Errors</h1>' . $output;
}

// Search for changes
$file_name = "{$base_path}/hashes.json";

$output = '';
if (! file_exists($file_name)) {
    // File doesn't exist, it's the first time I run the script
    // Store the file locally.
    $file_content = file_get_contents("{$url_path}/hashes.json");
    $file_handler = fopen($file_name, 'w');
    fwrite($file_handler, $file_content);
    fclose($file_handler);
} else {
    $local_json = json_decode(file_get_contents($file_name), true);
    $remote_content = file_get_contents("{$url_path}/hashes.json");
    $remote_json = json_decode($remote_content, true);
    foreach ($remote_json['locales'] as $locale_id => $locale_data) {
        foreach($locale_data as $product_id => $product_data) {
            if (isset($products[$product_id])) {
                // Check only if it's a product I care about
                foreach($product_data as $channel_id => $channel_data) {
                    // Check only if is a channel I care about
                    foreach ($channel_data as $file_id => $md5_hash) {
                        try {
                            if ($local_json['locales'][$locale_id][$product_id][$channel_id][$file_id] != $md5_hash) {
                                // Hash for this file has changed
                                $output .= "<h2>{$locale_id}</h2>\n";
                                $output .= "<p><strong>{$file_id}</strong> was changed from the existing version in {$products[$product_id]} ({$channels[$channel_id]} channel).</p>\n";
                            }
                        } catch (Exception $e) {
                            // Local hash doesn't have this key
                            $output .= "<h2>{$locale_id}</h2>\n";
                            $output .= "<p><strong>{$file_id}</strong> was added in {$products[$product_id]} ({$channels[$channel_id]} channel).</p>\n";
                        }
                    }
                }
            }
        }
    }
    // Store the new file
    $file_handler = fopen($file_name, 'w');
    fwrite($file_handler, $remote_content);
    fclose($file_handler);
}
if ($output != '') {
    $main_output .= '<h1>Changes</h1>' . $output;
}

if ($main_output != '') {
    // $to and $from are defined at the beginning of the file
    $output = '<html><head><title>Mozilla Productization Errors</title></head><body>' .
              $output .
              '</body></html>';
    $subject = 'Mozilla Productization Errors - Notification for ' . date('Y-m-d');
    $headers = "From: {$from}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, $main_output, $headers);
}
