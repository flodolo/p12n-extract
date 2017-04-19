<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Check High-Res Icons on Fennec</title>
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

        table {
            border-collapse: collapse;
        }

        td,
        th {
            border: 1px solid #ccc;
            padding: 10px;
        }

        .sp_image {
            padding: 0 4px;
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
    $channel = 'central';
    if (isset($_REQUEST['channel'])) {
        if (isset($channels[$_REQUEST['channel']])) {
            $channel = $_REQUEST['channel'];
        }
    }

    $repositories = [
        'trunk'   => 'https://hg.mozilla.org/l10n-central/',
        'beta'    => 'https://hg.mozilla.org/releases/l10n/mozilla-beta/',
        'release' => 'https://hg.mozilla.org/releases/l10n/mozilla-release/',
    ];

    // Only interested in Fennec
    $product = 'mobile';
    $product_name = 'Firefox for Android';


    // Known assets
    $known_assets = [
        'amazon' => 'data:image/png;base64,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',
        'bing'   => 'data:image/png;base64,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',
    ];

    $locale_with_errors = $locale_clean = $error_numbers = 0;

    $html_intro = "<p>Last update: {$json_data['metadata']['creation_date']}</p>\n";
    $html_intro .= "<p>Bug reference: <a href='https://bugzilla.mozilla.org/show_bug.cgi?id=1179109'>bug 1179109</a>.";

    // Create channel filter
    $html_intro .= "<p>Filter by channel</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($channels as $channel_id => $channel_name) {
        $html_intro .= "<li><a href='?channel={$channel_id}'>{$channel_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";
    $locale_list = [];

    $errors_detail = [];

    $table = '
<table>
  <thead>
    <tr>
      <th>Locale</th>
      <th>Images</th>
      <th>Errors</th>
    </tr>
  </thead>
  <tbody>';

    foreach ($locales as $locale) {
        if (isset($json_data['locales'][$locale][$product])) {
            if (isset($json_data['locales'][$locale][$product][$channel]['searchplugins'])) {
                // I have searchplugins for this locale
                $table .= "<tr id='{$locale}'>
                             <th>
                               <a href='#{$locale}'>{$locale}</a>
                             </th>
                           <td>\n";
                $errors = '';
                $locale_errors_detail = [];
                foreach ($json_data['locales'][$locale][$product][$channel]['searchplugins'] as $singlesp) {
                    $spfilename = strtolower($singlesp['file']);
                    // Android only has one image
                    $image_index = $singlesp['images'][0];
                    $image = $json_data['images'][$image_index];
                    $table .= "<img class='sp_image' src='{$image}' />\n";

                    $plugin_has_error = false;
                    // Check if the asset is obsolete
                    foreach ($known_assets as $provider => $known_image) {
                        if (strpos($spfilename, $provider) !== false &&
                            $image != $known_image) {
                            $errors .= "{$spfilename} uses an outdated image.<br/>";
                            $plugin_has_error = true;
                            $error_numbers++;
                            $locale_errors_detail[] = $spfilename;
                        }
                    }
                    // Check if the image is not 96px, but only if there are no errors
                    if (! $plugin_has_error) {
                        $image_data = getimagesize($image);
                        if ($image_data) {
                            list($width, $height, $type, $attr) = $image_data;
                            if ($width < 96 || $height < 96) {
                                $errors .= "{$spfilename} uses an image smaller than 96px ({$width}x{$height}px).<br/>";
                                $error_numbers++;
                                $locale_errors_detail[] = $spfilename;
                            }
                        }
                    }
                }
                if ($errors) {
                    $locale_with_errors++;
                    $locale_list[] = $locale;
                    // Save list of searchplugins with errors
                    $locale_errors_detail = array_unique($locale_errors_detail);
                    $errors_detail[$locale] = $locale_errors_detail;
                } else {
                    $locale_clean++;
                }
                $table .= "      <td>";
                if ($errors) {
                    $table .= "<a href='{$repositories[$channel]}{$locale}'>Link to repository</a><br/>{$errors}";
                } else {
                    $table .= "&nbsp;";
                }
                $table .= "      </td>\n    </tr>\n";
                $table .= "      </td>\n    </tr>\n";
            }
        }
    }

    $table .= '
  </tbody>
</table>
';
    echo $html_intro;
    echo "<p>Locales with errors ({$locale_with_errors}): " . implode(', ', $locale_list) . ".</p>\n";

    // Consider eBay a special case
    $locales_ebay = [];
    foreach ($locale_list as $locale) {
        if (isset($errors_detail[$locale]) &&
            count($errors_detail[$locale]) == 1 &&
            mb_strpos($errors_detail[$locale][0], 'ebay') !== false
            ) {
            // The only error for this locale is eBay
            $locales_ebay[] = $locale;
        }
    }
    $locales_without_ebay = array_diff($locale_list, $locales_ebay);

    echo "<p>Locales with errors ignoring eBay (" . count($locales_without_ebay) . "): " . implode(', ', $locales_without_ebay) . ".</p>\n";
    echo "<p>Clean locales: {$locale_clean}</p>\n<p>Errors: {$error_numbers}</p>";
    echo $table;
