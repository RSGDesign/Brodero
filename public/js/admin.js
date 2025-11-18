// Admin Panel JavaScript - Complete Implementation
let currentPage = 'dashboard';
let currentImageInputId = null;

// Get CSRF token
function getCSRFToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// Dashboard
async function renderDashboard() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/stats');
    if (!response.ok) throw new Error('Failed to fetch stats');
    const stats = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Dashboard</h1>
        <p>Bine ai venit în panoul de administrare Brodero</p>
      </div>
      <div class="stats">
        <div class="stat-card"><div class="number">${stats.products || 0}</div><div class="label">Produse</div></div>
        <div class="stat-card"><div class="number">${stats.pages || 0}</div><div class="label">Pagini</div></div>
        <div class="stat-card"><div class="number">${stats.customers || 0}</div><div class="label">Clienți</div></div>
        <div class="stat-card"><div class="number">${stats.orders || 0}</div><div class="label">Comenzi</div></div>
        <div class="stat-card"><div class="number">${stats.newsletter || 0}</div><div class="label">Abonați Newsletter</div></div>
      </div>
    `;
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

// Pages Management
async function renderPages() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/pages');
    if (!response.ok) throw new Error('Failed to fetch pages');
    const pages = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Gestionare Pagini</h1>
        <button class="btn" onclick="showPageEditor()">+ Pagină Nouă</button>
      </div>
      <div class="card">
        ${pages.length === 0 ? '<p>Nu există pagini. Creează prima pagină!</p>' : `
        <table>
          <thead><tr><th>Titlu</th><th>Slug</th><th>Status</th><th>Acțiuni</th></tr></thead>
          <tbody>
            ${pages.map(p => `
              <tr>
                <td>${p.title}</td><td>/${p.slug}</td>
                <td>${p.is_published ? '✅ Publicat' : '⚠️ Draft'}</td>
                <td>
                  <button class="btn btn-sm" onclick="showPageEditor(${p.id})">Editează</button>
                  <button class="btn btn-sm btn-danger" onclick="deletePage(${p.id})">Șterge</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>`}
      </div>
      <div id="pageModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="pageModalTitle">Editare Pagină</h2>
            <button class="modal-close" onclick="closeModal('pageModal')">&times;</button>
          </div>
          <form id="pageForm">
            <input type="hidden" id="pageId">
            <div class="form-group"><label>Titlu</label><input type="text" id="pageTitle" required></div>
            <div class="form-group"><label>Slug (URL)</label><input type="text" id="pageSlug" required></div>
            <div class="form-group"><label>Conținut HTML</label><textarea id="pageContent"></textarea></div>
            <div class="form-group"><label>Meta Description</label><textarea id="pageMetaDesc" rows="3"></textarea></div>
            <div class="form-group"><label><input type="checkbox" id="pagePublished"> Publicat</label></div>
            <div class="actions">
              <button type="submit" class="btn">Salvează</button>
              <button type="button" class="btn btn-secondary" onclick="closeModal('pageModal')">Anulează</button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.getElementById('pageForm').addEventListener('submit', savePage);
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function showPageEditor(id = null) {
  if (!document.getElementById('pageModal')) await renderPages();
  const modal = document.getElementById('pageModal');
  modal.classList.add('active');
  if (id) {
    const page = await fetch(`/admin/api/pages/${id}`).then(r => r.json());
    document.getElementById('pageId').value = page.id;
    document.getElementById('pageTitle').value = page.title;
    document.getElementById('pageSlug').value = page.slug;
    document.getElementById('pageContent').value = page.content || '';
    document.getElementById('pageMetaDesc').value = page.meta_description || '';
    document.getElementById('pagePublished').checked = page.is_published;
    document.getElementById('pageModalTitle').textContent = 'Editare Pagină';
  } else {
    document.getElementById('pageForm').reset();
    document.getElementById('pageId').value = '';
    document.getElementById('pageModalTitle').textContent = 'Pagină Nouă';
    document.getElementById('pagePublished').checked = true;
  }
}

async function savePage(e) {
  e.preventDefault();
  const id = document.getElementById('pageId').value;
  const data = {
    title: document.getElementById('pageTitle').value,
    slug: document.getElementById('pageSlug').value,
    content: document.getElementById('pageContent').value,
    meta_description: document.getElementById('pageMetaDesc').value,
    is_published: document.getElementById('pagePublished').checked ? 1 : 0
  };
  try {
    const response = await fetch(id ? `/admin/api/pages/${id}` : '/admin/api/pages', {
      method: id ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
      body: JSON.stringify(data)
    });
    if (!response.ok) throw new Error(await response.text());
    closeModal('pageModal');
    renderPages();
  } catch (error) {
    alert('Eroare la salvare: ' + error.message);
  }
}

async function deletePage(id) {
  if (!confirm('Sigur ștergi această pagină?')) return;
  await fetch(`/admin/api/pages/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCSRFToken() } });
  renderPages();
}

// Products Management
async function renderProducts() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/products');
    if (!response.ok) throw new Error('Failed to fetch products');
    const products = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Gestionare Produse</h1>
        <button class="btn" onclick="showProductEditor()">+ Produs Nou</button>
      </div>
      <div class="card">
        ${products.length === 0 ? '<p>Nu există produse. Creează primul produs!</p>' : `
        <table>
          <thead><tr><th>Imagine</th><th>Titlu</th><th>Preț</th><th>Acțiuni</th></tr></thead>
          <tbody>
            ${products.map(p => `
              <tr>
                <td><img src="${p.image || '/placeholder.svg'}" style="width:50px;height:50px;object-fit:cover;border-radius:4px"></td>
                <td>${p.title}</td>
                <td>${(p.price_cents/100).toFixed(2)} RON</td>
                <td>
                  <button class="btn btn-sm" onclick="showProductEditor(${p.id})">Editează</button>
                  <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id})">Șterge</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>`}
      </div>
    `;
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function showProductEditor(id = null) {
  window.location.href = id ? `/admin/products/${id}/edit` : '/admin/products/create';
}

async function deleteProduct(id) {
  if (!confirm('Sigur ștergi acest produs?')) return;
  await fetch(`/admin/api/products/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCSRFToken() } });
  renderProducts();
}

