@extends('layouts.app')

@section('content')
<div style="max-width: 700px; margin: 0 auto; padding: 30px;">
    <h2>{{ isset($coupon) ? 'Editează cupon' : 'Adaugă cupon' }}</h2>
    
    <form method="POST" action="{{ isset($coupon) ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" style="background: white; padding: 30px; border-radius: 8px; margin-top: 20px;">
        @csrf
        @if(isset($coupon))
            @method('PATCH')
        @endif
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Cod cupon *</strong></label>
            <input type="text" name="code" value="{{ old('code', $coupon->code ?? '') }}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Tip *</strong></label>
            <select name="type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="percent" {{ old('type', $coupon->type ?? '') === 'percent' ? 'selected' : '' }}>Procent (%)</option>
                <option value="fixed" {{ old('type', $coupon->type ?? '') === 'fixed' ? 'selected' : '' }}>Fix (RON)</option>
            </select>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Valoare *</strong></label>
            <input type="number" step="0.01" name="value" value="{{ old('value', $coupon->value ?? '') }}" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Data expirare</strong></label>
            <input type="date" name="expires_at" value="{{ old('expires_at', isset($coupon) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Utilizări maxime (0 = nelimitat)</strong></label>
            <input type="number" name="max_uses" value="{{ old('max_uses', $coupon->max_uses ?? 0) }}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;"><strong>Valoare minimă comandă (RON)</strong></label>
            <input type="number" step="0.01" name="min_order_value" value="{{ old('min_order_value', $coupon->min_order_value ?? 0) }}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" name="active" value="1" {{ old('active', $coupon->active ?? true) ? 'checked' : '' }}>
                <strong>Activ</strong>
            </label>
        </div>
        
        <button type="submit" class="btn btn-success">{{ isset($coupon) ? 'Actualizează' : 'Salvează' }}</button>
        <a href="{{ route('admin.coupons.index') }}" class="btn">Anulează</a>
    </form>
</div>
@endsection
