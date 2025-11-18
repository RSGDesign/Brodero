<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\Page;
use App\Models\Newsletter;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function stats()
    {
        return response()->json([
            'products' => Product::count(),
            'pages' => Page::count(),
            'customers' => User::where('role', 'customer')->count(),
            'orders' => Order::count(),
            'newsletter' => Newsletter::count(),
        ]);
    }
}
