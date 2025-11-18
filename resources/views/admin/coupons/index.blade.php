@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Cupoane</h2>
        <a href="{{ route('admin.coupons.create') }}" class="btn btn-success">Adaugă cupon</a>
    </div>
    
    <table style="width: 100%; background: white; border-collapse: collapse;">
        <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px; text-align: left;">Cod</th>
                <th style="padding: 12px; text-align: left;">Tip</th>
                <th style="padding: 12px; text-align: right;">Valoare</th>
                <th style="padding: 12px; text-align: center;">Utilizări</th>
                <th style="padding: 12px; text-align: center;">Activ</th>
                <th style="padding: 12px; text-align: left;">Expiră</th>
                <th style="padding: 12px; text-align: right;">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><strong>{{ $coupon->code }}</strong></td>
                    <td style="padding: 12px;">{{ $coupon->type === 'percent' ? 'Procent' : 'Fix' }}</td>
                    <td style="padding: 12px; text-align: right;">{{ $coupon->value }}{{ $coupon->type === 'percent' ? '%' : ' RON' }}</td>
                    <td style="padding: 12px; text-align: center;">{{ $coupon->uses_count }} / {{ $coupon->max_uses ?: '∞' }}</td>
                    <td style="padding: 12px; text-align: center;">{{ $coupon->active ? '✓' : '✗' }}</td>
                    <td style="padding: 12px;">{{ $coupon->expires_at ? $coupon->expires_at->format('d.m.Y') : '-' }}</td>
                    <td style="padding: 12px; text-align: right;">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Sigur ștergi?')">Șterge</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding: 20px; text-align: center;">Nu există cupoane.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        {{ $coupons->links() }}
    </div>
</div>
@endsection
