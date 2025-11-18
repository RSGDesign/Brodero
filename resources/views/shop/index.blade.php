@extends('layouts.shop')

@section('title', 'Magazin - Brodero')

@push('styles')
<style>
    #shop {padding:80px 0 40px}
    #mainContent {display:grid;grid-template-columns:1fr 280px;gap:30px;align-items:start}
    #productsContainer .grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px}
    #productsContainer .grid>div {background:white;border:1px solid var(--border);border-radius:8px;padding:12px;text-align:center;transition:transform .2s,box-shadow .2s}
    #productsContainer .grid>div:hover {transform:translateY(-4px);box-shadow:0 8px 20px rgba(0,0,0,.15)}
    #productsContainer .grid>div img {width:100%;height:180px;object-fit:cover;border-radius:6px;margin-bottom:10px}
    #productsContainer .grid>div h3 {font-size:15px;margin-bottom:6px;color:var(--fg)}
    #productsContainer .grid>div .price {font-size:18px;font-weight:bold;color:var(--accent);margin:8px 0}
    #filterPanel {background:white;padding:20px;border-radius:8px;border:1px solid var(--border);position:sticky;top:100px}
    #filterPanel h2 {font-size:18px;margin-bottom:16px}
    #filterPanel .form-row {margin-bottom:16px}
    #filterPanel .form-row label {display:block;margin-bottom:6px;font-weight:500;font-size:14px}
    .price-range {display:flex;align-items:center;gap:8px;margin:10px 0}
    .price-range input {width:70px;padding:6px;border:1px solid var(--border);border-radius:4px;text-align:center}
    .slider-container {position:relative;height:6px;background:var(--border);border-radius:3px;margin:20px 0}
    .slider-range {position:absolute;height:100%;background:var(--accent);border-radius:3px}
    .slider-thumb {position:absolute;top:50%;transform:translate(-50%,-50%);width:18px;height:18px;background:var(--accent);border-radius:50%;cursor:pointer;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,.2)}
    .category-checkbox {display:flex;align-items:center;gap:8px;margin-bottom:8px}
    .toolbar {display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding:12px;background:white;border:1px solid var(--border);border-radius:8px}
    .toolbar select {padding:6px 10px;border:1px solid var(--border);border-radius:4px;margin-left:8px}
    .pagination {display:flex;justify-content:center;align-items:center;gap:10px;margin-top:30px}
    .pagination button {padding:8px 14px;border:1px solid var(--border);background:white;border-radius:4px;cursor:pointer}
    .pagination button:disabled {opacity:.5;cursor:not-allowed}
    @media (max-width:768px) {#mainContent {grid-template-columns:1fr} #filterPanel {position:static}}
</style>
@endpush

@section('content')
<section id="shop">
    <div class="container">
        <h1 style="font-size:32px;margin-bottom:30px;text-align:center">Magazin</h1>
        <div id="mainContent">
            <div>
                <div class="toolbar">
                    <div><label>Sortare:</label><select id="sortSelect"><option value="">Implicit</option><option value="name_asc">Nume A-Z</option><option value="name_desc">Nume Z-A</option><option value="price_asc">Preț crescător</option><option value="price_desc">Preț descrescător</option></select></div>
                    <div><label>Pe pagină:</label><select id="pageSizeSelect"><option value="12">12</option><option value="24">24</option><option value="48">48</option></select></div>
                </div>
                <div id="productsContainer">
                    <div class="grid">
                        @forelse($products as $product)
                            <div>
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->title }}">
                                @else
                                    <img src="{{ asset('placeholder.png') }}" alt="{{ $product->title }}" style="opacity:.3">
                                @endif
                                <h3>{{ $product->title }}</h3>
                                <p class="price">{{ number_format($product->price_cents / 100, 2) }} RON</p>
                                <a href="{{ route('shop.show', $product) }}" class="btn">Detalii</a>
                            </div>
                        @empty
                            <p>Niciun produs găsit.</p>
                        @endforelse
                    </div>
                </div>
                <div class="pagination">
                    <button id="prevBtn" {{ $products->currentPage()==1?'disabled':'' }}>‹ Înapoi</button>
                    <span class="page-info">Pagina {{ $products->currentPage() }} din {{ $products->lastPage() }}</span>
                    <button id="nextBtn" {{ $products->currentPage()==$products->lastPage()?'disabled':'' }}>Înainte ›</button>
                </div>
            </div>
            <aside id="filterPanel">
                <h2>Filtre</h2>
                <div class="form-row"><label>Caută</label><input type="text" id="searchInput" placeholder="Caută produse..."></div>
                <div class="form-row">
                    <label>Preț</label>
                    <div class="price-range"><input type="number" id="minPrice" value="0" min="0"><span>-</span><input type="number" id="maxPrice" value="500" min="0"><span>RON</span></div>
                    <div class="slider-container" id="sliderContainer"><div class="slider-range" id="sliderRange"></div><div class="slider-thumb" id="thumbMin"></div><div class="slider-thumb" id="thumbMax"></div></div>
                </div>
                @if(isset($categories) && $categories->count() > 0)
                <div class="form-row">
                    <label>Categorii</label>
                    <div id="categoriesContainer">
                        @foreach($categories as $category)
                            <div class="category-checkbox">
                                <input type="checkbox" class="cat-filter" id="cat{{ $category->id }}" value="{{ $category->id }}">
                                <label for="cat{{ $category->id }}">{{ $category->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
                <button class="btn" onclick="applyFilters()" style="width:100%;margin-top:10px">Aplică Filtre</button>
                <button class="btn" onclick="clearFilters()" style="width:100%;margin-top:10px;background:var(--border);color:var(--fg)">Resetează</button>
            </aside>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
const minInput=document.getElementById('minPrice'),maxInput=document.getElementById('maxPrice'),thumbMin=document.getElementById('thumbMin'),thumbMax=document.getElementById('thumbMax'),sliderRange=document.getElementById('sliderRange'),sliderContainer=document.getElementById('sliderContainer'),PRICE_MIN=0,PRICE_MAX=500;function valueToPct(v){return((v-PRICE_MIN)/(PRICE_MAX-PRICE_MIN))*100}function pctToValue(p){return Math.round((p/100)*(PRICE_MAX-PRICE_MIN)+PRICE_MIN)}function updateSlider(){const minVal=parseFloat(minInput.value)||PRICE_MIN,maxVal=parseFloat(maxInput.value)||PRICE_MAX,minPct=valueToPct(Math.max(PRICE_MIN,minVal)),maxPct=valueToPct(Math.min(PRICE_MAX,maxVal));thumbMin.style.left=minPct+'%';thumbMax.style.left=maxPct+'%';sliderRange.style.left=minPct+'%';sliderRange.style.width=(maxPct-minPct)+'%'}minInput.addEventListener('input',updateSlider);maxInput.addEventListener('input',updateSlider);let activeThumb=null;thumbMin.addEventListener('pointerdown',e=>{activeThumb='min';thumbMin.setPointerCapture(e.pointerId)});thumbMax.addEventListener('pointerdown',e=>{activeThumb='max';thumbMax.setPointerCapture(e.pointerId)});document.addEventListener('pointermove',e=>{if(!activeThumb)return;const rect=sliderContainer.getBoundingClientRect(),pct=Math.max(0,Math.min(100,((e.clientX-rect.left)/rect.width)*100)),val=pctToValue(pct);if(activeThumb==='min')minInput.value=Math.min(val,parseFloat(maxInput.value)-1);else maxInput.value=Math.max(val,parseFloat(minInput.value)+1);updateSlider()});document.addEventListener('pointerup',()=>{activeThumb=null});updateSlider();function applyFilters(){const search=document.getElementById('searchInput').value,minPrice=minInput.value,maxPrice=maxInput.value,categories=Array.from(document.querySelectorAll('.cat-filter:checked')).map(cb=>cb.value),sort=document.getElementById('sortSelect').value,pageSize=document.getElementById('pageSizeSelect').value,params=new URLSearchParams();if(search)params.append('search',search);if(minPrice)params.append('min_price',minPrice);if(maxPrice)params.append('max_price',maxPrice);if(categories.length)params.append('categories',categories.join(','));if(sort)params.append('sort',sort);if(pageSize)params.append('per_page',pageSize);window.location.href='{{ route("shop.index") }}?'+params.toString()}function clearFilters(){window.location.href='{{ route("shop.index") }}'}document.getElementById('sortSelect').addEventListener('change',applyFilters);document.getElementById('pageSizeSelect').addEventListener('change',applyFilters);document.getElementById('searchInput').addEventListener('keypress',e=>{if(e.key==='Enter')applyFilters()});document.getElementById('prevBtn')?.addEventListener('click',()=>{const url=new URL(window.location.href),page=parseInt(url.searchParams.get('page')||1);if(page>1){url.searchParams.set('page',page-1);window.location.href=url.toString()}});document.getElementById('nextBtn')?.addEventListener('click',()=>{const url=new URL(window.location.href),page=parseInt(url.searchParams.get('page')||1);url.searchParams.set('page',page+1);window.location.href=url.toString()});
</script>
@endpush
