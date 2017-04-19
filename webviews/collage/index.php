<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Searchplugins Images</title>
    <style type="text/css">
        body {
            background-color: #FCFCFC;
            font-family: Arial, Verdana;
            font-size: 14px;
            margin: 0 auto;
            width: 600px;
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
            padding: 4px 6px;
            border: 1px solid #000;
            background-color: #888;
            margin: 0 4px;
        }

        .filter a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
        }

        #collage {
            background-color: #FFF;
            padding: 20px;
            border: 1px solid #CCC;
            width: 450px;
            margin: 30px auto 0;
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
    if (isset($_REQUEST['product'])) {
        if (isset($products[$_REQUEST['product']])) {
            $requested_product = $_REQUEST['product'];
        }
    } else {
        $requested_product = 'browser';
    }

    if (isset($_REQUEST['channel'])) {
        if (isset($channels[$_REQUEST['channel']])) {
            $requested_channel = $_REQUEST['channel'];
        }
    } else {
        $requested_channel = 'central';
    }

    $html_output = "<h1>Images for $products[$requested_product] ($channels[$requested_channel])</h1>";

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
    $html_output .= "</ul>\n";

    $images = [];
    foreach ($locales as $locale) {
        if ($locale != 'en-US') {
            if (isset($json_data['locales'][$locale][$requested_product])) {
                if (isset($json_data['locales'][$locale][$requested_product][$requested_channel]['searchplugins'])) {
                    // I have searchplugins for this locale
                    foreach ($json_data['locales'][$locale][$requested_product][$requested_channel]['searchplugins'] as $singlesp) {
                        foreach ($singlesp['images'] as $imageindex) {
                            array_push($images, $imageindex);
                        }
                    }
                }
            }
        }
    }
    $images = array_unique($images);
    $html_output .= "<div id='collage'>";
    foreach ($images as $imageindex) {
        $html_output .= "<img style='padding: 4px;' src='{$json_data['images'][$imageindex]}' />\n";
    }
    $html_output .= "</div>";

    echo $html_output;