// Categories Management
async function renderCategories() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/categories');
    if (!response.ok) throw new Error('Failed to fetch categories');
    const cats = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Categorii</h1>
        <button class="btn" onclick="showCategoryEditor()">+ Categorie Nouă</button>
      </div>
      <div class="card">
        ${cats.length === 0 ? '<p>Nu există categorii.</p>' : `
        <table>
          <thead><tr><th>Nume</th><th>Slug</th><th>Acțiuni</th></tr></thead>
          <tbody>
            ${cats.map(c => `
              <tr>
                <td>${c.name}</td><td>/${c.slug}</td>
                <td>
                  <button class="btn btn-sm" onclick="showCategoryEditor(${c.id})">Editează</button>
                  <button class="btn btn-sm btn-danger" onclick="deleteCategory(${c.id})">Șterge</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>`}
      </div>
      <div id="categoryModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="categoryModalTitle">Editare Categorie</h2>
            <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
          </div>
          <form id="categoryForm">
            <input type="hidden" id="categoryId">
            <div class="form-group"><label>Nume</label><input type="text" id="categoryName" required></div>
            <div class="form-group"><label>Slug</label><input type="text" id="categorySlug" required></div>
            <div class="actions">
              <button type="submit" class="btn">Salvează</button>
              <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">Anulează</button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.getElementById('categoryForm').addEventListener('submit', saveCategory);
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function showCategoryEditor(id = null) {
  if (!document.getElementById('categoryModal')) await renderCategories();
  const modal = document.getElementById('categoryModal');
  modal.classList.add('active');
  if (id) {
    const cat = await fetch(`/admin/api/categories/${id}`).then(r => r.json());
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.name;
    document.getElementById('categorySlug').value = cat.slug;
    document.getElementById('categoryModalTitle').textContent = 'Editare Categorie';
  } else {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModalTitle').textContent = 'Categorie Nouă';
  }
}

async function saveCategory(e) {
  e.preventDefault();
  const id = document.getElementById('categoryId').value;
  const data = {
    name: document.getElementById('categoryName').value,
    slug: document.getElementById('categorySlug').value
  };
  const response = await fetch(id ? `/admin/api/categories/${id}` : '/admin/api/categories', {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
    body: JSON.stringify(data)
  });
  if (!response.ok) { alert('Eroare la salvare'); return; }
  closeModal('categoryModal');
  renderCategories();
}

async function deleteCategory(id) {
  if (!confirm('Sigur ștergi această categorie?')) return;
  await fetch(`/admin/api/categories/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCSRFToken() } });
  renderCategories();
}

