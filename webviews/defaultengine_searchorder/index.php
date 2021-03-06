<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Display defaults</title>
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
    $html_output .= "<table>
      <thead>
        <tr>
          <th>Locale</th>
          <th>Default</th>
          <th>Search #1</th>
          <th>Search #2</th>
          <th>Search #3</th>
        </tr>
      </thead>
      <tbody>\n";
    foreach ($locales as $locale) {
        $searchorder = [
            '1' => '-',
            '2' => '-',
            '3' => '-',
        ];
        $default = '-';

        if (isset($json_data['locales'][$locale][$requested_product][$requested_channel])) {
            $p12n_record = $json_data['locales'][$locale][$requested_product][$requested_channel];
            $default = $p12n_record['p12n']['defaultenginename'];
            for ($i = 1; $i < 4; $i++) {
                if (isset($p12n_record['p12n']['searchorder'][$i])) {
                    $searchorder[$i] = $p12n_record['p12n']['searchorder'][$i];
                } else {
                    $searchorder[$i] = '-';
                }
            }
        }

        $html_output .= "
        <tr>
          <td>{$locale}</td>
          <td>{$default}</td>
          <td>{$searchorder[1]}</td>
          <td>{$searchorder[2]}</td>
          <td>{$searchorder[3]}</td>
        </tr>\n";
    }
    $html_output .= "
      </tbody>
    </table>\n";

    echo $html_intro;
    echo $html_output;
