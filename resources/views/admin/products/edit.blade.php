<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editează Produs - Admin</title>
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
                <h1>Editează Produs</h1>
                <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">← Înapoi la listă</a>
            </div>
            
            <div class="card">
                <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    
                    <div class="form-group">
                        <label>Titlu *</label>
                        <input type="text" name="title" value="{{ old('title', $product->title) }}" required>
                        @error('title')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Descriere</label>
                        <textarea name="description" rows="6">{{ old('description', $product->description) }}</textarea>
                        @error('description')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>Categorie</label>
                        <select name="category_id">
                            <option value="">Fără categorie</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Preț (RON) *</label>
                        <input type="number" step="0.01" name="price_ron" value="{{ old('price_ron', number_format($product->price_cents / 100, 2, '.', '')) }}" required>
                        <small style="color:#6c757d">Preț curent: {{ number_format($product->price_cents / 100, 2) }} RON</small>
                        @error('price_ron')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>URL Imagine</label>
                        <input type="url" name="image_url" value="{{ old('image_url', $product->image_url) }}">
                        @if($product->image_url)
                            <div style="margin-top:10px">
                                <img src="{{ $product->image_url }}" alt="Preview" style="max-width:200px;border-radius:4px;border:1px solid #ddd">
                            </div>
                        @endif
                        @error('image_url')<span style="color:red;font-size:13px">{{ $message }}</span>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $product->is_published) ? 'checked' : '' }}>
                            Publicat (vizibil în shop)
                        </label>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn">Actualizează Produsul</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Anulează</a>
                    </div>
                </form>
                
                <!-- Product Files Section -->
                <input type="hidden" id="productId" value="{{ $product->id }}">
                <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e0e0e0;">
                    <h2 style="margin-bottom: 20px;">Fișiere Descărcabile</h2>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <div id="productFilesDrop" style="flex:1;min-height:140px;border:2px dashed #cfdffc;border-radius:8px;display:flex;align-items:center;justify-content:center;padding:18px;cursor:pointer;background:#fbfdff">
                            <input type="file" id="productFilesInput" style="display:none">
                            <div style="text-align:center">
                                <div style="font-size:48px">📎</div>
                                <div style="margin-top:8px"><button type="button" class="btn" id="productFilesBrowse">Selectează Fișiere</button></div>
                                <div id="productFilesDropText" style="margin-top:8px;color:#666">sau trage fișiere aici</div>
                            </div>
                        </div>
                        
                        <div style="flex:1">
                            <div id="productFilesStatus" style="min-height:20px;margin-bottom:8px"></div>
                            <div style="font-weight:600;margin-bottom:8px">Fișiere încărcate</div>
                            <div id="productFilesList" style="max-height:220px;overflow:auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    // Product Files Management
    async function loadProductFiles() {
        const productId = document.getElementById('productId').value;
        if (!productId) {
            document.getElementById('productFilesList').innerHTML = '<p style="color:#888;font-size:14px">Salvează produsul mai întâi.</p>';
            return;
        }
        
        try {
            const res = await fetch(`/admin/products/${productId}/files`);
            if (!res.ok) throw new Error('Nu s-au putut încărca fișierele');
            const files = await res.json();
            
            const container = document.getElementById('productFilesList');
            if (!files.length) {
                container.innerHTML = '<p style="color:#888;font-size:14px">Nu există fișiere încărcate.</p>';
                return;
            }
            
            container.innerHTML = files.map(file => `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px;background:#f5f5f5;border-radius:4px;margin-bottom:8px">
                    <div style="flex:1">
                        <strong>${file.original_name}</strong>
                        <div style="font-size:12px;color:#666">
                            ${(file.filesize / 1024).toFixed(1)} KB • ${new Date(file.created_at).toLocaleDateString('ro-RO')}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteProductFile(${file.id})">🗑️</button>
                </div>
            `).join('');
        } catch (err) {
            document.getElementById('productFilesList').innerHTML = `<p style="color:#e74c3c">Eroare: ${err.message}</p>`;
        }
    }

    async function uploadProductFiles(filesArg) {
        const productId = document.getElementById('productId').value;
        const statusEl = document.getElementById('productFilesStatus');
        
        if (!productId) {
            if (statusEl) statusEl.innerHTML = '<div style="color:#e74c3c">Salvează produsul mai întâi.</div>';
            return;
        }

        let files = filesArg || document.getElementById('productFilesInput').files;
        if (!files || files.length === 0) {
            if (statusEl) statusEl.innerHTML = '<div style="color:#e74c3c">Selectează cel puțin un fișier.</div>';
            return;
        }

        const uploadList = document.getElementById('productFilesList');
        const browseBtn = document.getElementById('productFilesBrowse');
        const input = document.getElementById('productFilesInput');
        
        if (browseBtn) browseBtn.disabled = true;
        if (input) input.disabled = true;

        for (let file of Array.from(files)) {
            const row = document.createElement('div');
            row.style = 'display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px;padding:8px;border-radius:6px;background:#fafafa;border:1px solid #eee';
            row.innerHTML = `
                <div style="flex:1">
                    <div style="font-weight:600">${file.name}</div>
                    <div style="height:8px;background:#eee;border-radius:4px;margin-top:6px;overflow:hidden">
                        <div class="progress-bar" style="width:0%;height:100%;background:#00a0ff;transition:width 0.3s"></div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger cancel-btn">Anulează</button>
            `;
            
            const progressBar = row.querySelector('.progress-bar');
            const cancelBtn = row.querySelector('.cancel-btn');
            if (uploadList) uploadList.prepend(row);

            try {
                await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    const fd = new FormData();
                    fd.append('files', file);
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    xhr.open('POST', `/admin/products/${productId}/files`, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    
                    xhr.upload.onprogress = function(ev) {
                        if (ev.lengthComputable) {
                            const pct = Math.round((ev.loaded / ev.total) * 100);
                            progressBar.style.width = pct + '%';
                        }
                    };
                    
                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            const json = JSON.parse(xhr.responseText || '{}');
                            progressBar.style.width = '100%';
                            progressBar.style.background = '#27ae60';
                            
                            setTimeout(() => {
                                row.remove();
                                loadProductFiles();
                            }, 500);
                            
                            resolve();
                        } else {
                            reject(new Error('Upload failed'));
                        }
                    };
                    
                    xhr.onerror = () => reject(new Error('Network error'));
                    
                    cancelBtn.onclick = () => {
                        xhr.abort();
                        row.remove();
                        reject(new Error('Cancelled'));
                    };
                    
                    xhr.send(fd);
                });
            } catch (err) {
                if (err.message !== 'Cancelled') {
                    progressBar.style.background = '#e74c3c';
                    cancelBtn.textContent = 'Șterge';
                    cancelBtn.onclick = () => row.remove();
                }
            }
        }
        
        if (browseBtn) browseBtn.disabled = false;
        if (input) input.disabled = false;
        if (input) input.value = '';
    }

    async function deleteProductFile(fileId) {
        if (!confirm('Sigur ștergi acest fișier?')) return;
        
        const productId = document.getElementById('productId').value;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        try {
            const res = await fetch(`/admin/products/${productId}/files/${fileId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            
            if (res.ok) {
                loadProductFiles();
            } else {
                alert('Eroare la ștergere');
            }
        } catch (err) {
            alert('Eroare: ' + err.message);
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadProductFiles();
        
        const browseBtn = document.getElementById('productFilesBrowse');
        const input = document.getElementById('productFilesInput');
        const dropZone = document.getElementById('productFilesDrop');
        
        if (browseBtn && input) {
            browseBtn.onclick = () => input.click();
            input.onchange = () => uploadProductFiles();
        }
        
        if (dropZone) {
            dropZone.ondragover = (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#00a0ff';
                dropZone.style.background = '#e6f7ff';
            };
            
            dropZone.ondragleave = () => {
                dropZone.style.borderColor = '#cfdffc';
                dropZone.style.background = '#fbfdff';
            };
            
            dropZone.ondrop = (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#cfdffc';
                dropZone.style.background = '#fbfdff';
                uploadProductFiles(e.dataTransfer.files);
            };
        }
    });
    </script>
</body>
</html>