// Media Management
async function renderMedia() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/media');
    if (!response.ok) throw new Error('Failed to fetch media');
    const media = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Biblioteca Media</h1>
        <button class="btn" onclick="document.getElementById('mediaUploadInput').click()">+ Upload Imagine</button>
      </div>
      <div class="card">
        ${media.length === 0 ? '<p>Nicio imagine uploadată</p>' : `
          <div class="media-grid">
            ${media.map(m => `
              <div class="media-item">
                <img src="${m.path}" alt="${m.original_name}">
                <div class="name">${m.original_name}</div>
                <button class="btn btn-sm btn-danger" onclick="deleteMedia(${m.id})" style="margin-top:5px;width:100%">Șterge</button>
              </div>
            `).join('')}
          </div>
        `}
      </div>
      <input type="file" id="mediaUploadInput" accept="image/*" style="display:none">
    `;
    document.getElementById('mediaUploadInput').addEventListener('change', uploadMediaFile);
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function uploadMediaFile(e) {
  const file = e.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('image', file);
  try {
    const res = await fetch('/admin/api/media/upload', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': getCSRFToken() },
      body: formData
    });
    if (res.ok) {
      alert('Imagine uploadată cu succes!');
      renderMedia();
    } else {
      alert('Eroare la upload: ' + await res.text());
    }
  } catch (err) {
    alert('Eroare la upload: ' + err.message);
  }
}

async function deleteMedia(id) {
  if (!confirm('Sigur ștergi acest fișier?')) return;
  await fetch(`/admin/api/media/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCSRFToken() } });
  renderMedia();
}

// Customers
async function renderCustomers() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/customers');
    if (!response.ok) throw new Error('Failed to fetch customers');
    const customers = await response.json();
    app.innerHTML = `
      <div class="header"><h1>Clienți</h1></div>
      <div class="card">
        ${customers.length === 0 ? '<p>Nu există clienți.</p>' : `
        <table>
          <thead><tr><th>Nume</th><th>Email</th><th>Data Înregistrare</th></tr></thead>
          <tbody>
            ${customers.map(c => `<tr><td>${c.name || '-'}</td><td>${c.email}</td><td>${new Date(c.created_at).toLocaleDateString('ro-RO')}</td></tr>`).join('')}
          </tbody>
        </table>`}
      </div>
    `;
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

// Orders
async function renderOrders() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/orders');
    if (!response.ok) throw new Error('Failed to fetch orders');
    const orders = await response.json();
    app.innerHTML = `
      <div class="header"><h1>Comenzi</h1></div>
      <div class="card">
        ${orders.length === 0 ? '<p>Nu există comenzi.</p>' : `
        <table>
          <thead><tr><th>ID</th><th>Client</th><th>Total</th><th>Plată</th><th>Status</th><th>Data</th><th>Acțiuni</th></tr></thead>
          <tbody>
            ${orders.map(o => `
              <tr>
                <td>#${o.id}</td>
                <td>${o.customer_name || '-'}<br><small>${o.customer_email || '-'}</small></td>
                <td>${(o.total_cents / 100).toFixed(2)} RON</td>
                <td>${o.payment_method === 'card' ? '💳 Card' : '🏦 Transfer'}</td>
                <td>
                  <select onchange="updateOrderStatus(${o.id}, this.value)" style="padding:5px">
                    <option value="pending" ${o.status === 'pending' ? 'selected' : ''}>În așteptare</option>
                    <option value="paid" ${o.status === 'paid' ? 'selected' : ''}>Plătit</option>
                    <option value="processing" ${o.status === 'processing' ? 'selected' : ''}>Procesare</option>
                    <option value="completed" ${o.status === 'completed' ? 'selected' : ''}>Finalizat</option>
                    <option value="cancelled" ${o.status === 'cancelled' ? 'selected' : ''}>Anulat</option>
                  </select>
                </td>
                <td>${new Date(o.created_at).toLocaleDateString('ro-RO')}</td>
                <td><button class="btn btn-sm" onclick="viewOrder(${o.id})">Vezi</button></td>
              </tr>
            `).join('')}
          </tbody>
        </table>`}
      </div>
    `;
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function updateOrderStatus(orderId, status) {
  await fetch(`/admin/api/orders/${orderId}/status`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
    body: JSON.stringify({ status })
  });
  alert('Status actualizat!');
}

function viewOrder(id) {
  alert('Detalii comandă #' + id + ' (în dezvoltare)');
}

