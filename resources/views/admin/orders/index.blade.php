@extends('layouts.app')

@section('content')
<div style="max-width: 1400px; margin: 0 auto; padding: 30px;">
    <h2>Comenzi</h2>
    
    <table style="width: 100%; background: white; border-collapse: collapse; margin-top: 20px;">
        <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px; text-align: left;">ID</th>
                <th style="padding: 12px; text-align: left;">Client</th>
                <th style="padding: 12px; text-align: left;">Email</th>
                <th style="padding: 12px; text-align: right;">Total</th>
                <th style="padding: 12px; text-align: center;">Metodă</th>
                <th style="padding: 12px; text-align: center;">Status</th>
                <th style="padding: 12px; text-align: left;">Data</th>
                <th style="padding: 12px; text-align: right;">Acțiuni</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;">#{{ $order->id }}</td>
                    <td style="padding: 12px;">{{ $order->customer_name }}</td>
                    <td style="padding: 12px;">{{ $order->customer_email }}</td>
                    <td style="padding: 12px; text-align: right;">{{ number_format($order->total_cents / 100, 2) }} RON</td>
                    <td style="padding: 12px; text-align: center;">{{ $order->payment_method }}</td>
                    <td style="padding: 12px; text-align: center;">
                        <span style="padding: 4px 8px; border-radius: 4px; background: 
                            @if($order->status === 'paid') #d4edda
                            @elseif($order->status === 'pending') #fff3cd
                            @else #f8d7da
                            @endif; color: 
                            @if($order->status === 'paid') #155724
                            @elseif($order->status === 'pending') #856404
                            @else #721c24
                            @endif;">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td style="padding: 12px;">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                    <td style="padding: 12px; text-align: right;">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn" style="padding: 5px 10px; font-size: 12px;">Vezi</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 20px; text-align: center;">Nu există comenzi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div style="margin-top: 20px;">
        {{ $orders->links() }}
    </div>
</div>
@endsection
