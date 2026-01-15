<?php
/**
 * SEO Pages Management - Dashboard Admin
 * Gestionare SEO per pagină: title, description, keywords
 */

// Include config ÎNAINTE de orice output
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/seo.php';

// Verificare acces admin ÎNAINTE de header
if (!isLoggedIn() || !isAdmin()) {
    setMessage("Nu ai acces la această pagină.", "danger");
    redirect('/');
}

$db = getPDO();

// Obține toate paginile SEO
$seoPages = getAllSeoPages($db);

// Include header.php DUPĂ procesarea datelor
$pageTitle = "SEO Pages - Admin";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<section class="bg-dark text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 fw-bold mb-0">
                    <i class="bi bi-search me-2"></i>SEO Pages Management
                </h1>
                <p class="mb-0 mt-2 opacity-75">Gestionează meta tags (title, description, keywords) per pagină</p>
            </div>
        </div>
    </div>
</section>

<!-- Admin Section -->
<section class="py-5">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_products.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-box-seam me-2"></i>Produse
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_categories.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-tags me-2"></i>Categorii
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_coupons.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-ticket-perforated me-2"></i>Cupoane
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/product_files.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-file-earmark-arrow-down me-2"></i>Fișiere Descărcabile
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_orders.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-cart-check me-2"></i>Comenzi
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_users.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-people me-2"></i>Utilizatori
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_referrals.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-people-fill me-1"></i>Referrals
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/admin_newsletter.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-envelope-paper me-2"></i>Newsletter
                            </a>
                            <a href="<?php echo SITE_URL; ?>/admin/seo-pages.php" 
                               class="list-group-item list-group-item-action active">
                                <i class="bi bi-search me-2"></i>SEO Pages
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/cont.php" 
                               class="list-group-item list-group-item-action">
                                <i class="bi bi-arrow-left me-2"></i>Înapoi la Cont
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <!-- Success/Error Messages -->
                <?php if (hasMessage()): ?>
                    <div class="alert alert-<?php echo getMessageType(); ?> alert-dismissible fade show" role="alert">
                        <?php echo getMessage(); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Header Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Toate Paginile SEO
                                    <span class="badge bg-primary ms-2"><?php echo count($seoPages); ?></span>
                                </h5>
                                <!-- DEBUG -->
                                <?php if (count($seoPages) === 0): ?>
                                    <small class="text-danger">⚠️ Array gol - verifică baza de date</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSeoModal">
                                    <i class="bi bi-plus-circle me-2"></i>Adaugă Pagină SEO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Pages Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4 py-3">Pagină (Slug)</th>
                                        <th class="py-3">Meta Title</th>
                                        <th class="py-3">Keywords</th>
                                        <th class="py-3 text-center">Status</th>
                                        <th class="py-3">Actualizat</th>
                                        <th class="py-3 text-end pe-4">Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($seoPages)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p>Nu există pagini SEO configurate.</p>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSeoModal">
                                                    <i class="bi bi-plus-circle me-2"></i>Adaugă Prima Pagină
                                                </button>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($seoPages as $page): ?>
                                            <tr data-seo-id="<?php echo $page['id']; ?>">
                                                <td class="px-4">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (strpos($page['page_slug'], 'product:') === 0): ?>
                                                            <i class="bi bi-box-seam text-info me-2"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                                        <?php endif; ?>
                                                        <code class="text-dark"><?php echo htmlspecialchars($page['page_slug']); ?></code>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;" 
                                                         title="<?php echo htmlspecialchars($page['title']); ?>">
                                                        <?php echo htmlspecialchars($page['title']); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $titleLen = mb_strlen($page['title']);
                                                        $titleClass = $titleLen > 60 ? 'text-danger' : ($titleLen > 50 ? 'text-warning' : 'text-success');
                                                        ?>
                                                        <span class="<?php echo $titleClass; ?>">
                                                            <?php echo $titleLen; ?> caractere
                                                        </span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($page['keywords'])): ?>
                                                        <?php 
                                                        $keywords = explode(',', $page['keywords']);
                                                        $displayKeywords = array_slice($keywords, 0, 3);
                                                        ?>
                                                        <div>
                                                            <?php foreach ($displayKeywords as $kw): ?>
                                                                <span class="badge bg-light text-dark me-1 mb-1">
                                                                    <?php echo htmlspecialchars(trim($kw)); ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                            <?php if (count($keywords) > 3): ?>
                                                                <span class="badge bg-secondary">+<?php echo count($keywords) - 3; ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <small class="text-muted">Niciun keyword</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($page['is_active']): ?>
                                                        <span class="badge bg-success">Activ</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactiv</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('d.m.Y H:i', strtotime($page['updated_at'])); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                                            onclick="editSeoPage(<?php echo $page['id']; ?>)">
                                                        <i class="bi bi-pencil"></i> Editează
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteSeoPage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['page_slug']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="card border-0 bg-light mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Ghid Rapid SEO</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Meta Title:</strong>
                                <ul class="small mb-0 mt-2">
                                    <li>Optim: 50-60 caractere</li>
                                    <li>Include cuvinte cheie principale</li>
                                    <li>Unic per pagină</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <strong>Meta Description:</strong>
                                <ul class="small mb-0 mt-2">
                                    <li>Optim: 150-160 caractere</li>
                                    <li>Descriere clară a paginii</li>
                                    <li>Include call-to-action</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <strong>Keywords:</strong>
                                <ul class="small mb-0 mt-2">
                                    <li>Separate prin virgulă</li>
                                    <li>5-10 keywords relevante</li>
                                    <li>Exemple: <code>produse digitale, șabloane, fonturi</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add SEO Page Modal -->
