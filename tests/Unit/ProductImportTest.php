<?php

namespace Tests\Unit;

use App\Services\ProductCsvImportService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_creates_and_updates()
    {
        // --- First import: two new products ---
        $rows = new Collection([
            ['sku' => 'SKU1', 'name' => 'Product 1', 'price' => 10],
            ['sku' => 'SKU2', 'name' => 'Product 2', 'price' => 20],
        ]);

        $importService1 = new ProductCsvImportService();
        $importService1->collection($rows);

        // Check summary of first import
        $this->assertEquals(2, $importService1->summary['imported'], 'Two products should be imported');
        $this->assertEquals(0, $importService1->summary['updated'], 'No products should be updated');

        // --- Second import: update SKU1 only ---
        $rows2 = new Collection([
            ['sku' => 'SKU1', 'name' => 'Updated Product 1', 'price' => 15],
        ]);

        $importService2 = new ProductCsvImportService();
        $importService2->collection($rows2);

        // Check summary of second import
        $this->assertEquals(0, $importService2->summary['imported'], 'No new products should be imported');
        $this->assertEquals(1, $importService2->summary['updated'], 'SKU1 should be updated');

        // --- Verify database values ---
        $product1 = Product::where('sku', 'SKU1')->first();
        $this->assertEquals('Updated Product 1', $product1->name);
        $this->assertEquals(15, $product1->price);

        $product2 = Product::where('sku', 'SKU2')->first();
        $this->assertEquals('Product 2', $product2->name);
        $this->assertEquals(20, $product2->price);
    }
}