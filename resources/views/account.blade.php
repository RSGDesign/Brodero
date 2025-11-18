<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Contul meu — Brodero</title>
  <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
  <style>
    .account-container { max-width:1100px; margin:60px auto; padding:0 20px; }
    .account-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:40px; }
    .account-header h1 { margin:0; }
    .btn-logout { background:#dc3545; color:#fff; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; }
    .btn-logout:hover { background:#c82333; }
    .tabs { display:flex; gap:0; border-bottom:2px solid #dee2e6; margin-bottom:30px; }
    .tab { padding:12px 24px; background:transparent; border:none; cursor:pointer; font-size:1rem; border-bottom:3px solid transparent; transition:all .2s; }
    .tab.active { border-bottom-color:var(--accent); color:var(--accent); font-weight:600; }
    .tab:hover { background:#f8f9fa; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .orders-list { margin-top:20px; }
    .order-card { background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:20px; margin-bottom:20px; }
    .order-header { display:flex; justify-content:space-between; margin-bottom:12px; }
    .order-id { font-weight:600; font-size:1.1rem; }
    .order-status { padding:4px 12px; border-radius:20px; font-size:0.85rem; text-transform:uppercase; }
    .order-status.pending { background:#fff3cd; color:#856404; }
    .order-status.paid { background:#d4edda; color:#155724; }
    .order-status.failed { background:#f8d7da; color:#721c24; }
    .order-status.cancelled { background:#e2e3e5; color:#6c757d; }
    .order-details { color:#666; font-size:0.95rem; }
    .empty-state { text-align:center; padding:60px 20px; color:#666; }
  </style>
</head>
<body>
  @include('components.header')
  <main>
    <div class="account-container">
      <div class="account-header">
        <h1>Contul meu</h1>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
          @csrf
          <button type="submit" class="btn-logout">Deconectare</button>
        </form>
      </div>
      <div class="tabs">
        <button class="tab active" onclick="switchTab('orders')">Comenzi</button>
        <button class="tab" onclick="switchTab('profile')">Profil</button>
      </div>
      <div class="tab-content active" id="orders-tab">
        @php
          $orders = auth()->user()->orders()->with('items.product')->orderBy('created_at', 'desc')->get();
        @endphp
        @if($orders->isEmpty())
          <div class="empty-state">
            <h3>Nu ai comenzi încă</h3>
            <p><a href="{{ route('shop.index') }}" class="btn">Explorează magazinul</a></p>
          </div>
        @else
          <div class="orders-list">
            @foreach($orders as $order)
            <div class="order-card">
              <div class="order-header">
                <div class="order-id">Comanda #{{ $order->id }}</div>
                <div class="order-status {{ $order->status }}">{{ $order->status }}</div>
              </div>
              <div class="order-details">
                <ul style="list-style:none; padding:0; margin:10px 0;">
                  @foreach($order->items as $item)
                    <li>{{ $item->product->title }} × {{ $item->quantity }} - {{ number_format(($item->price_cents_snapshot * $item->quantity) / 100, 2) }} RON</li>
                  @endforeach
                </ul>
                <strong>Total: {{ number_format($order->total_cents / 100, 2) }} RON</strong><br>
                Dată: {{ $order->created_at->format('d.m.Y H:i') }}<br>
                Metodă: {{ $order->payment_method === 'transfer' ? 'Transfer bancar' : 'Card' }}
              </div>
            </div>
            @endforeach
          </div>
        @endif
      </div>
      <div class="tab-content" id="profile-tab">
        <div class="order-card">
          <h2 style="margin-bottom:20px">Informații profil</h2>
          <p><strong>Nume:</strong> {{ auth()->user()->name }}</p>
          <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
          <p><strong>Membru din:</strong> {{ auth()->user()->created_at->format('d.m.Y') }}</p>
        </div>
      </div>
    </div>
  </main>
  @include('components.footer')
  <script>
    function switchTab(tab) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      event.target.classList.add('active');
      document.getElementById(tab + '-tab').classList.add('active');
    }
  </script>
</body>
</html>
