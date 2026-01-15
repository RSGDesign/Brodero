<?php
/**
 * AJAX Handler pentru SEO Pages
 * CRUD operations pentru gestionarea SEO-ului
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/seo.php';

// Verificare admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acces interzis']);
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

// ============================================================================
// ACȚIUNI
// ============================================================================

switch ($action) {
    
    // ------------------------------------------------------------------------
    // GET - Obține o pagină SEO pentru editare
    // ------------------------------------------------------------------------
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalid']);
            exit;
        }
        
        $page = getSeoPageById($id, $db);
        
        if ($page) {
            echo json_encode([
                'success' => true,
                'page' => $page
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Pagina nu a fost găsită']);
        }
        break;
    
    // ------------------------------------------------------------------------
    // ADD - Adaugă o pagină SEO nouă
    // ------------------------------------------------------------------------
    case 'add':
        $pageSlug = trim($_POST['page_slug'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $ogImage = trim($_POST['og_image'] ?? '');
        $isActive = (int)($_POST['is_active'] ?? 1);
        
        // Validare
        if (empty($pageSlug) || empty($title)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Page Slug și Meta Title sunt obligatorii'
            ]);
            exit;
        }
        
        // Verifică dacă slug-ul există deja
        $existing = getSeoForPage($pageSlug, $db);
        if ($existing) {
            echo json_encode([
                'success' => false, 
                'message' => 'Există deja o pagină SEO cu acest slug'
            ]);
            exit;
        }
        
        $data = [
            'page_slug' => $pageSlug,
            'title' => $title,
            'description' => $description ?: null,
            'keywords' => $keywords ?: null,
            'og_image' => $ogImage ?: null,
            'is_active' => $isActive
        ];
        
        if (saveSeoPage($data, $db)) {
            echo json_encode([
                'success' => true,
                'message' => 'Pagina SEO a fost adăugată cu succes'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Eroare la salvarea paginii'
            ]);
        }
        break;
    
    // ------------------------------------------------------------------------
    // UPDATE - Actualizează o pagină SEO existentă
    // ------------------------------------------------------------------------
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $pageSlug = trim($_POST['page_slug'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $ogImage = trim($_POST['og_image'] ?? '');
        $isActive = (int)($_POST['is_active'] ?? 1);
        
        // Validare
        if ($id <= 0 || empty($pageSlug) || empty($title)) {
            echo json_encode([
                'success' => false,
                'message' => 'Date invalide'
            ]);
            exit;
        }
        
        // Verifică dacă pagina există
        $existing = getSeoPageById($id, $db);
        if (!$existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Pagina nu există'
            ]);
            exit;
        }
        
        // Verifică dacă slug-ul e folosit de altă pagină
        if ($pageSlug !== $existing['page_slug']) {
            $duplicateCheck = getSeoForPage($pageSlug, $db);
            if ($duplicateCheck) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Există deja o pagină SEO cu acest slug'
                ]);
                exit;
            }
        }
        
        $data = [
            'page_slug' => $pageSlug,
            'title' => $title,
            'description' => $description ?: null,
            'keywords' => $keywords ?: null,
            'og_image' => $ogImage ?: null,
            'is_active' => $isActive
        ];
        
        if (saveSeoPage($data, $db, $id)) {
            echo json_encode([
                'success' => true,
                'message' => 'Pagina SEO a fost actualizată cu succes'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Eroare la actualizarea paginii'
            ]);
        }
        break;
    
    // ------------------------------------------------------------------------
    // DELETE - Șterge o pagină SEO
    // ------------------------------------------------------------------------
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID invalid'
            ]);
            exit;
        }
        
        // Verifică dacă pagina există
        $existing = getSeoPageById($id, $db);
        if (!$existing) {
            echo json_encode([
                'success' => false,
                'message' => 'Pagina nu există'
            ]);
            exit;
        }
        
        if (deleteSeoPage($id, $db)) {
            echo json_encode([
                'success' => true,
                'message' => 'Pagina SEO a fost ștearsă cu succes'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Eroare la ștergerea paginii'
            ]);
        }
        break;
    
    // ------------------------------------------------------------------------
    // DEFAULT - Acțiune invalidă
    // ------------------------------------------------------------------------
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acțiune invalidă'
        ]);
        break;
}
