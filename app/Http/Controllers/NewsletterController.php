<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletters,email'
        ]);

        Newsletter::create([
            'email' => $request->email,
            'subscribed_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Mulțumim pentru abonare!']);
    }

    public function index()
    {
        $subscribers = Newsletter::latest()->get();
        return response()->json($subscribers);
    }
}
