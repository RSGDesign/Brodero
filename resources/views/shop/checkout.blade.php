@extends('layouts.shop')

@section('title', 'Checkout - Brodero')

@push('styles')
<style>
#checkout{padding:80px 0 40px}.checkout-grid{display:grid;grid-template-columns:1fr 400px;gap:40px;align-items:start}.checkout-form{background:white;border:1px solid var(--border);border-radius:8px;padding:30px}.checkout-form h2{font-size:22px;margin-bottom:20px}.form-row{margin-bottom:20px}.form-row label{display:block;margin-bottom:8px;font-weight:500}.form-row input,.form-row textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:4px}.form-row textarea{resize:vertical}.payment-methods{display:grid;gap:15px;margin:20px 0}.payment-card{border:2px solid var(--border);border-radius:8px;padding:15px;cursor:pointer;transition:all .2s}.payment-card:hover{border-color:var(--accent);background:rgba(45,106,255,.05)}.payment-card input{margin-right:10px}.payment-card label{cursor:pointer;font-weight:500}.payment-card.selected{border-color:var(--accent);background:rgba(45,106,255,.1)}.bank-info{margin-top:15px;padding:15px;background:#fff3cd;border:1px solid #ffc107;border-radius:6px;font-size:14px}.bank-info p{margin:5px 0}.order-summary{background:white;border:1px solid var(--border);border-radius:8px;padding:20px;position:sticky;top:100px}.order-summary h2{font-size:20px;margin-bottom:20px}.summary-items{margin-bottom:20px}.summary-item{display:flex;justify-content:space-between;margin-bottom:12px;font-size:14px}.summary-divider{border-top:1px solid var(--border);margin:15px 0}.summary-total{display:flex;justify-content:space-between;font-size:22px;font-weight:bold;color:var(--accent)}@media (max-width:992px){.checkout-grid{grid-template-columns:1fr}.order-summary{position:static}}
</style>
@endpush

@section('content')
<section id="checkout">
<div class="container">
<h1 style="font-size:32px;margin-bottom:30px;text-align:center">Finalizare comandă</h1>
<div class="checkout-grid">
<div class="checkout-form">
<form method="POST" action="{{ route('checkout.process') }}" id="checkoutForm">
@csrf
<h2>Informații contact</h2>
<div class="form-row">
<label>Nume complet *</label>
<input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required>
</div>
<div class="form-row">
<label>Email *</label>
<input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" required>
</div>
<div class="form-row">
<label>Telefon</label>
<input type="text" name="customer_phone" value="{{ old('customer_phone') }}">
</div>
<div class="form-row">
<label>Observații</label>
<textarea name="notes" rows="4">{{ old('notes') }}</textarea>
</div>
<h2 style="margin-top:30px">Metodă de plată</h2>
<div class="payment-methods">
<div class="payment-card" onclick="this.querySelector('input').checked=true;updatePaymentSelection()">
<input type="radio" name="payment_method" value="card" id="payCard" required>
<label for="payCard">Card bancar (Stripe)</label>
</div>
<div class="payment-card" onclick="this.querySelector('input').checked=true;updatePaymentSelection()">
<input type="radio" name="payment_method" value="transfer" id="payTransfer">
<label for="payTransfer">Transfer bancar</label>
</div>
</div>
<div class="bank-info" id="bankInfo" style="display:none">
<p><strong>Detalii transfer:</strong></p>
<p>Cont: RO12 BANK 1234 5678 9012 3456</p>
<p>Titular: Brodero SRL</p>
<p>Banca: Exemplu Bank</p>
<p>Menționați numărul comenzii în detalii.</p>
</div>
<button type="submit" class="btn" style="width:100%;margin-top:30px;padding:15px;font-size:18px">Plasează comanda</button>
</form>
<a href="{{ route('cart.index') }}" style="display:block;text-align:center;margin-top:15px;color:var(--accent)">← Înapoi la coș</a>
</div>
<div class="order-summary">
<h2>Rezumat comandă</h2>
<div class="summary-items">
@foreach($cart->items as $item)
<div class="summary-item">
<span>{{ $item->product->title }} × {{ $item->quantity }}</span>
<span>{{ number_format(($item->price_cents_snapshot * $item->quantity) / 100, 2) }} RON</span>
</div>
@endforeach
</div>
<div class="summary-divider"></div>
<div class="summary-item"><span>Subtotal:</span><span>{{ number_format($cart->subtotalCents() / 100, 2) }} RON</span></div>
@if($cart->discount_cents > 0)
<div class="summary-item" style="color:#28a745"><span>Discount ({{ $cart->coupon_code }}):</span><span>-{{ number_format($cart->discount_cents / 100, 2) }} RON</span></div>
@endif
<div class="summary-divider"></div>
<div class="summary-total"><span>Total:</span><span>{{ number_format($cart->totalCents() / 100, 2) }} RON</span></div>
</div>
</div>
</div>
</section>
@endsection

@push('scripts')
<script>
function updatePaymentSelection(){document.querySelectorAll('.payment-card').forEach(c=>c.classList.remove('selected'));const selected=document.querySelector('input[name="payment_method"]:checked');if(selected){selected.closest('.payment-card').classList.add('selected');document.getElementById('bankInfo').style.display=selected.value==='transfer'?'block':'none'}}document.querySelectorAll('input[name="payment_method"]').forEach(r=>r.addEventListener('change',updatePaymentSelection));
</script>
@endpush
