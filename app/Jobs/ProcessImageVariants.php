<?php

namespace App\Jobs;

use App\Models\Image as ImageModel;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProcessImageVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $fullPath,
        protected int $productId,
        protected int $uploadId
    ) {}

    // Process the image to create variants and link to product
    public function handle()
    {
        // Define image sizes
        $sizes = [256, 512, 1024];
        $filename = basename($this->fullPath);
        $absolutePath = Storage::disk('local')->path($this->fullPath);

        $manager = new ImageManager(new Driver());

        // Process each size variant
        foreach ($sizes as $size) {
            $variantPath = "images/{$size}_{$filename}";
            
            $image = $manager->read($absolutePath);
            $image->scale(width: $size); // Aspect ratio has been maintained here

            Storage::disk('public')->put($variantPath, (string) $image->toJpg(80));

            $imageRecord = ImageModel::create([
                'upload_id' => $this->uploadId,
                'path' => $variantPath,
                'size' => $size
            ]);

            // Link to product if it's the 512px version and no primary image exists
            if ($size === 512) {
                $product = Product::find($this->productId);
                if ($product && !$product->primary_image_id) {
                    $product->update(['primary_image_id' => $imageRecord->id]);
                }
            }
        }
    }
}