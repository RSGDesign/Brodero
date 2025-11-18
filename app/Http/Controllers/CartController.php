<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart();
        $cart->load('items.product', 'coupon');

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $cart->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price_cents' => $item->price_cents_snapshot,
                        'product' => [
                            'title' => $item->product->title,
                            'image_url' => $item->product->image_url
                        ]
                    ];
                }),
                'subtotal_cents' => $cart->subtotalCents(),
                'discount_cents' => $cart->discount_cents,
                'total_cents' => $cart->totalCents()
            ]);
        }

        return view('shop.cart', compact('cart'));
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $cart = $this->getOrCreateCart();

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->quantity += $request->get('quantity', 1);
            $cartItem->save();
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $request->get('quantity', 1),
                'price_cents_snapshot' => $product->price_cents
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Produs adăugat în coș');
    }

    public function updateItem(Request $request, CartItem $item)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        if ($item->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $item->quantity = $request->quantity;
        $item->save();

        return redirect()->route('cart.index')->with('success', 'Cantitate actualizată');
    }

    public function removeItem(CartItem $item)
    {
        if ($item->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $item->delete();

        return redirect()->route('cart.index')->with('success', 'Produs eliminat');
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $cart = $this->getOrCreateCart();
        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return back()->withErrors(['code' => 'Cupon invalid']);
        }

        $subtotal = $cart->subtotalCents() / 100;

        if (!$coupon->isUsable($subtotal)) {
            return back()->withErrors(['code' => 'Cuponul nu poate fi utilizat']);
        }

        // Calculate discount
        $discountCents = 0;
        if ($coupon->type === 'percent') {
            $discountCents = (int) (($cart->subtotalCents() * $coupon->value) / 100);
        } else {
            $discountCents = (int) ($coupon->value * 100);
        }

        $cart->coupon_code = $coupon->code;
        $cart->discount_cents = $discountCents;
        $cart->save();

        return back()->with('success', 'Cupon aplicat');
    }

    public function removeCoupon()
    {
        $cart = $this->getOrCreateCart();
        $cart->coupon_code = null;
        $cart->discount_cents = 0;
        $cart->save();

        return back()->with('success', 'Cupon eliminat');
    }

    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();
        $cart->coupon_code = null;
        $cart->discount_cents = 0;
        $cart->save();

        return redirect()->route('cart.index')->with('success', 'Coș golit');
    }

    private function getOrCreateCart()
    {
        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }
}
