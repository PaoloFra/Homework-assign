<?php

require __DIR__ . '/vendor/autoload.php';

use Homework\CommissionTask\Service\CommissionFee;

array_shift($argv);
if (!isset ($argv[0])) {
    echo 'Usage: php commission.php path/to/.csv/file';
    die();
}
$filename = $argv[0] = 'input.csv';
ini_set('auto_detect_line_endings', TRUE);

$operations = [];
if (file_exists($filename) && is_file($filename)) {
    $handle = fopen($filename, 'r');
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $operations[] = $data;
    }
    fclose($handle);
} else {
    echo 'file ' . $filename . ' not found';
    die();
}

ini_set('auto_detect_line_endings', FALSE);

$CommissionFeeList = new CommissionFee(['EUR' => 1,
    'USD' => 1.1497,
    'JPY' => 129.53]
);
print_r( $CommissionFeeList->process($operations));

print_r($operations);
//var_dump($argv);
