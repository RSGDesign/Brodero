<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show()
    {
        $cart = Cart::where('user_id', Auth::id())->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Coșul este gol']);
        }

        return view('shop.checkout', compact('cart'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'payment_method' => 'required|in:card,transfer,free'
        ]);

        $cart = Cart::where('user_id', Auth::id())->with('items.product', 'coupon')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return back()->withErrors(['cart' => 'Coșul este gol']);
        }

        $totalCents = $cart->totalCents();

        // Free order (total is zero after discount)
        if ($totalCents === 0) {
            return $this->createFreeOrder($cart, $request);
        }

        // Transfer payment
        if ($request->payment_method === 'transfer') {
            return $this->createTransferOrder($cart, $request);
        }

        // Card payment via Stripe
        return $this->createStripeSession($cart, $request);
    }

    private function createFreeOrder(Cart $cart, Request $request)
    {
        return DB::transaction(function () use ($cart, $request) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_cents' => 0,
                'payment_method' => 'free',
                'status' => 'paid',
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'notes' => $request->notes
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price_cents_snapshot' => $item->price_cents_snapshot
                ]);
            }

            // Increment coupon usage
            if ($cart->coupon_code) {
                Coupon::where('code', $cart->coupon_code)->increment('uses_count');
            }

            $cart->items()->delete();
            $cart->delete();

            return redirect()->route('checkout.success', ['order' => $order->id]);
        });
    }

    private function createTransferOrder(Cart $cart, Request $request)
    {
        return DB::transaction(function () use ($cart, $request) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_cents' => $cart->totalCents(),
                'payment_method' => 'transfer',
                'status' => 'pending',
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'notes' => $request->notes
            ]);

            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price_cents_snapshot' => $item->price_cents_snapshot
                ]);
            }

            $cart->items()->delete();
            $cart->delete();

            return redirect()->route('checkout.success', ['order' => $order->id]);
        });
    }

    private function createStripeSession(Cart $cart, Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = $cart->items->map(function ($item) {
            return [
                'price_data' => [
                    'currency' => 'ron',
                    'product_data' => ['name' => $item->product->title],
                    'unit_amount' => $item->price_cents_snapshot,
                ],
                'quantity' => $item->quantity,
            ];
        })->toArray();

        // Add discount as negative line item if exists
        if ($cart->discount_cents > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'ron',
                    'product_data' => ['name' => 'Discount ('.$cart->coupon_code.')'],
                    'unit_amount' => -$cart->discount_cents,
                ],
                'quantity' => 1,
            ];
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.show'),
            'metadata' => [
                'user_id' => Auth::id(),
                'cart_id' => $cart->id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone ?? '',
                'notes' => $request->notes ?? '',
                'coupon_code' => $cart->coupon_code ?? ''
            ]
        ]);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        $order = null;

        if ($request->filled('order')) {
            $order = Order::findOrFail($request->order);
        } elseif ($request->filled('session_id')) {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve($request->session_id);
            // Order would have been created by webhook
            $order = Order::where('user_id', $session->metadata->user_id)
                ->latest()
                ->first();
        }

        return view('shop.success', compact('order'));
    }

    public function webhook(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            DB::transaction(function () use ($session) {
                $cart = Cart::find($session->metadata->cart_id);
                if (!$cart) return;

                $order = Order::create([
                    'user_id' => $session->metadata->user_id,
                    'total_cents' => $session->amount_total,
                    'payment_method' => 'card',
                    'status' => 'paid',
                    'customer_name' => $session->metadata->customer_name,
                    'customer_email' => $session->metadata->customer_email,
                    'customer_phone' => $session->metadata->customer_phone,
                    'notes' => $session->metadata->notes
                ]);

                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price_cents_snapshot' => $item->price_cents_snapshot
                    ]);
                }

                // Increment coupon usage
                if ($session->metadata->coupon_code) {
                    Coupon::where('code', $session->metadata->coupon_code)->increment('uses_count');
                }

                $cart->items()->delete();
                $cart->delete();
            });
        }

        return response()->json(['status' => 'success']);
    }
}
