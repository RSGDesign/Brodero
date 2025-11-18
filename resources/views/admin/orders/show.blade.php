@extends('layouts.app')

@section('content')
<div style="max-width: 900px; margin: 0 auto; padding: 30px;">
    <h2>Detalii comandă #{{ $order->id }}</h2>
    
    <div style="background: white; padding: 30px; border-radius: 8px; margin-top: 20px;">
        <h3>Informații client</h3>
        <p><strong>Nume:</strong> {{ $order->customer_name }}</p>
        <p><strong>Email:</strong> {{ $order->customer_email }}</p>
        <p><strong>Telefon:</strong> {{ $order->customer_phone ?? '-' }}</p>
        @if($order->notes)
            <p><strong>Observații:</strong> {{ $order->notes }}</p>
        @endif
        
        <hr style="margin: 20px 0;">
        
        <h3>Produse</h3>
        <table style="width: 100%; margin-top: 15px;">
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product->title }} × {{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format(($item->price_cents_snapshot * $item->quantity) / 100, 2) }} RON</td>
                </tr>
            @endforeach
            <tr style="border-top: 2px solid #ddd; font-weight: bold;">
                <td style="padding-top: 10px;">Total</td>
                <td style="text-align: right; padding-top: 10px;">{{ number_format($order->total_cents / 100, 2) }} RON</td>
            </tr>
        </table>
        
        <hr style="margin: 20px 0;">
        
        <h3>Detalii plată</h3>
        <p><strong>Metodă:</strong> {{ ucfirst($order->payment_method) }}</p>
        <p><strong>Status:</strong> 
            <span style="padding: 4px 8px; border-radius: 4px; background: 
                @if($order->status === 'paid') #d4edda
                @elseif($order->status === 'pending') #fff3cd
                @else #f8d7da
                @endif;">
                {{ ucfirst($order->status) }}
            </span>
        </p>
        <p><strong>Data:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
        
        <form method="POST" action="{{ route('admin.orders.status', $order) }}" style="margin-top: 20px;">
            @csrf
            @method('PATCH')
            <label><strong>Schimbă status:</strong></label>
            <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin: 0 10px;">
                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn">Actualizează</button>
        </form>
    </div>
    
    <a href="{{ route('admin.orders.index') }}" style="display: inline-block; margin-top: 20px; color: #003366;">← Înapoi la comenzi</a>
</div>
@endsection
