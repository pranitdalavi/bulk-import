<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductCsvImportService implements ToCollection, WithHeadingRow, WithChunkReading
{
    // Summary of import results
    public $summary = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'invalid' => 0,
        'duplicates' => 0
    ];

    // Track all SKUs across chunks to prevent duplicates in entire CSV
    protected $allSkus = [];

    // Process each row in the collection
    public function collection(Collection $rows)
    {
        $skusInBatch = [];

        foreach ($rows as $row) {
            $sku = isset($row['sku']) ? trim($row['sku']) : null;
            $name = isset($row['name']) ? trim($row['name']) : null;
            $price = isset($row['price']) ? $row['price'] : null;

            $this->summary['total']++;

            // Validate required columns otherwise count as invalid
            if (!$sku || !$name || !$price) {
                $this->summary['invalid']++;
                continue;
            }

            // Check duplicate in CSV (both batch and overall)
            if (in_array($sku, $skusInBatch) || in_array($sku, $this->allSkus)) {
                $this->summary['duplicates']++;
                continue;
            }

            $skusInBatch[] = $sku;
            $this->allSkus[] = $sku;

            // Upsert product
            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'description' => $row['description'] ?? null,
                    'price' => $price
                ]
            );

            if ($product->wasRecentlyCreated) {
                $this->summary['imported']++;
            } else {
                $this->summary['updated']++;
            }
        }
    }

    // Define chunk size for reading
    public function chunkSize(): int
    {
        return 500;
    }
}