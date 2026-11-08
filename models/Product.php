<?php
/**
 * Bareeda Injera - Product Model
 *
 * Handles all product CRUD operations, search, filtering,
 * and stock management.
 */

require_once __DIR__ . '/../config/database.php';

class Product
{
    /** @var Database */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Read Operations ────────────────────────────────────

    /**
     * Get all products with optional category filter.
     *
     * @param  bool  $activeOnly  Only return active products
     * @param  int   $categoryId  Filter by category (0 = all)
     * @param  int   $limit
     * @param  int   $offset
     * @param  string $sortBy     price | name | created_at
     * @param  string $sortDir    ASC | DESC
     * @return array
     */
    public function getAll(bool $activeOnly = true, int $categoryId = 0, int $limit = 0, int $offset = 0, string $sortBy = 'created_at', string $sortDir = 'DESC'): array
    {
        $allowedSort  = ['price', 'name', 'created_at', 'stock_quantity'];
        $allowedDir   = ['ASC', 'DESC'];
        $sortBy       = in_array($sortBy, $allowedSort) ? $sortBy : 'created_at';
        $sortDir      = in_array(strtoupper($sortDir), $allowedDir) ? strtoupper($sortDir) : 'DESC';

        $sql = "SELECT p.*, GROUP_CONCAT(c.name) AS categories, GROUP_CONCAT(c.id) AS category_ids
                FROM products p
                LEFT JOIN product_categories pc ON p.id = pc.product_id
                LEFT JOIN categories c ON pc.category_id = c.id";

        $params = [];
        $where  = [];

        if ($activeOnly) {
            $where[] = 'p.is_active = 1';
        }

        if ($categoryId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM product_categories pc2 WHERE pc2.product_id = p.id AND pc2.category_id = ?)';
            $params[] = $categoryId;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " GROUP BY p.id ORDER BY p.{$sortBy} {$sortDir}";

        if ($limit > 0) {
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->db->query($sql, $params);
    }

    /**
     * Count all products (for pagination).
     */
    public function count(bool $activeOnly = true, int $categoryId = 0): int
    {
        $sql    = "SELECT COUNT(DISTINCT p.id) AS total FROM products p";
        $params = [];
        $where  = [];

        if ($activeOnly) {
            $where[] = 'p.is_active = 1';
        }

        if ($categoryId > 0) {
            $sql .= " LEFT JOIN product_categories pc ON p.id = pc.product_id";
            $where[] = 'pc.category_id = ?';
            $params[] = $categoryId;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $row = $this->db->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get a single product by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $product = $this->db->fetchOne(
            "SELECT * FROM products WHERE id = ?",
            [$id]
        );

        if ($product) {
            // Fetch categories for this product
            $product['categories'] = $this->db->query(
                "SELECT c.id, c.name, c.name_am
                 FROM categories c
                 INNER JOIN product_categories pc ON c.id = pc.category_id
                 WHERE pc.product_id = ?",
                [$id]
            );
        }

        return $product;
    }

    /**
     * Get products by category ID.
     *
     * @param  int $categoryId
     * @param  bool $activeOnly
     * @return array
     */
    public function getByCategory(int $categoryId, bool $activeOnly = true): array
    {
        $sql = "SELECT p.*
                FROM products p
                INNER JOIN product_categories pc ON p.id = pc.product_id
                WHERE pc.category_id = ?";

        $params = [$categoryId];

        if ($activeOnly) {
            $sql .= " AND p.is_active = 1";
        }

        $sql .= " ORDER BY p.name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Search products by name or description.
     *
     * @param  string $query
     * @param  bool   $activeOnly
     * @return array
     */
    public function search(string $query, bool $activeOnly = true): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $searchTerm = '%' . $query . '%';

        $sql = "SELECT p.*
                FROM products p
                WHERE (p.name LIKE ? OR p.name_am LIKE ? OR p.description LIKE ? OR p.description_am LIKE ?)";

        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];

        if ($activeOnly) {
            $sql .= " AND p.is_active = 1";
        }

        $sql .= " ORDER BY p.name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Get featured products (first N active products).
     *
     * @param  int $limit
     * @return array
     */
    public function getFeatured(int $limit = 4): array
    {
        return $this->db->query(
            "SELECT * FROM products WHERE is_active = 1 AND stock_quantity > 0 ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get low-stock products (for admin dashboard).
     *
     * @param  int $threshold
     * @return array
     */
    public function getLowStock(int $threshold = 10): array
    {
        return $this->db->query(
            "SELECT * FROM products WHERE is_active = 1 AND stock_quantity <= ? ORDER BY stock_quantity ASC",
            [$threshold]
        );
    }

    // ─── Create ─────────────────────────────────────────────

    /**
     * Create a new product.
     *
     * @param  array $data  Associative array of product fields
     * @return array ['success' => bool, 'message' => string, 'product_id' => int|null]
     */
    public function create(array $data): array
    {
        // Validate required fields
        $required = ['name', 'price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required.", 'product_id' => null];
            }
        }

        // Validate price
        $price = (float) $data['price'];
        if ($price < 0) {
            return ['success' => false, 'message' => 'Price cannot be negative.', 'product_id' => null];
        }

        // Validate stock
        $stock = isset($data['stock_quantity']) ? (int) $data['stock_quantity'] : 0;
        if ($stock < 0) {
            return ['success' => false, 'message' => 'Stock quantity cannot be negative.', 'product_id' => null];
        }

        $productId = $this->db->insert('products', [
            'name'            => $this->sanitize($data['name']),
            'name_am'         => isset($data['name_am']) ? $this->sanitize($data['name_am']) : null,
            'description'     => $data['description'] ?? null,
            'description_am'  => $data['description_am'] ?? null,
            'price'           => $price,
            'weight_kg'       => isset($data['weight_kg']) ? (float) $data['weight_kg'] : null,
            'pieces'          => isset($data['pieces']) ? max(1, (int) $data['pieces']) : 1,
            'image_url'       => $data['image_url'] ?? null,
            'stock_quantity'  => $stock,
            'is_active'       => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);

        // Assign categories if provided
        if (!empty($data['category_ids']) && is_array($data['category_ids'])) {
            $this->setCategories((int) $productId, $data['category_ids']);
        }

        return ['success' => true, 'message' => 'Product created successfully.', 'product_id' => (int) $productId];
    }

    // ─── Update ─────────────────────────────────────────────

    /**
     * Update an existing product.
     *
     * @param  int   $id
     * @param  array $data
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, array $data): array
    {
        // Check product exists
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        // Build update array with only provided fields
        $updateData = [];
        $allowedFields = ['name', 'name_am', 'description', 'description_am', 'price', 'weight_kg', 'pieces', 'image_url', 'stock_quantity', 'is_active'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($updateData['pieces'])) {
            $updateData['pieces'] = max(1, (int) $updateData['pieces']);
        }

        // Sanitize string fields
        foreach (['name', 'name_am', 'description', 'description_am', 'image_url'] as $sf) {
            if (isset($updateData[$sf]) && is_string($updateData[$sf])) {
                $updateData[$sf] = $this->sanitize($updateData[$sf]);
            }
        }

        // Validate numeric fields
        if (isset($updateData['price']) && (float) $updateData['price'] < 0) {
            return ['success' => false, 'message' => 'Price cannot be negative.'];
        }
        if (isset($updateData['stock_quantity']) && (int) $updateData['stock_quantity'] < 0) {
            return ['success' => false, 'message' => 'Stock quantity cannot be negative.'];
        }

        if (!empty($updateData)) {
            $this->db->update('products', $updateData, 'id = ?', [$id]);
        }

        // Update categories if provided
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $this->setCategories($id, $data['category_ids']);
        }

        return ['success' => true, 'message' => 'Product updated successfully.'];
    }

    // ─── Delete ─────────────────────────────────────────────

    /**
     * Delete a product (soft-delete by deactivating, or hard delete).
     *
     * @param  int  $id
     * @param  bool $hardDelete  If true, permanently remove the record
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id, bool $hardDelete = false): array
    {
        $existing = $this->getById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        if ($hardDelete) {
            // Check if product is in any active orders
            $orderCount = $this->db->fetchOne(
                "SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?",
                [$id]
            );
            if ($orderCount && $orderCount['cnt'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete: product is referenced in existing orders.'];
            }
            $this->db->delete('products', 'id = ?', [$id]);
        } else {
            // Soft delete — deactivate
            $this->db->update('products', ['is_active' => 0], 'id = ?', [$id]);
        }

        return ['success' => true, 'message' => 'Product deleted successfully.'];
    }

    // ─── Stock Management ───────────────────────────────────

    /**
     * Update stock quantity for a product.
     *
     * @param  int $id
     * @param  int $quantity  New stock quantity
     * @return bool
     */
    public function updateStock(int $id, int $quantity): bool
    {
        if ($quantity < 0) {
            return false;
        }

        $affected = $this->db->update('products', ['stock_quantity' => $quantity], 'id = ?', [$id]);
        return $affected >= 0;
    }

    /**
     * Decrease stock by a given amount (e.g., after order placement).
     *
     * @param  int $id
     * @param  int $amount
     * @return bool
     */
    public function decreaseStock(int $id, int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $affected = $this->db->execute(
            "UPDATE products SET stock_quantity = GREATEST(stock_quantity - ?, 0) WHERE id = ?",
            [$amount, $id]
        );
        return $affected > 0;
    }

    /**
     * Increase stock by a given amount (e.g., after restocking).
     *
     * @param  int $id
     * @param  int $amount
     * @return bool
     */
    public function increaseStock(int $id, int $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $affected = $this->db->execute(
            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
            [$amount, $id]
        );
        return $affected > 0;
    }

    // ─── Category Management ────────────────────────────────

    /**
     * Set categories for a product (replaces existing).
     *
     * @param int   $productId
     * @param array $categoryIds
     */
    private function setCategories(int $productId, array $categoryIds): void
    {
        // Remove existing mappings
        $this->db->delete('product_categories', 'product_id = ?', [$productId]);

        // Insert new mappings
        foreach ($categoryIds as $catId) {
            $catId = (int) $catId;
            if ($catId > 0) {
                $this->db->insert('product_categories', [
                    'product_id'  => $productId,
                    'category_id' => $catId,
                ]);
            }
        }
    }

    // ─── Utility ────────────────────────────────────────────

    /**
     * Basic string sanitization.
     */
    private function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
