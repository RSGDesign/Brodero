<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = User::where('role', 'customer')
            ->orWhereNull('role')
            ->latest()
            ->get(['id', 'name', 'email', 'created_at']);
        
        return response()->json($customers);
    }
}
