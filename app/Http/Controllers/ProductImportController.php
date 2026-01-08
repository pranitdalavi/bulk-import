<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ProductCsvImportService;

class ProductImportController extends Controller
{
    protected $importService;

    // Constructor to inject the import service
    public function __construct(ProductCsvImportService $importService)
    {
        $this->importService = $importService;
    }

    // Handle CSV import request
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        // Use the Excel facade to import the CSV using the service
        Excel::import($this->importService, $request->file('csv_file'));

        return response()->json([
            'summary' => $this->importService->summary
        ]);
    }
}
