<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\Image as ImageModel;
use App\Models\Product;
use App\Jobs\ProcessImageVariants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class UploadController extends Controller
{
    public function uploadChunk(Request $request)
    {
        // Validate chunk upload request
        $request->validate([
            'file' => 'required|file',
            'dzchunkindex' => 'required|numeric',
            'filename' => 'required|string',
        ]);

        $filename = $request->filename;
        $index = $request->dzchunkindex;
        
        // Store chunks in a private temp folder
        $request->file('file')->storeAs("temp/{$filename}", "part_{$index}");

        return response()->json(['status' => 'chunk_saved']);
    }

    public function completeUpload(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'checksum' => 'required|string',
            'product_id' => 'required|exists:products,id',
            'totalChunks' => 'required|integer',
        ]);

        $filename = $request->filename;
        $lockKey = "merge_{$filename}";

        return Cache::lock($lockKey, 60)->get(function () use ($request, $filename) {
            $storage = Storage::disk('local');
            $finalPath = "uploads/{$filename}";
            
            // 1. Idempotency: Check if this file already exists in DB
            $existing = Upload::where('checksum', $request->checksum)->first();
            if ($existing) {
                $this->attachToProduct($existing->id, $request->product_id);
                return response()->json(['status' => 'already_exists', 'upload_id' => $existing->id]);
            }

            // 2. Stream Merge
            $fullPathOnDisk = $storage->path($finalPath);
            if (!file_exists(dirname($fullPathOnDisk))) {
                mkdir(dirname($fullPathOnDisk), 0755, true);
            }

            $out = fopen($fullPathOnDisk, "wb");
            for ($i = 0; $i < $request->totalChunks; $i++) {
                $chunkPath = $storage->path("temp/{$filename}/part_{$i}");
                if (!file_exists($chunkPath)) {
                    fclose($out);
                    return response()->json(['error' => "Missing chunk $i"], 422);
                }
                $in = fopen($chunkPath, "rb");
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);

            // 3. Verify Checksum
            if (md5_file($fullPathOnDisk) !== $request->checksum) {
                $storage->delete($finalPath);
                return response()->json(['error' => 'Checksum Mismatch'], 422);
            }

            // 4. Insert record into uploads table
            $upload = Upload::create([
                'filename' => $filename,
                'checksum' => $request->checksum,
                'completed' => true
            ]);

            // 5. Dispatch image processing job
            ProcessImageVariants::dispatch($finalPath, $request->product_id, $upload->id);

            // 6. Delete temp chunks
            $storage->deleteDirectory("temp/{$filename}");

            return response()->json(['status' => 'processing', 'upload_id' => $upload->id]);
        });
    }

    // Attach uploaded image to product if no primary image exists
    private function attachToProduct($uploadId, $productId)
    {
        $product = Product::find($productId);
        if ($product && !$product->primary_image_id) {
            // Find the 512px variant if it was already processed
            $image = ImageModel::where('upload_id', $uploadId)->where('size', 512)->first();
            if ($image) {
                $product->update(['primary_image_id' => $image->id]);
            }
        }
    }
}