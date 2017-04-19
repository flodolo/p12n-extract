<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset=utf-8>
    <title>Check SSL status on handlers + Yahoo</title>
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

    $channels = [
        'trunk'   => 'Nightly',
        'beta'    => 'Beta',
        'release' => 'Release',
    ];

    $products = [
        'browser' => 'Firefox',
        'mobile'  => 'Firefox for Android',
        'suite'   => 'Seamonkey',
        'mail'    => 'Thunderbird',
    ];

    $channel = 'central';
    if (isset($_REQUEST['channel'])) {
        if (isset($channels[$_REQUEST['channel']])) {
            $channel = $_REQUEST['channel'];
        }
    }

    // For this view I'm not interested in Seamonkey and Thunderbird
    unset($products['suite']);
    unset($products['mail']);

    $html_intro = "<p id='lastupdate'>Last update: {$json_data['metadata']['creation_date']}</p>\n";
    $html_intro .= "<p>Bug reference: <a href='https://bugzilla.mozilla.org/show_bug.cgi?id=994248'>bug 994248</a>.";

    // Create channel filter
    $html_intro .= "<p>Filter by channel</p>\n";
    $html_intro .= "<ul class='filter'>\n";
    foreach ($channels as $channel_id => $channel_name) {
        $html_intro .= "<li><a href='?channel={$channel_id}'>{$channel_name}</a></li>\n";
    }
    $html_intro .= "</ul>\n";

    $html_errors = "<h1>Errors</h1>\n<ul>";

    foreach ($products as $product => $product_name) {
        $html_output = "<h1>Details</h1>\n<h2>{$product_name} - {$channels[$channel]}</h2>";

        $locale_list = '';
        $non_english_locale_list = '';
        $locale_errors = [];

        foreach ($locales as $locale) {
            $html_output .= "<h3 id='{$locale}'><a href='{$locale}'>{$locale}</a></h3>";
            if (isset($json_data['locales'][$locale][$product])) {
                // We have this product+locale, check searchplugins
                if (isset($json_data['locales'][$locale][$product][$channel]['searchplugins'])) {
                    // We have searchplugins for this locale
                    foreach ($json_data['locales'][$locale][$product][$channel]['searchplugins'] as $singlesp) {
                        $spfilename = strtolower($singlesp['file']);
                        if (strpos($spfilename, 'yahoo') !== false) {
                            $locale_list .= "{$locale}, ";
                            if (strpos($singlesp['description'], 'en-US') === false) {
                                $non_english_locale_list .= "{$locale}, ";
                            }
                            // We have Yahoo, check if it's set as second searchplugin
                            if (array_key_exists(2, $json_data['locales'][$locale][$product][$channel]['p12n']['searchorder'])) {
                                $second_search = strtolower($json_data['locales'][$locale][$product][$channel]['p12n']['searchorder'][2]);
                                if (strpos($second_search, 'yahoo') === false) {
                                    $html_output .= "<p>{$locale} doesn't have Yahoo as second ({$second_search})</p>";
                                }
                            } else {
                               $html_output .= "<p>{$locale} doesn't have a second search plugin</p>";
                            }
                        }

                        if (strpos($singlesp['url'], 'yahoo') !== false ||
                            strpos($singlesp['url'], 'google') !== false) {
                            if (strpos($spfilename, 'metrofx') === false) {
                                $html_output .= "<p>{$singlesp['name']} (search): ";
                                if (! $singlesp['secure']) {
                                    $html_output .= "<span class='red'>not SSL</span></p>";
                                    array_push($locale_errors, $locale);
                                    $html_errors .= "<li>{$locale} - {$product}: {$singlesp['name']} ({$singlesp['file']}), not SSL</li>";
                                } else {
                                    $html_output .= "<span class='green'>SSL</span></p>";
                                }
                            }
                        }
                    }
                }

                // Check productization
                if (isset($json_data['locales'][$locale][$product][$channel]['p12n'])) {
                    $p12n_data = $json_data['locales'][$locale][$product][$channel]['p12n'];

                    // Check productization for Google and Yahoo
                    if (isset($p12n_data['contenthandlers'])) {
                        $contenthandlers = $p12n_data['contenthandlers']['mailto'];
                        foreach ($contenthandlers as $contenthandler) {
                            if (strpos($contenthandler['uri'], 'yahoo') !== false ||
                                strpos($contenthandler['uri'], 'google') !== false) {
                                $html_output .= "<p>{$contenthandler['name']} (mailto): ";
                                if ((strpos($contenthandler['uri'], 'https') === false)) {
                                    $html_output .= "<span class='red'>not SSL</span></p>";
                                    $html_errors .= "<li>{$locale} - {$product}: {$contenthandler['name']}, not SSL</li>";
                                    array_push($locale_errors, $locale);
                                } else {
                                    $html_output .= "<span class='green'>SSL</span></p>";
                                }
                            }
                        }
                    } else {
                        $html_output .= "<p class='red'>mailto handler is missing</p>";
                        $html_errors .= "<li>{$locale} - {$product}: mailto handler is missing</li>";
                    }

                    // 30 boxes
                    if (isset($p12n_data['contenthandlers']['webcal'])) {
                        $contenthandlers = $p12n_data['contenthandlers']['webcal'];
                        foreach ($contenthandlers as $contenthandler) {
                            if (strpos($contenthandler['uri'], '30boxes') !== false) {
                                $html_output .= "<p>{$contenthandler['name']} (webcal): ";
                                if ((strpos($contenthandler['uri'], 'https') === false)) {
                                    $html_output .= "<span class='red'>not SSL</span></p>";
                                    $html_errors .= "<li>{$locale} - {$product}: {$contenthandler['name']}, not SSL</li>";
                                    array_push($locale_errors, $locale);
                                } else {
                                    $html_output .= "<span class='green'>SSL</span></p>";
                                }
                            }
                        }
                    } else {
                        $html_output .= "<p class='red'>webcal handler is missing</p>";
                        $html_errors .= "<li>{$locale} - {$product}: webcal handler is missing</li>";
                    }

                    if (isset($p12n_data['feedhandlers'])) {
                        $feedhandlers = $p12n_data['feedhandlers'];
                        foreach ($feedhandlers as $feedhandler) {
                            if (strpos($feedhandler['uri'], 'yahoo') !== false ||
                                strpos($feedhandler['uri'], 'google') !== false) {
                                $html_output .= "<p>{$feedhandler['title']} (feed): ";
                                if ((strpos($feedhandler['uri'], 'https') === false)) {
                                    $html_output .= "<span class='red'>not SSL</span></p>";
                                    $html_errors .= "<li>{$locale} - {$product}: {$feedhandler['title']}, not SSL</li>";
                                    array_push($locale_errors, $locale);
                                } else {
                                    $html_output .= "<span class='green'>SSL</span></p>";
                                }
                            }
                        }
                    } else {
                        $html_output .= "<p class='red'>feed handler is missing</p>";
                        $html_errors .= "<li>{$locale} - {$product}: feed handler is missing</li>";
                    }


                }
            }
        }
        $html_output .= "<p>List of locales with Yahoo: {$locale_list}</p>";
        $html_output .= "<p>List of locales with localized versions of Yahoo: {$non_english_locale_list}</p>";
        $locale_errors = array_unique($locale_errors);
        if (count($locale_errors) > 0) {
            $html_output .= "<p>Locales with errors: ";
            foreach ($locale_errors as $locale) {
                $html_output .= $locale . ' ';
            }
            $html_output .= "</p>";
        }
    }

    echo $html_intro;
    if (count($locale_errors) > 0) {
        $html_errors .= '</ul>';
        echo $html_errors;
    }
    echo $html_output;
