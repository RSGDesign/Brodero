<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Dashboard Admin — Brodero CMS</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="logo">Brodero CMS</div>
      <nav class="nav">
        <a href="#" onclick="loadPage('dashboard')" class="active">📊 Dashboard</a>
        <a href="#" onclick="loadPage('pages')">📄 Pagini</a>
        <a href="#" onclick="loadPage('products')">🛍️ Produse</a>
        <a href="#" onclick="loadPage('categories')">📁 Categorii</a>
        <a href="#" onclick="loadPage('media')">🖼️ Media</a>
        <a href="#" onclick="loadPage('coupons')">🎫 Cupoane</a>
        <a href="#" onclick="loadPage('settings')">⚙️ Setări</a>
        <a href="#" onclick="loadPage('customers')">👥 Clienți</a>
        <a href="#" onclick="loadPage('orders')">📦 Comenzi</a>
        <a href="#" onclick="loadPage('newsletter')">📧 Newsletter</a>
      </nav>
      <div style="margin-top:auto; padding:20px; border-top:1px solid rgba(255,255,255,0.1)">
        <a href="{{ route('home') }}" style="color:#fff; text-decoration:none; display:block; margin-bottom:10px">🌐 Vezi Site</a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" style="background:none; border:none; color:#fff; cursor:pointer; padding:0; font:inherit">🚪 Deconectare</button>
        </form>
      </div>
    </aside>
    <main class="content">
      <div id="app"></div>
    </main>
  </div>
  <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>
