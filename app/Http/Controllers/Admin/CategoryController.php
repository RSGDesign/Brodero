<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        // Check if it's an API request
        if (request()->expectsJson() || request()->is('*/api/*')) {
            $categories = Category::latest()->get();
            return response()->json($categories);
        }
        
        $categories = Category::withCount('products')->latest()->paginate(20);
        return view('admin.categories.index', compact('categories'));
    }

    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug'
        ]);

        $data = $request->all();
        if (!$request->filled('slug')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category = Category::create($data);

        // API response
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json($category, 201);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Categorie creată');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug,'.$category->id
        ]);

        $data = $request->all();
        if (!$request->filled('slug')) {
            $data['slug'] = Str::slug($request->name);
        }

        $category->update($data);

        // API response
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json($category);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Categorie actualizată');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        
        // API response
        if (request()->expectsJson() || request()->is('*/api/*')) {
            return response()->json(['message' => 'Categorie ștearsă']);
        }
        
        return redirect()->route('admin.categories.index')->with('success', 'Categorie ștearsă');
    }
}
