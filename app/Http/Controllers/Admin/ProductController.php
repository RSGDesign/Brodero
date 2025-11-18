<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $products = Product::with('category')->latest()->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function apiIndex()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json($products);
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price_ron' => 'required|numeric|min:0',
            'image_url' => 'nullable|url',
            'downloadable_file' => 'nullable|file',
            'is_published' => 'boolean'
        ]);

        $data = $request->except(['downloadable_file', 'price_ron']);
        
        // Convert RON to cents
        $data['price_cents'] = (int)($request->price_ron * 100);
        
        // Set default for is_published
        if (!isset($data['is_published'])) {
            $data['is_published'] = false;
        }

        if ($request->hasFile('downloadable_file')) {
            $path = $request->file('downloadable_file')->store('products', 'public');
            $data['downloadable_file'] = $path;
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produs creat');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'price_ron' => 'required|numeric|min:0',
            'image_url' => 'nullable|url',
            'downloadable_file' => 'nullable|file',
            'is_published' => 'boolean'
        ]);

        $data = $request->except(['downloadable_file', 'price_ron']);
        
        // Convert RON to cents
        $data['price_cents'] = (int)($request->price_ron * 100);
        
        // Set default for is_published
        if (!isset($data['is_published'])) {
            $data['is_published'] = false;
        }

        if ($request->hasFile('downloadable_file')) {
            if ($product->downloadable_file) {
                Storage::disk('public')->delete($product->downloadable_file);
            }
            $path = $request->file('downloadable_file')->store('products', 'public');
            $data['downloadable_file'] = $path;
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produs actualizat');
    }

    public function destroy(Product $product)
    {
        if ($product->downloadable_file) {
            Storage::disk('public')->delete($product->downloadable_file);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produs șters');
    }
}
