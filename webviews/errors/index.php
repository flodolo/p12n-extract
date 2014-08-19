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
    $json_data = file_get_contents($json_source);
    $json_array = json_decode($json_data, true);
    $locales = array_keys($json_array);


    $product_names = [
        'browser' => 'Firefox Desktop',
        'mobile'  => 'Firefox Mobile (Android)',
        'suite'   => 'Seamonkey',
        'mail'    => 'Thunderbird'
    ];
    $channels = ['trunk', 'aurora', 'beta', 'release'];
    $products = ['browser', 'mobile', 'suite', 'mail'];

    $html_output  = "<h1>Productization Errors</h1>\n";
    $html_output .= "<p>Filter by product</p>\n";
    $html_output .= "<ul class='filter'>\n";
    foreach ($products as $product) {
        $html_output .= "  <li><a href='?product={$product}'>{$product_names[$product]}</a></li>\n";
    }
    $html_output .= "  <li><a href='?'>all</a></li>\n";
    $html_output .= "</ul>\n";

    $html_output .= "<p>Filter by channel</p>\n";
    $html_output .= "<ul class='filter'>\n";
    foreach ($channels as $channel) {
        $html_output .= "  <li><a href='?channel={$channel}'>{$channel}</a></li>\n";
    }
    $html_output .= "  <li><a href='?'>all</a></li>\n";
    $html_output .= "</ul>\n";

    if (! empty($_REQUEST['channel'])) {
        if (in_array($_REQUEST['channel'], $channels)) {
            $channels = [$_REQUEST['channel']];
        }
    }
    if (! empty($_REQUEST['product'])) {
        if (in_array($_REQUEST['product'], $products)) {
            $products = [$_REQUEST['product']];
        }
    }

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
                        }
                        $html_output .= "</ul>\n";
                    }
                }
            }
        }
    }

    echo $html_output;

