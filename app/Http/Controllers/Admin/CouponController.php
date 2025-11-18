<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        // Check if it's an API request
        if (request()->expectsJson() || request()->is('*/api/*')) {
            $coupons = Coupon::latest()->get();
            return response()->json($coupons);
        }
        
        $coupons = Coupon::latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function show($id)
    {
        $coupon = Coupon::findOrFail($id);
        return response()->json($coupon);
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
            'active' => 'boolean',
            'max_uses' => 'nullable|integer|min:0',
            'min_order_cents' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Convert min_order_cents to min_order_value (RON)
        if (isset($data['min_order_cents'])) {
            $data['min_order_value'] = $data['min_order_cents'] / 100;
            unset($data['min_order_cents']);
        }
        
        // Set defaults for API requests
        if (!isset($data['active'])) $data['active'] = true;
        if (!isset($data['max_uses'])) $data['max_uses'] = 0;

        $coupon = Coupon::create($data);

        // API response
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json($coupon, 201);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Cupon creat');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        
        $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code,'.$coupon->id,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
            'active' => 'boolean',
            'max_uses' => 'nullable|integer|min:0',
            'min_order_cents' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        
        // Convert min_order_cents to min_order_value (RON)
        if (isset($data['min_order_cents'])) {
            $data['min_order_value'] = $data['min_order_cents'] / 100;
            unset($data['min_order_cents']);
        }

        $coupon->update($data);

        // API response
        if ($request->expectsJson() || $request->is('*/api/*')) {
            return response()->json($coupon);
        }

        return redirect()->route('admin.coupons.index')->with('success', 'Cupon actualizat');
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        
        // API response
        if (request()->expectsJson() || request()->is('*/api/*')) {
            return response()->json(['message' => 'Cupon șters']);
        }
        
        return redirect()->route('admin.coupons.index')->with('success', 'Cupon șters');
    }
}
