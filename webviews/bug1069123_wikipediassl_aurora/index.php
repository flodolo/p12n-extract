<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset=utf-8>
    <title>Check SSL on Wikimedia</title>
    <style type="text/css">
        body { background-color: #FCFCFC; font-family: Arial, Verdana; font-size: 14px; padding: 10px; }
        p { margin-top: 2px; }
        h2 { clear: both; }
        .green { color: green; }
        .red { color: red; }
    </style>
</head>

<body>

<?php
    $file_name = '../searchplugins.json';
    $json_file = file_get_contents($file_name);
    $json_data = json_decode($json_file, true);

    $locales = [];
    foreach (array_keys($json_data) as $locale) {
        $locales[] = $locale;
    }
    sort($locales);

    $channel = 'aurora';
    $products = array('browser', 'mobile');
    echo "<p>Last update: {$json_data['creation_date']}</p>\n";
    echo "<p>Bug reference: <a href='https://bugzilla.mozilla.org/show_bug.cgi?id=1069123'>bug 1069123</a>.";

    $html_errors = '<h1>Errors</h1><ul>';
    $locale_errors = [];
    $html_output = '';

    foreach ($products as $i=>$product) {
        $html_output .= "<h1>Details</h1>\n<h2>{$product} - {$channel}</h2>";
        foreach ($locales as $locale) {
            $html_output .= "<h3>{$locale}</h3>";
            if (isset($json_data[$locale][$product])) {
                if (isset($json_data[$locale][$product][$channel])) {
                    // I have searchplugins for this locale
                    foreach ($json_data[$locale][$product][$channel] as $key => $singlesp) {
                        if ($key != 'p12n') {
                            $spfilename = strtolower($singlesp['file']);
                            if (strpos($singlesp['url'], 'wikipedia') !== false ||
                                strpos($singlesp['url'], 'wiktionary') !== false) {
                                if (strpos($spfilename, 'metrofx') === false) {
                                    $html_output .= "<p>{$singlesp['name']}: ";
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
                }
            }
        }
        $locale_errors = array_unique($locale_errors);
        if (count($locale_errors) > 0) {
            $html_output .= "<p>Locales with errors: ";
            foreach ($locale_errors as $locale) {
                $html_output .= $locale . ' ';
            }
            $html_output .= "</p>";
        }
    }

    if (count($locale_errors) > 0) {
        $html_errors .= '</ul>';
        echo $html_errors;
    }

    echo $html_output;
