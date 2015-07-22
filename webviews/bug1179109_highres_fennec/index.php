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
        'aurora'  => 'Developer Edition',
        'beta'    => 'Beta',
        'release' => 'Release',
    ];
    $channel = isset($_REQUEST['channel']) ? $_REQUEST['channel'] : 'aurora';

    $repositories = [
        'trunk'   => 'https://hg.mozilla.org/l10n-central/',
        'aurora'  => 'https://hg.mozilla.org/releases/l10n/mozilla-aurora/',
        'beta'    => 'https://hg.mozilla.org/releases/l10n/mozilla-beta/',
        'release' => 'https://hg.mozilla.org/releases/l10n/mozilla-release/',
    ];

    // Only interested in Fennec
    $product = 'mobile';
    $product_name = 'Firefox for Android';


    // Known assets
    $known_assets = [
        'amazon' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAYAAADimHc4AAAAAXNSR0IArs4c6QAACixJREFUeAHtXQ1MldcZfuRXlIr4A1MoYAf+xKBswlKmdTJ1Q7RZdc5kM7XWsXaGWk2WTDPc0qlzOmZqnHGZoxrntuiydB1UXZSRqS1h03WTVdDa+osov/I3qAicve93ufTC/eHc673f+ejOmxzu933n/c77nuc5/+fcC/CJjKDLXArlFFopCB38igFjytgyxoz1APkM3ZVQ0KCbgwFjzZgbwmxo8M0B3rGAM+YjgunPdyhspKDFXASeInPVTMABCvHm2tbW+hCI5eaHO4cnNCRKEGhjArhd0qIIgSBFdrXZPgQ0AYqLgiZAE6AYAcXmdQ3QBChGQLF5XQM0AYoRUGxe1wBNgGIEFJvXNUAToBgBxeZ1DdAEKEZAsfkQxfZ9Nh8SEoKwsDD09PTg4cOHPqej+sVhQcC4ceOwdOlSZGRkICUlxQhJSUkIDuYNPaCpqQm3bt3CzZs3UVlZiVOnTqG8vNwgRzXAMvYdN4otdT1//nxBYIpHjx4Jb6W2tlbk5+eLqKgoS+WJCBnsj9ODwQqm38fHx4uSkhJvMXep39zcLNauXWt6HlwA7c4HaxGwZMkS0dDQ4BLMx3m4b98+dwCofm4dAhYuXOhTcyNLzObNm1WD7cq+NQiYNm2a4OYikNLZ2SmSk5NdgaDymTUIOHnyZCCx70/70KFDKsF2sm2JYyk02sHZs2dlRmyoqanB4cOHcfnyZbS2tmLq1KlYs2YN0tLSpN5vbGxEbGyspYaoTqx40YP75d2jR4/2l1BPFzt37hQjR450shkUFCR27drl6dUBcenp6U5pmJ1nB3tqmyAGr76+fgBArm4KCgqGBI0mX65edXq2YsWKIdNyACigusoX41JTUzFhwgTKr3uhzhnbtm1zr9AXc+TIkSF1WIHmGVJ6ZigpJ+DatWtG+9/d3e02v8eOHUNbW5vbeHtERUWF/dLjJy9tWEWUrwV1dHRgwYIFxrpOXFwcEhMTMWXKFCPwek9oaCi2b98uhVd7e7uUHi/kWUUs4wmvat6+fdsI58+f9wkfT7XIMUHqdxxvlV5bxxM/wEC9rVQqsnpSiT2mkmVqgEw+uDnicf/MmTMxY8YMozOdPHkyOEyaNAkTJ06UScZSOpYnIDMzEzk5OcjOzjYmW1Zqv/3BpCUJCA8Px+rVq7Fp0ybwMPXTLJYjYN68eSgsLAQtzn2ace/Pm6U64S1btuDcuXP/N+AzC5apAVu3bpUe77PjPJLh/V9enKOlDNAmjjFnWL9+PUd7FCuNgtjRgK51yKS/aNEiwkRO6urqxMaNG0VMTIyT39OnT5dKZMeOHU7vyvgZCB3lNSAiIgIHDx6kvA0tpaWlWL58ubEM7UrbfkrCVZxVnyknYOXKlcayw1AAXbhwAcuWLQPtarlVHY4EKO+E161b5xZQxwg6YuIRfNYdjnMEpQSMHj0avBs2lNy5cwdnzpwZSm1YzoSVEsCTLJmFMdllZj41JyPR0dEyaqboKCUgISFBKpOe2n3HBPj4oozwsrdVRCkBtL8rhYNMieWZ8+LFi6XSmzNnDkaM4PMI6kUpAXTmUwoB2kQ3JlmelGnDvv+wric9juMtSVmyhkrLH/HKJiVZWVlSEydW2rBhg1s/aQlDOh274qVLlwQtb7tNk4A1K840Q04Zos140dvba8fE42dXV5egFVJBTUd/OrQPIPbu3evxPU+Re/bs6U/LRMAH21RHAGeaRjieMHKKq6qqErRJL06fPi3oixlO8d4+sMAZIbUEcNOiUmjkNLhEmn2vloDIyEhx7949v3Fw8eJFo4bIJLh7926zwXZlTy0B3AzRdqN0X+AJ2OLiYkGza8GkXr161a0qnZ4QtGztCgwVz9QTwCTk5uYKBsZX2b9/v6DFuH4A6UyRqK6udkqODngJCzQ7/X5S3q1BAPvB+wJXrlxxAs3Tg7KyMsHfJXOVDzo5MeDcKa0pidmzZ7vUdXyfjg2J1DiI59IgXsmCePXLEHn0mZMKkTDOv3jxdJAd8lqSY4DrDUBvr9evenyBj56sWrXKCPSNGfCCnaPQsBXUvIAPbxUVFeHEiROO0U7XPOs9cOCAsXOWl5dnfDop9T1IiQW+/xXga3TSfWKkOy3gj+8B3/iV+3hvYnwm4P3XbL9Anf8W8Na/vTHpnS4f3OXzPnzssKWlxTgjSjXCu0QktHetAL5HKxkhEmsDnTSBH/WKRKISKj4TkDge+BNtv37uSeDvN4D8PwN/rZKwaFGVl2lVvPVj4E4Tfe+4A2im0Eb3wUTIdPqZ7Re/CLz0jM35FtoTGrvJPxnxmQA2HxEGvLEG+GaGzZmKu8AvSoHf/QPo7PKPg1ZJZewo4MHrNm/OXQO+9HP/eCZR4dwbYpC/VQhsfpP6AmoVZtEq76+fB+7uBn72dSDJ87F/9wkrjmG/f5ADHH8JGN/XF0QTAXZ59yP71eN/PlYNcDSfReeo3ngBmEJNk12YlGI6sv8b+rcFpyuBdqrSVpXYMcCqdFttznzK5iX7n7ETeO828PzTlI8Xbc+X7gdO/sc/OfEbAezOKGqSfvIcQMM2BHHKDtLVA5z9AHibCHmbnL9e7xCp6DKaBljL0wj0LwBcgIIdfL7RCLz8W+AMFRyWQmpqvz0XuEnPU34IdFN+/CF+JcDu0NNUgn65Gkjz8E2gqvs2Mt75EPgnlbC7D+xvB+6TxvCYmwzMozD3swCN9Z0KSncv8HoJ8Fox0NHXj4XSb4LcK6DmiAh79bitn/OXlwEhgJ3jDSeu0j9+FphG4+uhpLaNiLhlq+4f1gFcAm80ADXNQA+B4o3EUHOSNJ5KaozNNo9iuFA86WErmFobFF0CflQEVFQPtEYTMmPEV9NCaW79hJiBWr7dBYwAuzs8jHshkzrqbGAqAeKtcDvc9F+gjgh60GHLvL1kcjMXFgJEhtM/QKDdzbER9I9ZougZlVhZ6aH0j18AfvoX4H0axbkSHm7TLNgY+ZRfd6Xh+7OAE2B3jWsEt7PfnQ9wieJqrVLutwJ/uAjsKwU+qnfvCfcT9wuAvN9TP/COez1fY0wjwNFBHnFwx/fsLOAZao/NIqORatKb/wKOUYn/Gw0IZJZRRoYCcxKBd6mvCoQoIcAxI1HUbHx1JpBNIZ0yOmOS3HKAYxrurhvagTJqMspo3M4Alt/w3+jFnU1vnysnYLDDXOJmxQOfT7B1oHFjgTjqPGOfAMYQWWOorQ+ndp/7ho8f0XIB/VxcIwFdT4E7bW5OOHCH/kHt4NStd285AmQg4o7d25GRTLoqdIYlASqACpRNKktaVCKgCVCJPtnWBGgCFCOg2LyuAZoAxQgoNq9rgCZAMQKKzesaoAlQjIBi87oGaAIUI6DYvK4BmgDFCCg2zzWAtru1KEKgjQmoVGRcmyXsmYBCjYQyBAzseVeshALtsupgIgaMOWNvCJ0d0yQQBmYVQgafMR8gzEYuhXIKdGzJNGfMyrRqO4wpY8sY95f8/wEKvBLprcz3zwAAAABJRU5ErkJggg==',
        'bing' => 'data:image/png;base64,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',
        'duckduckgo' => 'data:image/png;base64,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',
        'google' => 'data:image/png;base64,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',
        'twitter' => 'data:image/png;base64,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',
        'wikipedia' => 'data:image/png;base64,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',
        'yahoo' => 'data:image/png;base64,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',
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
                            }
                        }
                    }
                }
                if ($errors) {
                    $locale_with_errors++;
                } else {
                    $locale_clean++;
                }
                $table .= "      <td>
                                   <a href='{$repositories[$channel]}{$locale}'>Link to repository</a><br/>
                                   {$errors}
                                 </td>\n    </tr>\n";
                $table .= "      </td>\n    </tr>\n";
            }
        }
    }

    $table .= '
  </tbody>
</table>
';

    echo $html_intro;
    echo "<p>Locales with errors: {$locale_with_errors}</p>\n<p>Clean locales: {$locale_clean}</p>\n<p>Errors: {$error_numbers}</p>";
    echo $table;
