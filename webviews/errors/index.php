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

        a {
            color: #0096dd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
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

        .product {
            color: #fff;
            text-transform: uppercase;
            padding: 2px 8px;
            margin: 1px 3px 1px 0;
            display: inline-block;
            font-size: 10px;
            border-radius: 6px;
        }

        .browser {
            background-color: #f58667;
        }

        .mobile {
            background-color: #3d8014;
        }

        .suite {
            background-color: #8ac451;
        }

        .mail {
            background-color: #3161a3;
        }

        .error,
        .warning {
            text-transform: uppercase;
            padding: 2px 8px;
            margin: 1px 3px 1px 0;
            display: inline-block;
            font-size: 10px;
            border-radius: 6px;
            width: 24px;
            text-align: center;
        }

        .error {
            background-color: #ea3b28;
            color: #fff;
        }

        .warning {
            background-color: #FAE455;
            color: #000;
        }

        .wauto {
            width: auto;
        }
    </style>
</head>

<body>

<?php
    $json_source = '../errors.json';
    if (! file_exists($json_source)) {
        die("errors.json is missing");
    }
    $json_file = file_get_contents($json_source);
    $json_data = json_decode($json_file, true);

    // Supported locales
    $locales = array_keys($json_data["locales"]);
    $locales = array_unique($locales);
    sort($locales);

    // Supported channels
    $channels = [
        'trunk'   => 'Nightly',
        'aurora'  => 'Developer Edition',
        'beta'    => 'Beta',
        'release' => 'Release',
    ];

    //Supported products
    $products = [
        'browser' => 'Firefox',
        'mobile'  => 'Firefox for Android',
        'suite'   => 'Seamonkey',
        'mail'    => 'Thunderbird',
    ];

    // Get filter parameters
    $requested_product = '';
    if (isset($_REQUEST['product'])) {
        if (isset($products[$_REQUEST['product']])) {
            $requested_product = $_REQUEST['product'];
        }
    }

    $requested_channel = '';
    if (isset($_REQUEST['channel'])) {
        if (isset($channels[$_REQUEST['channel']])) {
            $requested_channel = $_REQUEST['channel'];
        }
    }

    // Title section (changes according to filters)
    $extra_title = '';
    if ($requested_product) {
        $extra_title .= " - $products[$requested_product]";
    }
    if ($requested_channel) {
        $extra_title .= " ({$channels[$requested_channel]})";
    }
    $html_output  = "<h1>Productization Errors{$extra_title}</h1>\n";
    $html_output .= "<p class='update'>Last update: {$json_data["metadata"]["creation_date"]}</p>\n";
    $html_output .= "<p><span class='error'>sp</span> identifies an error in /searchplugins,
                     <span class='error'>p12n</span> identifies an error in region.properties.</br>
                     <span class='error wauto'>errors</span> and <span class='warning wauto'>warnings</span>
                     have different colors.</p>";

    // Filter by product
    $html_output .= "<p>Filter by product</p>\n";
    $html_output .= "<ul class='filter'>\n";
    foreach ($products as $product_id => $product_name) {
        $link = "?product={$product_id}";
        if ($requested_channel) {
            $link .= "&amp;channel={$requested_channel}";
        }
        $html_output .= "  <li><a href='{$link}'>{$product_name}</a></li>\n";
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
    foreach ($channels as $channel_id => $channel_name) {
        $link = "?channel={$channel_id}";
        if ($requested_product) {
            $link .= "&amp;product={$requested_product}";
        }
        $html_output .= "  <li><a href='{$link}'>{$channel_name}</a></li>\n";
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
        foreach (array_keys($channels) as $channel_id) {
            if ($channel_id != $requested_channel) {
                unset($channels[$channel_id]);
            }
        }
    }
    if ($requested_product) {
        foreach (array_keys($products) as $product_id) {
            if ($product_id != $requested_product) {
                unset($products[$product_id]);
            }
        }
    }

    foreach ($channels as $channel_id => $channel_name) {
        $error_count = 0;
        $html_output .= "<h2>Repository: <a id='{$channel_id}' href='?channel={$channel_id}'>{$channel_name}</a></h2>";
        foreach ($locales as $locale) {
            $title = "<h3>Locale: <a id='{$locale}_{$channel_id}' href='#{$locale}_{$channel_id}'>{$locale}</a></h2>";
            $printed_title = false;
            $issues_list = [];
            $locale_html_output = '';
            foreach ($products as $product_id => $product_name) {
                if (isset($json_data['locales'][$locale][$product_id][$channel_id])) {
                    if (! $printed_title) {
                        $locale_html_output .= $title;
                        $locale_html_output .= "<ul>\n";
                        $printed_title = true;
                    }
                    $product_part = "<span class='product {$product_id}'>{$product_name}</span>";
                    foreach ($json_data['locales'][$locale][$product_id][$channel_id] as $key => $value) {
                        foreach ($value as $message) {
                            switch ($key) {
                                case 'errors':
                                    $error_class = 'error sp';
                                    $error_text = 'sp';
                                    break;
                                case 'warnings':
                                    $error_class = 'warning sp';
                                    $error_text = 'sp';
                                    break;
                                case 'p12n_errors':
                                    $error_class = 'error p12n';
                                    $error_text = 'p12n';
                                    break;
                                case 'p12n_warnings':
                                    $error_class = 'warning p12n';
                                    $error_text = 'p12n';
                                    break;
                                default:
                                    $error_class = 'issue';
                                    $error_text = '';
                                    break;
                            }
                            $locale_html_output .= "  <li><span class='{$error_class}'>{$error_text}</span>{$product_part}{$message}</li>\n";
                            $error_count++;
                        }
                    }
                }
            }
            if ($locale_html_output) {
                $locale_html_output .= "</ul>\n";
                $html_output .= $locale_html_output;
            }
        }
        if ($error_count == 0) {
            $html_output .= "<p>No errors found.</p>";
        }
    }

    echo $html_output;
