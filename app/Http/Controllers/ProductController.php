<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_published', true)->with('category');

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Price filter
        if ($request->filled('min_price')) {
            $query->where('price_cents', '>=', $request->min_price * 100);
        }
        if ($request->filled('max_price')) {
            $query->where('price_cents', '<=', $request->max_price * 100);
        }

        // Category filter
        if ($request->filled('categories')) {
            $categoryIds = explode(',', $request->categories);
            $query->whereIn('category_id', $categoryIds);
        }

        // Sort
        $sort = $request->get('sort', '');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_cents', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_cents', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::all();

        return view('shop.index', compact('products', 'categories'));
    }

    public function show(Product $product)
    {
        if (!$product->is_published) {
            abort(404);
        }

        return view('shop.show', compact('product'));
    }
}
