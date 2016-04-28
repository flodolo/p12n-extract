#!/usr/bin/env php
<?php

/* This file is used in a cronjob in case there are errors on products/channel
 * that I need to track.
 */
date_default_timezone_set('Europe/Rome');

// You should only need to adapt the following variables. It's more reliable to
// use a settings.local.inc.php file to override these settings
$to = 'yourmailaddress@example.com';
$cc = 'yourmailaddress@example.com';
$from = 'yourmailaddress@example.com';
$log_file = false;

// Local path where .json files and script are stored
$base_path = '/srv/transvision/p12n';

/* Path to retrieve Transvision data
 * Standard path: https://transvision.mozfr.org/p12n
 * Can also be a local path.
 */
$uri_path = '/srv/transvision/github/web/p12n';

$settings_file_name = "{$base_path}/settings.local.inc.php";
if (file_exists($settings_file_name)) {
    // settings.local.inc.php can be used to override email addresses and
    // $log_file without storing them within the code.
    include($settings_file_name);
}

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

// Get the most recent version of the errors file
$errors_file_name = "{$base_path}/errors.json";
$file_content = file_get_contents("{$uri_path}/errors.json");
$file_handler = fopen($errors_file_name, 'w');
fwrite($file_handler, $file_content);
fclose($file_handler);
$errors_data = json_decode($file_content, true);

$main_output = '';
$output = '';
foreach ($errors_data['locales'] as $locale_id => $locale_data) {
    foreach($locale_data as $product_id => $product_data) {
        if (isset($products[$product_id])) {
            // Check only if it's a product I care about
            foreach($product_data as $channel_id => $channel_data) {
                // Check only if is a channel I care about
                if (isset($channels[$channel_id])) {
                    $output .= "<h2>{$locale_id}</h2>\n" .
                               "<p>There are errors in {$products[$product_id]} ({$channels[$channel_id]} channel):</p>\n<ul>\n";
                    foreach ($channel_data as $type => $error_group) {
                        foreach ($error_group as $error_message) {
                            $output .= "  <li><strong>{$type}</strong>: {$error_message}</li>\n";
                        }
                    }
                    $output .= "</ul>\n";
                }
            }
        }
    }
}
if ($output != '') {
    $main_output .= "\n<h1>Errors</h1>\n{$output}";
}

// Search for changes
$hashes_file_name = "{$base_path}/hashes.json";

$output = '';
if (! file_exists($hashes_file_name)) {
    // File doesn't exist, it's the first time I run the script
    // Store the file locally.
    $file_content = file_get_contents("{$uri_path}/hashes.json");
    $file_handler = fopen($hashes_file_name, 'w');
    fwrite($file_handler, $file_content);
    fclose($file_handler);
} else {
    $local_json = json_decode(file_get_contents($hashes_file_name), true);
    $remote_content = file_get_contents("{$uri_path}/hashes.json");
    $remote_json = json_decode($remote_content, true);
    foreach ($remote_json['locales'] as $locale_id => $locale_data) {
        $locale_errors = false;
        $locale_output = '';
        foreach($locale_data as $product_id => $product_data) {
            if (isset($products[$product_id])) {
                // Check only if it's a product I care about
                foreach($product_data as $channel_id => $channel_data) {
                    // Check only if is a channel I care about
                    if (isset($channels[$channel_id])) {
                        foreach ($channel_data as $file_id => $md5_hash) {
                            $files_data = $local_json['locales'][$locale_id][$product_id][$channel_id];
                            if (isset($files_data[$file_id])) {
                                if ($files_data[$file_id] != $md5_hash) {
                                    // Hash for this file has changed
                                    $locale_errors = true;
                                    $locale_output .= "<p><strong>{$file_id}</strong> was changed from the existing version in {$products[$product_id]} ({$channels[$channel_id]} channel).</p>\n";
                                }
                            } else {
                                // Local hash doesn't have this key
                                $locale_errors = true;
                                $locale_output .= "<p><strong>{$file_id}</strong> was added in {$products[$product_id]} ({$channels[$channel_id]} channel).</p>\n";
                            }
                        }
                    }
                }
            }
        }
        if ($locale_errors) {
            $output .= "  \n<h2>{$locale_id}</h2>\n" . $locale_output;
        }
    }
    // Store the new file
    $file_handler = fopen($hashes_file_name, 'w');
    fwrite($file_handler, $remote_content);
    fclose($file_handler);
}
if ($output != '') {
    $main_output .= '<h1>Changes ' . date('Y-m-d') . '</h1>' . $output;
}

if ($main_output != '') {
    // Send email only if there are errors
    // $to and $from are defined at the beginning of the file
    $mail_content = '<html><head><title>Mozilla Productization Updates and Errors</title></head><body>' .
        $main_output .
        '</body></html>';
    $subject = 'Mozilla Productization Updates and Errors - Notification for ' . date('Y-m-d');
    $headers = "From: {$from}\r\n";
    $headers .= "Cc: {$cc}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, $mail_content, $headers);
} else {
    $main_output = '<h1>No Changes for ' . date('Y-m-d') . "</h1>\n";
}

if ($log_file) {
    // Write an output.log file for debugging, also if there are no changes
    $file_name = "{$base_path}/output.log";
    $file_content = file_get_contents($file_name);
    $file_handler = fopen($file_name, 'w');
    fwrite($file_handler, $file_content . $main_output);
    fclose($file_handler);
}
