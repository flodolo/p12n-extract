#!/usr/bin/env php
<?php

if (php_sapi_name() != 'cli') {
    die('This command can only be used in CLI mode.');
}
date_default_timezone_set('Europe/Paris');

// Autoloading of composer dependencies
$script_folder = realpath(__DIR__ . '/');
require_once  "$script_folder/vendor/autoload.php";

$file_name = "$script_folder/../searchplugins.json";
$output_filename = "$script_folder/productization.xlsx";
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
];

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Locale');
$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Product');
$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Channel');
$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Type');
$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Name');
$objPHPExcel->getActiveSheet()->setCellValue('F1', 'XML filename');
$objPHPExcel->getActiveSheet()->setCellValue('G1', 'URL');
$objPHPExcel->getActiveSheet()->setCellValue('H1', 'Order');
$current_line = 2;

foreach ($locales as $locale) {
    foreach ($products as $product_id => $product_name) {
        if (isset($json_data['locales'][$locale][$product_id])) {
            foreach ($channels as $channel_id => $channel_name) {
                if (isset($json_data['locales'][$locale][$product_id][$channel_id])) {
                    $channel_data = $json_data['locales'][$locale][$product_id][$channel_id];
                    foreach ($channel_data['searchplugins'] as $searchplugin) {
                        $objPHPExcel->getActiveSheet()->setCellValue("A{$current_line}", $locale);
                        $objPHPExcel->getActiveSheet()->setCellValue("B{$current_line}", $product_name);
                        $objPHPExcel->getActiveSheet()->setCellValue("C{$current_line}", $channel_name);
                        $objPHPExcel->getActiveSheet()->setCellValue("D{$current_line}", 'searchplugin');
                        $objPHPExcel->getActiveSheet()->setCellValue("E{$current_line}", $searchplugin['name']);
                        $objPHPExcel->getActiveSheet()->setCellValue("F{$current_line}", $searchplugin['file']);
                        $objPHPExcel->getActiveSheet()->setCellValue("G{$current_line}", $searchplugin['url']);
                        $search_order = array_search($searchplugin['name'], $channel_data['p12n']['searchorder']);
                        if ($search_order === fALSE) {
                            $search_order = '-';
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("H{$current_line}", $search_order);
                        $current_line++;
                    }
                    foreach ($channel_data['p12n']['contenthandlers'] as $content_type => $content_handlers) {
                        foreach ($content_handlers as $handler_order => $handler_data) {
                            $objPHPExcel->getActiveSheet()->setCellValue("A{$current_line}", $locale);
                            $objPHPExcel->getActiveSheet()->setCellValue("B{$current_line}", $product_name);
                            $objPHPExcel->getActiveSheet()->setCellValue("C{$current_line}", $channel_name);
                            $objPHPExcel->getActiveSheet()->setCellValue("D{$current_line}", "{$content_type} content handler");
                            $objPHPExcel->getActiveSheet()->setCellValue("E{$current_line}", $handler_data['name']);
                            $objPHPExcel->getActiveSheet()->setCellValue("F{$current_line}", '-');
                            $objPHPExcel->getActiveSheet()->setCellValue("G{$current_line}", $handler_data['uri']);
                            $objPHPExcel->getActiveSheet()->setCellValue("H{$current_line}", $handler_order);
                            $current_line++;
                        }
                    }
                }
            }
        }
    }
}

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
$objWriter->save($output_filename);
