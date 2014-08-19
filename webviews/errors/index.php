<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Productization Errors</title>
    <style type="text/css">
        body {
            background-color: #fdfdfd;
            font-family: Arial, Verdana;
            font-size: 14px;
            margin: 0 auto;
            padding: 10px 20px;
        }

        h1 {
            margin-top: 20px;
        }

        .filter:after {
            content: '';
            display: block;
            clear: both;
        }

        .filter li {
            display: inline;
            float: left;
            padding: 8px;
            border: 1px solid #000;
            background-color: #888;
            margin: 0 4px;
        }

        .filter a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
        }

        code {
            display: inline-block;
            background-color: #e1e1e1;
            font-family: monospace;
            font-size: 13px;
            padding: 4px;
        }

        .update {
            font-size: 12px;
        }

        .warnings,
        .p12n_warnings {
            color: #ffbf00;
            font-weight: bold;
        }

        .errors,
        .p12n_errors {
            color: #f00;
            font-weight: bold;
        }
    </style>
</head>

<body>

<?php
    $json_source = '../errors.json';
    if (! file_exists($json_source)) {
        die("errors.json is missing");
    }
    $json_data = file_get_contents($json_source);
    $json_array = json_decode($json_data, true);

    // Local arrays
    $locales = array_keys($json_array);
    $product_names = [
        'browser' => 'Firefox Desktop',
        'mobile'  => 'Firefox Android',
        'suite'   => 'Seamonkey',
        'mail'    => 'Thunderbird'
    ];
    $channels = ['trunk', 'aurora', 'beta', 'release'];
    $products = ['browser', 'mobile', 'suite', 'mail'];

    // Get filter parameters
    $requested_product = '';
    if (isset($_REQUEST['product'])) {
        if (in_array($_REQUEST['product'], $products)) {
            $requested_product = $_REQUEST['product'];
        }
    }

    $requested_channel = '';
    if (isset($_REQUEST['channel'])) {
        if (in_array($_REQUEST['channel'], $channels)) {
            $requested_channel = $_REQUEST['channel'];
        }
    }

    // Title section (changes according to filters)
    $extra_title = '';
    if ($requested_product) {
        $extra_title .= " - $product_names[$requested_product]";
    }
    if ($requested_channel) {
        $extra_title .= " ({$requested_channel})";
    }
    $html_output  = "<h1>Productization Errors{$extra_title}</h1>\n";
    $html_output .= "<p class='update'>Last update: {$json_array["metadata"]["creation_date"]}</p>\n";

    // Filter by product
    $html_output .= "<p>Filter by product</p>\n";
    $html_output .= "<ul class='filter'>\n";
    foreach ($products as $product) {
        $link = "?product={$product}";
        if ($requested_channel) {
            $link .= "&amp;channel={$requested_channel}";
        }
        $html_output .= "  <li><a href='{$link}'>{$product_names[$product]}</a></li>\n";
    }
    $reset_link = "?";
    if ($requested_channel) {
        // If set, keep channel and reset only product
        $reset_link .= "channel={$requested_channel}";
    }
    $html_output .= "  <li><a href='{$reset_link}'>all</a></li>\n";
    $html_output .= "</ul>\n";

    // Filter by channel
    $html_output .= "<p>Filter by channel</p>\n";
    $html_output .= "<ul class='filter'>\n";
    foreach ($channels as $channel) {
        $link = "?channel={$channel}";
        if ($requested_product) {
            $link .= "&amp;product={$requested_product}";
        }
        $html_output .= "  <li><a href='{$link}'>{$channel}</a></li>\n";
    }
    $reset_link = "?";
    if ($requested_product) {
        // If set, keep product and reset only channel
        $reset_link .= "product={$requested_product}";
    }
    $html_output .= "  <li><a href='{$reset_link}'>all</a></li>\n";
    $html_output .= "</ul>\n";

    // Filter channels and products arrays
    if ($requested_channel) {
        $channels = [$requested_channel];
    }
    if ($requested_product) {
        $products = [$requested_product];
    }

    $error_count = 0;
    foreach ($channels as $channel) {
        $html_output .= "<h2>Repository: <a id='{$channel}' href='?channel={$channel}'>{$channel}</a></h2>";
        foreach ($locales as $locale) {
            $title = "<h3>Locale: <a id='{$locale}_{$channel}' href='#{$locale}_{$channel}'>{$locale}</a></h2>";
            $printed_title = false;
            foreach ($products as $product) {
                if (isset($json_array[$locale][$product][$channel])) {
                    if (! $printed_title) {
                        $html_output .= $title;
                        $printed_title = true;
                    }
                    $html_output .= "<h3>{$product_names[$product]}</h3>";
                    foreach ($json_array[$locale][$product][$channel] as $key => $value) {
                        $name = str_replace('_', ' ', $key);
                        $name = strtoupper(str_replace('p12n', 'Productization', $name));
                        $html_output .= "<p class='{$key}'>{$name}:</p>\n";
                        $html_output .= "<ul>\n";
                        foreach ($value as $message) {
                            $html_output .= "  <li>{$message}</li>\n";
                            $error_count++;
                        }
                        $html_output .= "</ul>\n";
                    }
                }
            }
        }
    }

    if ($error_count == 0) {
        $html_output .= "<p>No errors or warnings available.</p>";
    }

    echo $html_output;

