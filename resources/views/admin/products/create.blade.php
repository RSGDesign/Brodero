<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Adaugă Produs - Admin</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="logo">BRODERO Admin</div>
            <nav class="nav">
                <a href="/admin">📊 Dashboard</a>
                <a href="#" onclick="loadPage('pages')">📄 Pagini</a>
                <a href="#" onclick="loadPage('products')" class="active">🛍️ Produse</a>
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
                <h1>Adaugă Produs Nou</h1>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">← Înapoi la listă</a>
            </div>
            
            <div class="card">
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label>Titlu *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required>
                        @error('title')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Descriere</label>
                        <textarea name="description" rows="6">{{ old('description') }}</textarea>
                        @error('description')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Categorie</label>
                        <select name="category_id">
                            <option value="">Fără categorie</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Preț (RON) *</label>
                        <input type="number" step="0.01" name="price_ron" value="{{ old('price_ron', '0.00') }}" required>
                        <small style="color:#6c757d">Prețul va fi convertit automat în cenți pentru stocare</small>
                        @error('price_cents')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>URL Imagine</label>
                        <input type="url" name="image_url" value="{{ old('image_url') }}">
                        <small style="color:#6c757d">URL-ul imaginii principale a produsului</small>
                        @error('image_url')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }}>
                            Publicat (vizibil în shop)
                        </label>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn">Salvează Produsul</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Anulează</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>
