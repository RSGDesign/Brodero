@extends('layouts.shop')

@section('title', 'Coș - Brodero')

@push('styles')
<style>
#cart{padding:80px 0 40px}.cart-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px}.cart-header h1{font-size:32px}.cart-header .clear-btn{background:var(--border);color:var(--fg);padding:10px 20px;border:none;border-radius:4px;cursor:pointer}.cart-grid{display:grid;gap:20px;margin-bottom:30px}.cart-item{background:white;border:1px solid var(--border);border-radius:8px;padding:20px;display:grid;grid-template-columns:100px 1fr auto auto;gap:20px;align-items:center}.cart-item img{width:100px;height:100px;object-fit:cover;border-radius:6px}.cart-item-details h3{font-size:16px;margin-bottom:8px}.cart-item-details .price{color:var(--muted);font-size:14px}.qty-controls{display:flex;align-items:center;gap:8px}.qty-controls button{width:32px;height:32px;border:1px solid var(--border);background:white;border-radius:4px;cursor:pointer;font-size:18px}.qty-controls input{width:50px;text-align:center;border:1px solid var(--border);border-radius:4px;padding:4px}.cart-item-total{font-size:18px;font-weight:bold;color:var(--accent)}.remove-btn{background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:20px}.summary-section{background:white;border:1px solid var(--border);border-radius:8px;padding:20px;max-width:400px;margin-left:auto}.summary-row{display:flex;justify-content:space-between;margin-bottom:12px}.summary-row.total{font-size:24px;font-weight:bold;color:var(--accent);border-top:2px solid var(--border);padding-top:12px;margin-top:12px}.coupon-section{margin:20px 0;padding:15px;background:var(--bg);border-radius:6px}.coupon-section input{width:100%;padding:8px;border:1px solid var(--border);border-radius:4px;margin-bottom:8px}.coupon-applied{display:flex;justify-content:space-between;align-items:center;background:#d4edda;color:#155724;padding:10px;border-radius:4px}.empty-cart{text-align:center;padding:60px 20px}.empty-cart svg{width:80px;height:80px;color:var(--muted);margin-bottom:20px}
</style>
@endpush

@section('content')
<section id="cart">
<div class="container">
@if($cart->items->isEmpty())
<div class="empty-cart">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
<h2 style="margin-bottom:20px">Coșul este gol</h2>
<p style="color:var(--muted);margin-bottom:30px">Adaugă produse pentru a continua</p>
<a href="{{ route('shop.index') }}" class="btn">Mergi la magazin</a>
</div>
@else
<div class="cart-header">
<h1>Coșul meu</h1>
<form method="POST" action="{{ route('cart.clear') }}" style="display:inline">
@csrf
@method('DELETE')
<button type="submit" class="clear-btn">Golește coșul</button>
</form>
</div>
<div class="cart-grid">
@foreach($cart->items as $item)
<div class="cart-item">
@if($item->product->image_url)
<img src="{{ $item->product->image_url }}" alt="{{ $item->product->title }}">
@else
<img src="{{ asset('placeholder.png') }}" alt="{{ $item->product->title }}" style="opacity:.3">
@endif
<div class="cart-item-details">
<h3>{{ $item->product->title }}</h3>
<p class="price">{{ number_format($item->price_cents_snapshot / 100, 2) }} RON</p>
</div>
<form method="POST" action="{{ route('cart.update', $item) }}" class="qty-controls">
@csrf
@method('PATCH')
<button type="button" onclick="this.nextElementSibling.stepDown();this.closest('form').submit()">−</button>
<input type="number" name="quantity" value="{{ $item->quantity }}" min="1" readonly>
<button type="button" onclick="this.previousElementSibling.stepUp();this.closest('form').submit()">+</button>
</form>
<div class="cart-item-total">{{ number_format(($item->price_cents_snapshot * $item->quantity) / 100, 2) }} RON</div>
<form method="POST" action="{{ route('cart.remove', $item) }}">
@csrf
@method('DELETE')
<button type="submit" class="remove-btn" title="Șterge">×</button>
</form>
</div>
@endforeach
</div>
<div class="summary-section">
<div class="summary-row"><span>Subtotal:</span><span>{{ number_format($cart->subtotalCents() / 100, 2) }} RON</span></div>
@if($cart->coupon_code)
<div class="coupon-applied">
<span>Cupon {{ $cart->coupon_code }}: -{{ number_format($cart->discount_cents / 100, 2) }} RON</span>
<form method="POST" action="{{ route('cart.coupon.remove') }}" style="display:inline">
@csrf
@method('DELETE')
<button type="submit" style="background:transparent;border:none;color:#721c24;cursor:pointer;font-size:18px">×</button>
</form>
</div>
@else
<div class="coupon-section">
<form method="POST" action="{{ route('cart.coupon.apply') }}">
@csrf
<input type="text" name="code" placeholder="Cod cupon" required>
<button type="submit" class="btn" style="width:100%">Aplică cupon</button>
</form>
</div>
@endif
<div class="summary-row total"><span>Total:</span><span>{{ number_format($cart->totalCents() / 100, 2) }} RON</span></div>
<a href="{{ route('checkout.show') }}" class="btn" style="width:100%;text-align:center;margin-top:20px;display:block;text-decoration:none">Finalizează comanda</a>
</div>
@endif
</div>
</section>
@endsection
