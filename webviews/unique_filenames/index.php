<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Duplicated Searchplugins</title>
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

        table {
            border: 1px solid #888;
            border-collapse: collapse;
        }

        td, th {
            border-top: 1px solid #888;
            border-bottom: 1px solid #888;
            border-collapse: collapse;
            padding: 2px 6px;
            min-width: 100px;
            text-align: left;
        }

        th {
            background-color: #BBB;
        }
    </style>
</head>

<body>

<?php
    $file_name = '../hashes.json';
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
    $requested_product = 'browser';
    if (isset($_REQUEST['product'])) {
        if (isset($products[$_REQUEST['product']])) {
            $requested_product = $_REQUEST['product'];
        }
    }

    $requested_channel = 'trunk';
    if (isset($_REQUEST['channel'])) {
        if (isset($channels[$_REQUEST['channel']])) {
            $requested_channel = $_REQUEST['channel'];
        }
    }

    $html_intro = "<p>Last update: {$json_data['metadata']['creation_date']}</p>\n";

    // Create product selector
    $html_intro .= "<p>Select product</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($products as $product_id => $product_name) {
        $html_intro .= "<li><a href='?product={$product_id}&amp;channel={$requested_channel}'>{$product_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";

    // Create channel selector
    $html_intro .= "<p>Select channel</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($channels as $channel_id => $channel_name) {
        $html_intro .= "<li><a href='?channel={$channel_id}&amp;product={$requested_product}'>{$channel_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";

    $html_output = "<h1>{$products[$requested_product]} - {$channels[$requested_channel]}</h1>";
    $html_output .= '
    <table>
      <thead>
        <tr>
          <th>Filename</th>
        </tr>
      </thead>
      <tbody>
    ';

    $sp_names = [];
    foreach ($locales as $locale) {
        if (isset($json_data['locales'][$locale][$requested_product])) {
            if (isset($json_data['locales'][$locale][$requested_product][$requested_channel])) {
                // I have data for this locale
                $sp_data = $json_data['locales'][$locale][$requested_product][$requested_channel];
                foreach ($sp_data as $filename => $hash) {
                    if (! in_array($filename, $sp_names)) {
                        $sp_names[] = $filename;
                    }
                }
            }
        }
    }
    sort($sp_names);

    foreach ($sp_names as $sp_name) {
        $html_output .= "
        <tr>
          <td>{$sp_name}</td>
        </tr>";
    }
    $html_output .= '
      </tbody>
    </table>
    ';

    echo $html_intro;
    echo $html_output;
