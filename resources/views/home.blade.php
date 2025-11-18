@extends('layouts.shop')

@section('title', 'Brodero — Modele broderie')

@section('content')
<section class="hero container">
<div>
<h1>Modele moderne de broderie</h1>
<p>Brodero oferă designuri de broderie create cu pasiune și precizie. Inspirăm creatori din întreaga lume să transforme fiecare idee într-o lucrare unică.</p>
<p><a class="btn" href="{{ route('shop.index') }}">Vezi colecția</a></p>
</div>
<div>
<img src="{{ asset('hero.jpg') }}" alt="Modele broderie" style="width:400px;max-width:100%;border-radius:8px" onerror="this.src='{{ asset('placeholder.svg') }}'">
</div>
</section>

<section class="section container" style="text-align:center">
<h2 style="margin-bottom:20px">Cele mai noi modele</h2>
<div id="featuredProducts" class="grid">
@foreach($products as $product)
<div class="card">
@if($product->image_url)
<img src="{{ $product->image_url }}" alt="{{ $product->title }}">
@else
<img src="{{ asset('placeholder.svg') }}" alt="{{ $product->title }}">
@endif
<div class="card-body">
<h3>{{ $product->title }}</h3>
<p style="font-size:1.2rem;font-weight:600;color:var(--accent);margin:10px 0">{{ number_format($product->price_cents / 100, 2) }} RON</p>
<a href="{{ route('shop.show', $product) }}" class="btn">Vezi detalii</a>
</div>
</div>
@endforeach
</div>
</section>
@endsection
