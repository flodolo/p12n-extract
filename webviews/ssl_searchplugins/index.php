<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Check SSL on Amazon</title>
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

        .green {
            color: green;
        }

        .red {
            color: red;
        }
    </style>
</head>

<body>

<?php
    $file_name = '../searchplugins.json';
    $json_file = file_get_contents($file_name);
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

    $requested_channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 'aurora';
    $requested_product = isset($_REQUEST['product']) ? $_REQUEST['product'] : 'browser';

    $html_intro = "<p>Last update: {$json_data['metadata']['creation_date']}</p>\n";

    // Create product filter
    $html_intro .= "<p>Filter by product</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($products as $product_id => $product_name) {
        $link = "?product={$product_id}";
        if ($requested_channel) {
            $link .= "&amp;channel={$requested_channel}";
        }
        $html_intro .= "  <li><a href='{$link}'>{$product_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";

    // Create channel filter
    $html_intro .= "<p>Filter by channel</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($channels as $channel_id => $channel_name) {
        $link = "?channel={$channel_id}";
        if ($requested_product) {
            $link .= "&amp;product={$requested_product}";
        }
        $html_intro .= "  <li><a href='{$link}'>{$channel_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";

    $html_errors = '<h1>List of searchplugins without SSL</h1><ul>';
    $locale_errors = [];
    $locale_good = [];
    $html_output = '';

    $html_output .= "<h1>Details</h1>\n<h2>{$products[$requested_product]} - {$channels[$requested_channel]}</h2>";
    foreach ($locales as $locale) {
        $html_output .= "<h3>{$locale}</h3>";
        if (isset($json_data['locales'][$locale][$requested_product])) {
            if (isset($json_data['locales'][$locale][$requested_product][$requested_channel]['searchplugins'])) {
                // I have searchplugins for this locale
                foreach ($json_data['locales'][$locale][$requested_product][$requested_channel]['searchplugins'] as $singlesp) {
                    $spfilename = strtolower($singlesp['file']);
                    $html_output .= "<p>{$singlesp['name']}: ";
                    if (! $singlesp['secure']) {
                        $html_output .= "<span class='red'>not SSL</span></p>";
                        array_push($locale_errors, $locale);
                        $html_errors .= "<li>{$locale} - {$products[$requested_product]}: {$singlesp['name']} ({$singlesp['file']}), not SSL</li>";
                    } else {
                        $html_output .= "<span class='green'>SSL</span></p>";
                        array_push($locale_good, $locale);
                    }
                }
            }
        }
    }
    $locale_errors = array_unique($locale_errors);

    $html_intro .= "<h1>Results</h1>\n";
    $html_intro .= "<p>" . count($locale_good) . " searchplugins with SSL.</p>";
    $html_intro .= "<p>" . count($locale_errors) . " searchplugins without SSL.</p>";
    if (count($locale_errors) > 0) {
        $html_output .= "<p>Locales with errors: ";
        foreach ($locale_errors as $locale) {
            $html_output .= $locale . ' ';
        }
        $html_output .= "</p>";
    }


    echo $html_intro;
    if (count($locale_errors) > 0) {
        $html_errors .= '</ul>';
        echo $html_errors;
    }
    echo $html_output;
