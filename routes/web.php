<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\UploadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function () {
    return redirect('/upload');
});

// Product CSV Import Route
Route::post('/import/products', [ProductImportController::class, 'import']);

// Image Upload Routes
Route::post('/upload/chunk', [UploadController::class, 'uploadChunk']);
Route::post('/upload/complete', [UploadController::class, 'completeUpload']);

// Render upload form for csv import and image upload
Route::get('/upload', function() {
    return view('upload');
});