// Newsletter
async function renderNewsletter() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/newsletter');
    if (!response.ok) throw new Error('Failed to fetch newsletter');
    const subs = await response.json();
    app.innerHTML = `
      <div class="header"><h1>Newsletter Subscribers</h1></div>
      <div class="card">
        ${subs.length === 0 ? '<p>Nu există abonați.</p>' : `
        <table>
          <thead><tr><th>Email</th><th>Data Abonare</th></tr></thead>
          <tbody>
            ${subs.map(s => `<tr><td>${s.email}</td><td>${new Date(s.created_at).toLocaleDateString('ro-RO')}</td></tr>`).join('')}
          </tbody>
        </table>`}
      </div>
    `;
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

// Coupons
async function renderCoupons() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/coupons');
    if (!response.ok) throw new Error('Failed to fetch coupons');
    const coupons = await response.json();
    app.innerHTML = `
      <div class="header">
        <h1>Cupoane Reducere</h1>
        <button class="btn" onclick="showCouponEditor()">+ Cupon Nou</button>
      </div>
      <div class="card">
        ${coupons.length === 0 ? '<p>Nu există cupoane.</p>' : `
        <table>
          <thead><tr><th>Cod</th><th>Tip</th><th>Valoare</th><th>Expirare</th><th>Acțiuni</th></tr></thead>
          <tbody>
            ${coupons.map(c => `
              <tr>
                <td><strong>${c.code}</strong></td>
                <td>${c.type === 'percent' ? 'Procent' : 'Fix'}</td>
                <td>${c.type === 'percent' ? c.value + '%' : parseFloat(c.value).toFixed(2) + ' RON'}</td>
                <td>${c.expires_at ? new Date(c.expires_at).toLocaleDateString('ro-RO') : 'Nelimitat'}</td>
                <td>
                  <button class="btn btn-sm" onclick="showCouponEditor(${c.id})">Editează</button>
                  <button class="btn btn-sm btn-danger" onclick="deleteCoupon(${c.id})">Șterge</button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>`}
      </div>
      <div id="couponModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2 id="couponModalTitle">Editare Cupon</h2>
            <button class="modal-close" onclick="closeModal('couponModal')">&times;</button>
          </div>
          <form id="couponForm">
            <input type="hidden" id="couponId">
            <div class="form-group"><label>Cod Cupon</label><input type="text" id="couponCode" required></div>
            <div class="form-group">
              <label>Tip Reducere</label>
              <select id="couponType" required>
                <option value="percent">Procent (%)</option>
                <option value="fixed">Sumă Fixă (RON)</option>
              </select>
            </div>
            <div class="form-group"><label>Valoare</label><input type="number" id="couponValue" min="0" step="0.01" required></div>
            <div class="form-group"><label>Comandă Minimă (RON)</label><input type="number" id="couponMinOrder" min="0" step="0.01"></div>
            <div class="form-group"><label>Număr Maxim Utilizări</label><input type="number" id="couponMaxUses" min="0"></div>
            <div class="form-group"><label>Data Expirare</label><input type="date" id="couponExpires"></div>
            <div class="actions">
              <button type="submit" class="btn">Salvează</button>
              <button type="button" class="btn btn-secondary" onclick="closeModal('couponModal')">Anulează</button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.getElementById('couponForm').addEventListener('submit', saveCoupon);
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function showCouponEditor(id = null) {
  if (!document.getElementById('couponModal')) await renderCoupons();
  const modal = document.getElementById('couponModal');
  modal.classList.add('active');
  if (id) {
    const coupon = await fetch(`/admin/api/coupons/${id}`).then(r => r.json());
    document.getElementById('couponId').value = coupon.id;
    document.getElementById('couponCode').value = coupon.code;
    document.getElementById('couponType').value = coupon.type;
    document.getElementById('couponValue').value = coupon.value;
    document.getElementById('couponMinOrder').value = coupon.min_order_value || '';
    document.getElementById('couponMaxUses').value = coupon.max_uses || '';
    document.getElementById('couponExpires').value = coupon.expires_at ? coupon.expires_at.split('T')[0] : '';
    document.getElementById('couponModalTitle').textContent = 'Editare Cupon';
  } else {
    document.getElementById('couponForm').reset();
    document.getElementById('couponId').value = '';
    document.getElementById('couponModalTitle').textContent = 'Cupon Nou';
  }
}

async function saveCoupon(e) {
  e.preventDefault();
  const id = document.getElementById('couponId').value;
  const type = document.getElementById('couponType').value;
  const value = parseFloat(document.getElementById('couponValue').value);
  const minOrder = document.getElementById('couponMinOrder').value;
  const data = {
    code: document.getElementById('couponCode').value.toUpperCase(),
    type: type,
    value: value,
    min_order_cents: minOrder ? Math.round(parseFloat(minOrder) * 100) : null,
    max_uses: document.getElementById('couponMaxUses').value || null,
    expires_at: document.getElementById('couponExpires').value || null
  };
  const response = await fetch(id ? `/admin/api/coupons/${id}` : '/admin/api/coupons', {
    method: id ? 'PUT' : 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
    body: JSON.stringify(data)
  });
  if (!response.ok) { alert('Eroare la salvare'); return; }
  closeModal('couponModal');
  renderCoupons();
}

async function deleteCoupon(id) {
  if (!confirm('Sigur ștergi acest cupon?')) return;
  await fetch(`/admin/api/coupons/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': getCSRFToken() } });
  renderCoupons();
}

// Settings
async function renderSettings() {
  const app = document.getElementById('app');
  try {
    const response = await fetch('/admin/api/settings');
    if (!response.ok) throw new Error('Failed to fetch settings');
    const settings = await response.json();
    const settingsObj = {};
    settings.forEach(s => settingsObj[s.key] = s.value);
    app.innerHTML = `
      <div class="header"><h1>Setări Site</h1></div>
      <div class="card">
        <form id="settingsForm">
          <div class="form-group"><label>Titlu Site</label><input type="text" id="site_title" value="${settingsObj.site_title || ''}"></div>
          <div class="form-group"><label>Descriere Site</label><textarea id="site_description" rows="3">${settingsObj.site_description || ''}</textarea></div>
          <div class="form-group"><label>Email Contact</label><input type="email" id="contact_email" value="${settingsObj.contact_email || ''}"></div>
          <div class="form-group"><label>Telefon Contact</label><input type="text" id="contact_phone" value="${settingsObj.contact_phone || ''}"></div>
          <div class="form-group"><label>Facebook URL</label><input type="url" id="facebook_url" value="${settingsObj.facebook_url || ''}"></div>
          <div class="form-group"><label>Titular Cont Bancar</label><input type="text" id="bank_titular" value="${settingsObj.bank_titular || ''}"></div>
          <div class="form-group"><label>IBAN</label><input type="text" id="bank_iban" value="${settingsObj.bank_iban || ''}"></div>
          <div class="form-group"><label>Bancă</label><input type="text" id="bank_name" value="${settingsObj.bank_name || ''}"></div>
          <button type="submit" class="btn">Salvează Setări</button>
        </form>
      </div>
    `;
    document.getElementById('settingsForm').addEventListener('submit', saveSettings);
  } catch (error) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + error.message + '</p></div>';
  }
}

async function saveSettings(e) {
  e.preventDefault();
  const keys = ['site_title', 'site_description', 'contact_email', 'contact_phone', 'facebook_url', 'bank_titular', 'bank_iban', 'bank_name'];
  const data = {};
  keys.forEach(key => { data[key] = document.getElementById(key).value; });
  await fetch('/admin/api/settings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCSRFToken() },
    body: JSON.stringify(data)
  });
  alert('Setări salvate!');
}

// Utilities
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('active');
    if (id === 'mediaPickerModal') setTimeout(() => modal.remove(), 300);
  }
}

async function loadPage(page) {
  const app = document.getElementById('app');
  try {
    switch(page) {
      case 'dashboard': await renderDashboard(); break;
      case 'pages': await renderPages(); break;
      case 'products': await renderProducts(); break;
      case 'categories': await renderCategories(); break;
      case 'media': await renderMedia(); break;
      case 'coupons': await renderCoupons(); break;
      case 'settings': await renderSettings(); break;
      case 'customers': await renderCustomers(); break;
      case 'orders': await renderOrders(); break;
      case 'newsletter': await renderNewsletter(); break;
      default: app.innerHTML = '<div class="header"><h1>' + page + '</h1><p>În dezvoltare...</p></div>';
    }
  } catch(err) {
    app.innerHTML = '<div class="header"><h1>Eroare</h1><p>' + err.message + '</p></div>';
  }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  const app = document.getElementById('app');
  if (!app) return;
  document.querySelectorAll('.nav a[onclick]').forEach(link => {
    const match = link.getAttribute('onclick').match(/loadPage\('(.+?)'\)/);
    if (match) {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.nav a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
        currentPage = match[1];
        loadPage(currentPage);
      });
    }
  });
  const params = new URLSearchParams(location.search);
  const openParam = params.get('open');
  if (openParam) currentPage = openParam;
  loadPage(currentPage);
});
