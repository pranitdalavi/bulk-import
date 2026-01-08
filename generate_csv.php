<?php

$filename = 'products_10000.csv';
$rows = 10000;

$handle = fopen($filename, 'w');

// Add header
fputcsv($handle, ['sku', 'name', 'description', 'price']);

// Generate 10,000 rows
for ($i = 1; $i <= $rows; $i++) {
    $sku = "SKU" . str_pad($i, 5, '0', STR_PAD_LEFT);
    $name = "Product $i";
    $description = "Description for product $i";
    $price = rand(100, 10000) / 100; // Random price 1.00 to 100.00
    fputcsv($handle, [$sku, $name, $description, $price]);
}

fclose($handle);

echo "CSV file generated: $filename\n";