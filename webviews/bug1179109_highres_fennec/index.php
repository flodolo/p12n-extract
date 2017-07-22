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
    $channel = 'trunk';
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
    $known_assets = [];

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
    echo "<p>Locales with errors ({$locale_with_errors}): " . implode(', ', $locale_list) . "</p>\n";

    echo "<p>Clean locales: {$locale_clean}</p>\n<p>Errors: {$error_numbers}</p>";
    echo $table;
