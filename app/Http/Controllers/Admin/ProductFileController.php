<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductFileController extends Controller
{
    public function index($productId)
    {
        $files = ProductFile::where('product_id', $productId)->latest()->get();
        return response()->json($files);
    }

    public function upload(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        
        $request->validate([
            'files' => 'required|file|max:51200' // 50MB max
        ]);

        $uploadedFiles = [];
        
        $file = $request->file('files');
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . $originalName;
        
        // Store in product_files directory
        $path = $file->storeAs('product_files', $filename, 'public');
        
        $productFile = ProductFile::create([
            'product_id' => $productId,
            'filename' => $filename,
            'original_name' => $originalName,
            'filesize' => $file->getSize()
        ]);
        
        $uploadedFiles[] = $productFile;

        return response()->json([
            'message' => 'Fișier încărcat cu succes',
            'files' => $uploadedFiles
        ], 201);
    }

    public function destroy($productId, $fileId)
    {
        $file = ProductFile::where('product_id', $productId)->findOrFail($fileId);
        
        // Delete physical file
        Storage::disk('public')->delete('product_files/' . $file->filename);
        
        $file->delete();
        
        return response()->json(['message' => 'Fișier șters cu succes']);
    }
}
