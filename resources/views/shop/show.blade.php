@extends('layouts.shop')

@section('title', $product->title . ' - Brodero')

@push('styles')
<style>
.product-container{max-width:1200px;margin:0 auto;padding:80px 20px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start}.product-image-main{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1)}.product-info h1{font-size:2.5rem;color:var(--fg);margin-bottom:20px}.product-price{font-size:2rem;color:var(--accent);font-weight:600;margin-bottom:20px}.product-description{line-height:1.8;color:var(--fg);margin-bottom:30px;font-size:1.1rem}.quantity-selector{display:flex;align-items:center;gap:15px;margin-bottom:30px}.quantity-selector label{font-weight:500}.quantity-selector input{width:80px;padding:10px;border:2px solid var(--accent);border-radius:4px;text-align:center;font-size:1.1rem}.product-actions{display:flex;gap:15px;flex-wrap:wrap}.btn-primary{background:var(--accent);color:white;padding:15px 40px;border:none;border-radius:4px;font-size:1.1rem;cursor:pointer;transition:background .3s}.btn-primary:hover{background:var(--accent-dark)}.btn-secondary{background:white;color:var(--accent);padding:15px 40px;border:2px solid var(--accent);border-radius:4px;font-size:1.1rem;cursor:pointer;text-decoration:none;display:inline-block}.btn-secondary:hover{background:var(--accent);color:white}.product-features{background:#f8f9fa;padding:20px;border-radius:8px;margin-top:30px}.product-features h3{margin-bottom:15px}.product-features ul{list-style:none;padding:0}.product-features li{padding:8px 0;border-bottom:1px solid #dee2e6}.product-features li:last-child{border-bottom:none}.product-features li::before{content:"✓ ";color:var(--accent);font-weight:bold;margin-right:10px}@media (max-width:768px){.product-container{grid-template-columns:1fr;gap:40px;padding:40px 20px}.product-info h1{font-size:2rem}.product-price{font-size:1.6rem}}
</style>
@endpush

@section('content')
<div class="product-container">
<div class="product-gallery">
@if($product->image_url)
<img src="{{ $product->image_url }}" alt="{{ $product->title }}" class="product-image-main">
@else
<img src="{{ asset('placeholder.png') }}" alt="{{ $product->title }}" class="product-image-main" style="opacity:.3">
@endif
</div>
<div class="product-info">
<h1>{{ $product->title }}</h1>
<div class="product-price">{{ number_format($product->price_cents / 100, 2) }} RON</div>
@if($product->description)
<div class="product-description">{!! nl2br(e($product->description)) !!}</div>
@endif
@auth
<form method="POST" action="{{ route('cart.add') }}">
@csrf
<input type="hidden" name="product_id" value="{{ $product->id }}">
<div class="quantity-selector">
<label>Cantitate:</label>
<input type="number" name="quantity" value="1" min="1" required>
</div>
<div class="product-actions">
<button type="submit" class="btn-primary">Adaugă în coș</button>
<a href="{{ route('shop.index') }}" class="btn-secondary">Înapoi la magazin</a>
</div>
</form>
@else
<div class="product-actions">
<a href="{{ route('login') }}" class="btn-primary" style="text-decoration:none;display:inline-block">Autentifică-te pentru a cumpăra</a>
<a href="{{ route('shop.index') }}" class="btn-secondary">Înapoi la magazin</a>
</div>
@endauth
<div class="product-features">
<h3>Detalii produs</h3>
<ul>
<li>Format digital - descărcare instant</li>
<li>Fișiere broderie incluse</li>
@if($product->category)
<li>Categorie: {{ $product->category->name }}</li>
@endif
<li>Suport tehnic disponibil</li>
</ul>
</div>
</div>
</div>
@endsection
