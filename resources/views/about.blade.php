@extends('layouts.shop')

@section('title', 'Despre Noi - Brodero')

@push('styles')
<style>
.about-hero{padding:80px 0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;text-align:center}.about-hero h1{font-size:3rem;margin-bottom:20px}.about-hero p{font-size:1.2rem;max-width:700px;margin:0 auto}.about-content{padding:80px 0}.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;margin-bottom:80px}.about-text h2{font-size:2.2rem;color:var(--accent);margin-bottom:20px}.about-text p{line-height:1.9;color:var(--fg);margin-bottom:15px;font-size:1.1rem}.about-image{border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.1)}.about-image img{width:100%;height:400px;object-fit:cover}.values{background:var(--light-bg);padding:80px 0}.values h2{text-align:center;font-size:2.5rem;color:var(--accent);margin-bottom:50px}.values-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:40px}.value-card{background:white;padding:30px;border-radius:8px;text-align:center;border:1px solid var(--border)}.value-card h3{font-size:1.5rem;color:var(--accent);margin-bottom:15px}.value-card p{color:var(--muted);line-height:1.8}@media (max-width:768px){.about-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="about-hero">
<div class="container">
<h1>Despre Brodero</h1>
<p>Pasiune, precizie și creativitate în fiecare design de broderie</p>
</div>
</section>

<section class="about-content">
<div class="container">
<div class="about-grid">
<div class="about-text">
<h2>Povestea noastră</h2>
<p>Brodero a pornit din pasiunea pentru broderie și dorința de a oferi creatorilor acces la designuri premium de înaltă calitate.</p>
<p>De-a lungul timpului, am crescut împreună cu comunitatea noastră, dezvoltând o platformă care combină tehnologia modernă cu arta tradițională a broderiei.</p>
<p>Astăzi, suntem mândri să oferim sute de designuri unice, create cu grijă și precizie pentru a inspira și susține proiectele tale creative.</p>
</div>
<div class="about-image">
<img src="{{ asset('placeholder.png') }}" alt="Brodero Team" style="opacity:.5">
</div>
</div>

<div class="about-grid" style="flex-direction:row-reverse">
<div class="about-text">
<h2>Misiunea noastră</h2>
<p>Credem că fiecare creator merită acces la instrumente și resurse de calitate pentru a-și transforma ideile în realitate.</p>
<p>De aceea, ne dedicăm să oferim designuri de broderie premium, suport tehnic excelent și o experiență de cumpărare simplă și sigură.</p>
<p>Inspirăm și susținem comunitatea de creatori să atingă noi niveluri de excelență în arta broderiei.</p>
</div>
<div class="about-image">
<img src="{{ asset('placeholder.png') }}" alt="Misiune" style="opacity:.5">
</div>
</div>
</div>
</section>

<section class="values">
<div class="container">
<h2>Valorile noastre</h2>
<div class="values-grid">
<div class="value-card">
<h3>🎯 Calitate</h3>
<p>Fiecare design este testat și optimizat pentru cele mai bune rezultate în producție.</p>
</div>
<div class="value-card">
<h3>🤝 Comunitate</h3>
<p>Ascultăm și răspundem nevoilor comunității noastre de creatori.</p>
</div>
<div class="value-card">
<h3>⚡ Inovație</h3>
<p>Explorăm constant noi tehnici și stiluri pentru a-ți oferi designuri fresh și relevante.</p>
</div>
<div class="value-card">
<h3>💙 Integritate</h3>
<p>Transparență, onestitate și respect în fiecare interacțiune cu clienții noștri.</p>
</div>
</div>
</div>
</section>

<section style="padding:80px 0;text-align:center;background:white">
<div class="container">
<h2 style="font-size:2.5rem;color:var(--accent);margin-bottom:20px">Hai în echipă!</h2>
<p style="font-size:1.2rem;color:var(--muted);margin-bottom:40px;max-width:700px;margin-left:auto;margin-right:auto">Descoperă colecția noastră și începe să creezi lucrări unice astăzi.</p>
<a href="{{ route('shop.index') }}" class="btn" style="padding:15px 40px;font-size:1.1rem">Explorează magazinul</a>
</div>
</section>
@endsection