<div class="modal fade" id="addSeoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Adaugă Pagină SEO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSeoForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Page Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="page_slug" required 
                               placeholder="Ex: despre-noi, contact, product:template-grafic">
                        <small class="text-muted">
                            Format: <code>slug-pagina</code> sau <code>product:slug-produs</code>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required maxlength="255"
                               placeholder="Ex: Despre Noi - Brodero">
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Optim: 50-60 caractere</small>
                            <small class="text-muted" id="titleCharCount">0 / 60</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Description</label>
                        <textarea class="form-control" name="description" rows="3" maxlength="500"
                                  placeholder="Descriere optimizată pentru motoarele de căutare..."></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Optim: 150-160 caractere</small>
                            <small class="text-muted" id="descCharCount">0 / 160</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keywords (separate prin virgulă)</label>
                        <input type="text" class="form-control" name="keywords"
                               placeholder="Ex: produse digitale, șabloane grafice, fonturi premium">
                        <small class="text-muted">5-10 keywords relevante</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">OG Image URL (opțional)</label>
                        <input type="url" class="form-control" name="og_image"
                               placeholder="https://brodero.com/assets/images/og-image.jpg">
                        <small class="text-muted">Imagine pentru social media sharing (1200x630px recomandat)</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" checked id="addIsActive">
                            <label class="form-check-label" for="addIsActive">Pagină activă</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-success" onclick="saveSeoPage()">
                    <i class="bi bi-check-circle me-2"></i>Salvează
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit SEO Page Modal -->
<div class="modal fade" id="editSeoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editează Pagină SEO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSeoForm">
                    <input type="hidden" name="id" id="editSeoId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Page Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="page_slug" id="editPageSlug" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="editTitle" required maxlength="255">
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Optim: 50-60 caractere</small>
                            <small class="text-muted" id="editTitleCharCount">0 / 60</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Meta Description</label>
                        <textarea class="form-control" name="description" id="editDescription" rows="3" maxlength="500"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Optim: 150-160 caractere</small>
                            <small class="text-muted" id="editDescCharCount">0 / 160</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keywords (separate prin virgulă)</label>
                        <input type="text" class="form-control" name="keywords" id="editKeywords">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">OG Image URL (opțional)</label>
                        <input type="url" class="form-control" name="og_image" id="editOgImage">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive">
                            <label class="form-check-label" for="editIsActive">Pagină activă</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                <button type="button" class="btn btn-primary" onclick="updateSeoPage()">
                    <i class="bi bi-check-circle me-2"></i>Actualizează
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter pentru Add form
document.querySelector('#addSeoModal input[name="title"]')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('titleCharCount').textContent = count + ' / 60';
    document.getElementById('titleCharCount').className = count > 60 ? 'text-danger' : (count > 50 ? 'text-warning' : 'text-muted');
});

document.querySelector('#addSeoModal textarea[name="description"]')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('descCharCount').textContent = count + ' / 160';
    document.getElementById('descCharCount').className = count > 160 ? 'text-danger' : (count > 150 ? 'text-warning' : 'text-muted');
});

// Character counter pentru Edit form
document.getElementById('editTitle')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('editTitleCharCount').textContent = count + ' / 60';
    document.getElementById('editTitleCharCount').className = count > 60 ? 'text-danger' : (count > 50 ? 'text-warning' : 'text-muted');
});

document.getElementById('editDescription')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('editDescCharCount').textContent = count + ' / 160';
    document.getElementById('editDescCharCount').className = count > 160 ? 'text-danger' : (count > 150 ? 'text-warning' : 'text-muted');
});

// Salvare pagină nouă
function saveSeoPage() {
    const form = document.getElementById('addSeoForm');
    const formData = new FormData(form);
    formData.append('action', 'add');
    formData.set('is_active', form.querySelector('[name="is_active"]').checked ? 1 : 0);

    fetch('<?php echo SITE_URL; ?>/ajax/seo_pages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare: ' + (data.message || 'Nu s-a putut salva pagina'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la salvare');
    });
}

// Editare pagină
function editSeoPage(id) {
    fetch('<?php echo SITE_URL; ?>/ajax/seo_pages.php?action=get&id=' + id)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const page = data.page;
            document.getElementById('editSeoId').value = page.id;
            document.getElementById('editPageSlug').value = page.page_slug;
            document.getElementById('editTitle').value = page.title;
            document.getElementById('editDescription').value = page.description || '';
            document.getElementById('editKeywords').value = page.keywords || '';
            document.getElementById('editOgImage').value = page.og_image || '';
            document.getElementById('editIsActive').checked = page.is_active == 1;

            // Update counters
            document.getElementById('editTitleCharCount').textContent = page.title.length + ' / 60';
            document.getElementById('editDescCharCount').textContent = (page.description || '').length + ' / 160';

            const modal = new bootstrap.Modal(document.getElementById('editSeoModal'));
            modal.show();
        } else {
            alert('Eroare la încărcarea datelor');
        }
    });
}

// Actualizare pagină
function updateSeoPage() {
    const form = document.getElementById('editSeoForm');
    const formData = new FormData(form);
    formData.append('action', 'update');
    formData.set('is_active', form.querySelector('[name="is_active"]').checked ? 1 : 0);

    fetch('<?php echo SITE_URL; ?>/ajax/seo_pages.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare: ' + (data.message || 'Nu s-a putut actualiza pagina'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la actualizare');
    });
}

// Ștergere pagină
function deleteSeoPage(id, slug) {
    if (!confirm(`Sigur vrei să ștergi pagina SEO: ${slug}?`)) {
        return;
    }

    fetch('<?php echo SITE_URL; ?>/ajax/seo_pages.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Eroare la ștergere');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Eroare la ștergere');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
