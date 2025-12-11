<?php
/**
 * Funcții Helper pentru Categorii Many-to-Many
 * Gestionează relația între produse și categorii
 */

/**
 * Obține toate categoriile unui produs
 * @param int $product_id
 * @return array
 */
function getProductCategories($product_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.* 
        FROM categories c
        INNER JOIN product_categories pc ON c.id = pc.category_id
        WHERE pc.product_id = ?
        ORDER BY c.name ASC
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obține ID-urile categoriilor unui produs
 * @param int $product_id
 * @return array
 */
function getProductCategoryIds($product_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT category_id 
        FROM product_categories 
        WHERE product_id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['category_id'];
    }
    return $ids;
}

/**
 * Atribuie categorii unui produs
 * @param int $product_id
 * @param array $category_ids
 * @return bool
 */
function assignCategoriesToProduct($product_id, $category_ids) {
    $db = getDB();
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Șterge categoriile existente
        deleteProductCategories($product_id);
        
        // Adaugă noile categorii
        if (!empty($category_ids)) {
            $stmt = $db->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            
            foreach ($category_ids as $category_id) {
                $category_id = (int)$category_id;
                if ($category_id > 0) {
                    $stmt->bind_param("ii", $product_id, $category_id);
                    $stmt->execute();
                }
            }
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Eroare assignCategoriesToProduct: " . $e->getMessage());
        return false;
    }
}

/**
 * Șterge toate categoriile unui produs
 * @param int $product_id
 * @return bool
 */
function deleteProductCategories($product_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM product_categories WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    return $stmt->execute();
}

/**
 * Verifică dacă un produs aparține unei categorii
 * @param int $product_id
 * @param int $category_id
 * @return bool
 */
function productHasCategory($product_id, $category_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM product_categories 
        WHERE product_id = ? AND category_id = ?
    ");
    $stmt->bind_param("ii", $product_id, $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Obține produsele dintr-o categorie (cu suport many-to-many)
 * @param int $category_id
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getProductsByCategory($category_id, $limit = null, $offset = 0) {
    $db = getDB();
    
    $sql = "
        SELECT DISTINCT p.* 
        FROM products p
        INNER JOIN product_categories pc ON p.id = pc.product_id
        WHERE pc.category_id = ?
        ORDER BY p.created_at DESC
    ";
    
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
    }
    
    $stmt = $db->prepare($sql);
    
    if ($limit !== null) {
        $stmt->bind_param("iii", $category_id, $limit, $offset);
    } else {
        $stmt->bind_param("i", $category_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Numără produsele dintr-o categorie
 * @param int $category_id
 * @return int
 */
function countProductsByCategory($category_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        INNER JOIN product_categories pc ON p.id = pc.product_id
        WHERE pc.category_id = ?
    ");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (int)$row['total'];
}

/**
 * Obține toate categoriile cu numărul de produse
 * @return array
 */
function getCategoriesWithProductCount() {
    $db = getDB();
    $query = "
        SELECT c.*, COUNT(DISTINCT pc.product_id) as product_count
        FROM categories c
        LEFT JOIN product_categories pc ON c.id = pc.category_id
        GROUP BY c.id
        ORDER BY c.display_order ASC, c.name ASC
    ";
    return $db->query($query)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obține produse cu filtre multiple (inclusiv categorii multiple)
 * @param array $filters ['category_ids' => [1,2,3], 'search' => 'text', ...]
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getProductsWithFilters($filters = [], $limit = null, $offset = 0) {
    $db = getDB();
    
    $where = ["1=1"];
    $params = [];
    $types = "";
    
    // Filtrare după categorii multiple
    if (!empty($filters['category_ids'])) {
        $categoryIds = array_map('intval', (array)$filters['category_ids']);
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $where[] = "p.id IN (SELECT DISTINCT product_id FROM product_categories WHERE category_id IN ($placeholders))";
        foreach ($categoryIds as $catId) {
            $params[] = $catId;
            $types .= "i";
        }
    }
    
    // Căutare text
    if (!empty($filters['search'])) {
        $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    // Preț minim
    if (isset($filters['min_price'])) {
        $where[] = "p.price >= ?";
        $params[] = $filters['min_price'];
        $types .= "d";
    }
    
    // Preț maxim
    if (isset($filters['max_price'])) {
        $where[] = "p.price <= ?";
        $params[] = $filters['max_price'];
        $types .= "d";
    }
    
    // Status
    if (isset($filters['is_active'])) {
        $where[] = "p.is_active = ?";
        $params[] = $filters['is_active'];
        $types .= "i";
    }
    
    $sql = "SELECT DISTINCT p.* FROM products p WHERE " . implode(" AND ", $where);
    
    // Sortare
    $orderBy = "p.created_at DESC";
    if (!empty($filters['order_by'])) {
        switch ($filters['order_by']) {
            case 'price_asc':
                $orderBy = "p.price ASC";
                break;
            case 'price_desc':
                $orderBy = "p.price DESC";
                break;
            case 'name_asc':
                $orderBy = "p.name ASC";
                break;
            case 'name_desc':
                $orderBy = "p.name DESC";
                break;
        }
    }
    
    $sql .= " ORDER BY $orderBy";
    
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
    }
    
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Numără produse cu filtre
 * @param array $filters
 * @return int
 */
function countProductsWithFilters($filters = []) {
    $db = getDB();
    
    $where = ["1=1"];
    $params = [];
    $types = "";
    
    if (!empty($filters['category_ids'])) {
        $categoryIds = array_map('intval', (array)$filters['category_ids']);
        $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $where[] = "p.id IN (SELECT DISTINCT product_id FROM product_categories WHERE category_id IN ($placeholders))";
        foreach ($categoryIds as $catId) {
            $params[] = $catId;
            $types .= "i";
        }
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if (isset($filters['min_price'])) {
        $where[] = "p.price >= ?";
        $params[] = $filters['min_price'];
        $types .= "d";
    }
    
    if (isset($filters['max_price'])) {
        $where[] = "p.price <= ?";
        $params[] = $filters['max_price'];
        $types .= "d";
    }
    
    if (isset($filters['is_active'])) {
        $where[] = "p.is_active = ?";
        $params[] = $filters['is_active'];
        $types .= "i";
    }
    
    $sql = "SELECT COUNT(DISTINCT p.id) as total FROM products p WHERE " . implode(" AND ", $where);
    
    $stmt = $db->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (int)$row['total'];
}
