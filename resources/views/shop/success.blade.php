@extends('layouts.shop')

@section('title', 'Comandă finalizată - Brodero')

@push('styles')
<style>
@keyframes scaleIn{0%{transform:scale(0);opacity:0}50%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}@keyframes fadeIn{0%{opacity:0;transform:translateY(20px)}100%{opacity:1;transform:translateY(0)}}#success{padding:80px 0;min-height:60vh;display:flex;align-items:center;justify-content:center}.success-container{background:white;border:1px solid var(--border);border-radius:12px;padding:60px 40px;max-width:600px;text-align:center}.success-icon{width:80px;height:80px;background:#28a745;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 30px;font-size:48px;animation:scaleIn .6s ease-out}.success-title{font-size:28px;color:#28a745;margin-bottom:15px;animation:fadeIn .8s ease-out .2s both}.success-message{font-size:16px;color:var(--muted);margin-bottom:30px;animation:fadeIn .8s ease-out .4s both}.order-details{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:20px;margin:30px 0;text-align:left;animation:fadeIn .8s ease-out .6s both}.order-details h3{font-size:18px;margin-bottom:15px}.order-detail-row{display:flex;justify-content:space-between;margin-bottom:10px;font-size:14px}.order-detail-row strong{color:var(--fg)}.payment-info{background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:15px;margin:20px 0;font-size:14px;animation:fadeIn .8s ease-out .8s both}.success-actions{display:flex;gap:15px;justify-content:center;margin-top:30px;animation:fadeIn .8s ease-out 1s both}.success-actions .btn{padding:12px 24px}
</style>
@endpush

@section('content')
<section id="success">
<div class="container">
<div class="success-container">
<div class="success-icon">✓</div>
<h1 class="success-title">Comanda a fost plasată cu succes!</h1>
<p class="success-message">Mulțumim pentru comandă. Un email de confirmare a fost trimis.</p>
@if($order)
<div class="order-details">
<h3>Detalii comandă</h3>
<div class="order-detail-row"><span>Număr comandă:</span><strong>#{{ $order->id }}</strong></div>
<div class="order-detail-row"><span>Email:</span><strong>{{ $order->customer_email }}</strong></div>
<div class="order-detail-row"><span>Total:</span><strong>{{ number_format($order->total_cents / 100, 2) }} RON</strong></div>
<div class="order-detail-row"><span>Metodă plată:</span><strong>{{ $order->payment_method === 'card' ? 'Card bancar' : 'Transfer bancar' }}</strong></div>
</div>
@if($order->payment_method === 'free')
<div class="payment-info" style="background:#d4edda;border-color:#28a745;color:#155724">
<p><strong>Comandă gratuită!</strong></p>
<p>Comanda a fost procesată gratuit datorită reducerii de 100%.</p>
</div>
@elseif($order->payment_method === 'transfer')
<div class="payment-info">
<p><strong>Instrucțiuni transfer bancar:</strong></p>
<p>Cont: RO12 BANK 1234 5678 9012 3456</p>
<p>Titular: Brodero SRL</p>
<p>Menționați: Comanda #{{ $order->id }}</p>
<p style="margin-top:10px">Veți primi fișierele după confirmarea plății.</p>
</div>
@else
<div class="payment-info" style="background:#d4edda;border-color:#28a745;color:#155724">
<p>Plata a fost procesată cu succes prin Stripe.</p>
<p>Verifică emailul pentru linkurile de descărcare.</p>
</div>
@endif
@endif
<div class="success-actions">
@if($order && $order->payment_method !== 'transfer')
<a href="#" class="btn">Vezi descărcări</a>
@endif
<a href="{{ route('shop.index') }}" class="btn" style="background:var(--border);color:var(--fg)">Continuă cumpărăturile</a>
</div>
</div>
</div>
</section>
@endsection
