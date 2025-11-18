<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Produse - Admin</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="logo">BRODERO Admin</div>
            <nav class="nav">
                <a href="/admin">📊 Dashboard</a>
                <a href="#" onclick="loadPage('pages')">📄 Pagini</a>
                <a href="{{ route('admin.products.index') }}" class="active">🛍️ Produse</a>
                <a href="#" onclick="loadPage('categories')">📁 Categorii</a>
                <a href="#" onclick="loadPage('media')">🖼️ Media</a>
                <a href="#" onclick="loadPage('coupons')">🎟️ Cupoane</a>
                <a href="#" onclick="loadPage('settings')">⚙️ Setări</a>
                <a href="#" onclick="loadPage('customers')">👥 Clienți</a>
                <a href="#" onclick="loadPage('orders')">📦 Comenzi</a>
                <a href="#" onclick="loadPage('newsletter')">📧 Newsletter</a>
                <form method="POST" action="{{ route('logout') }}" style="margin-top: 20px;">
                    @csrf
                    <button type="submit" style="background:none;border:none;color:#fff;cursor:pointer;padding:12px 20px;width:100%;text-align:left;">🚪 Logout</button>
                </form>
            </nav>
        </aside>
        
        <main class="content">
            <div class="header">
                <h1>Gestionare Produse</h1>
                <a href="{{ route('admin.products.create') }}" class="btn">+ Produs Nou</a>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            <div class="card">
                @if($products->isEmpty())
                    <p>Nu există produse. Creează primul produs!</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Imagine</th>
                                <th>Titlu</th>
                                <th>Categorie</th>
                                <th>Preț</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->title }}" style="width:50px;height:50px;object-fit:cover;border-radius:4px">
                                        @else
                                            <div style="width:50px;height:50px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:20px">📦</div>
                                        @endif
                                    </td>
                                    <td>{{ $product->title }}</td>
                                    <td>{{ $product->category->name ?? '-' }}</td>
                                    <td>{{ number_format($product->price_cents / 100, 2) }} RON</td>
                                    <td>{{ $product->is_published ? '✅ Publicat' : '⚠️ Draft' }}</td>
                                    <td>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm">Editează</a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Sigur ștergi acest produs?')">Șterge</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div style="margin-top:20px">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
    
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>
