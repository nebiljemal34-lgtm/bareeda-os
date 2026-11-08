<?php
/**
 * Bareeda Injera - Cart Class
 *
 * Manages shopping cart for both logged-in users (persisted in DB)
 * and guest users (persisted via session ID in DB).
 */

require_once __DIR__ . '/../config/database.php';

class Cart
{
    /** @var Database */
    private $db;

    /** @var int|null User ID if logged in */
    private $userId;

    /** @var string|null Session ID for guest carts */
    private $sessionId;

    /** @var bool */
    private $isLoggedIn;

    public function __construct()
    {
        $this->db = Database::getInstance();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Determine if user is logged in
        $this->isLoggedIn = isset($_SESSION['user']['id']);
        $this->userId     = $this->isLoggedIn ? (int) $_SESSION['user']['id'] : null;
        $this->sessionId  = $this->isLoggedIn ? null : session_id();

        // Ensure guest session exists
        if (!$this->isLoggedIn && empty($this->sessionId)) {
            session_regenerate_id();
            $this->sessionId = session_id();
        }
    }

    // ─── Add ────────────────────────────────────────────────

    /**
     * Add a product to the cart.
     *
     * @param  int $productId
     * @param  int $quantity
     * @return array ['success' => bool, 'message' => string]
     */
    public function add(int $productId, int $quantity = 1): array
    {
        if ($quantity < 1) {
            return ['success' => false, 'message' => 'Quantity must be at least 1.'];
        }

        // Verify product exists and is active
        $product = $this->db->fetchOne(
            "SELECT id, name, price, stock_quantity, is_active FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or unavailable.'];
        }

        // Check stock
        $currentQty = $this->getItemQuantity($productId);
        if (($currentQty + $quantity) > $product['stock_quantity']) {
            return [
                'success' => false,
                'message' => 'Only ' . ($product['stock_quantity'] - $currentQty) . ' more available in stock.',
            ];
        }

        if ($this->isLoggedIn) {
            return $this->addToUserCart($productId, $quantity);
        }

        return $this->addToSessionCart($productId, $quantity);
    }

    // ─── Remove ─────────────────────────────────────────────

    /**
     * Remove a product from the cart.
     *
     * @param  int $productId
     * @return bool
     */
    public function remove(int $productId): bool
    {
        if ($this->isLoggedIn) {
            $affected = $this->db->delete('cart', 'user_id = ? AND product_id = ?', [$this->userId, $productId]);
        } else {
            $affected = $this->db->delete('cart_sessions', 'session_id = ? AND product_id = ?', [$this->sessionId, $productId]);
        }
        return $affected > 0;
    }

    // ─── Update Quantity ────────────────────────────────────

    /**
     * Update the quantity of a product in the cart.
     *
     * @param  int $productId
     * @param  int $quantity
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $productId, int $quantity): array
    {
        if ($quantity < 1) {
            return $this->remove($productId)
                ? ['success' => true, 'message' => 'Item removed.']
                : ['success' => false, 'message' => 'Could not remove item.'];
        }

        // Verify product exists and check stock
        $product = $this->db->fetchOne(
            "SELECT id, stock_quantity, is_active FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );

        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or unavailable.'];
        }

        if ($quantity > $product['stock_quantity']) {
            return ['success' => false, 'message' => 'Only ' . $product['stock_quantity'] . ' available in stock.'];
        }

        if ($this->isLoggedIn) {
            $existing = $this->db->fetchOne(
                "SELECT id FROM cart WHERE user_id = ? AND product_id = ?",
                [$this->userId, $productId]
            );
            if ($existing) {
                $this->db->update('cart', ['quantity' => $quantity], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('cart', [
                    'user_id'    => $this->userId,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                ]);
            }
        } else {
            $existing = $this->db->fetchOne(
                "SELECT id FROM cart_sessions WHERE session_id = ? AND product_id = ?",
                [$this->sessionId, $productId]
            );
            if ($existing) {
                $this->db->update('cart_sessions', ['quantity' => $quantity], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('cart_sessions', [
                    'session_id' => $this->sessionId,
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                ]);
            }
        }

        return ['success' => true, 'message' => 'Cart updated.'];
    }

    // ─── Get Items ──────────────────────────────────────────

    /**
     * Get all cart items with product details.
     *
     * @return array
     */
    public function getItems(): array
    {
        if ($this->isLoggedIn) {
            return $this->db->query(
                "SELECT c.id AS cart_id, c.product_id, c.quantity,
                        p.name, p.name_am, p.price, p.image_url, p.weight_kg, p.pieces,
                        p.stock_quantity, p.is_active,
                        (p.price * c.quantity) AS line_total
                 FROM cart c
                 INNER JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ?
                 ORDER BY c.created_at DESC",
                [$this->userId]
            );
        }

        return $this->db->query(
            "SELECT cs.id AS cart_id, cs.product_id, cs.quantity,
                    p.name, p.name_am, p.price, p.image_url, p.weight_kg, p.pieces,
                    p.stock_quantity, p.is_active,
                    (p.price * cs.quantity) AS line_total
             FROM cart_sessions cs
             INNER JOIN products p ON cs.product_id = p.id
             WHERE cs.session_id = ?
             ORDER BY cs.created_at DESC",
            [$this->sessionId]
        );
    }

    /**
     * Get the quantity of a specific product in the cart.
     */
    private function getItemQuantity(int $productId): int
    {
        if ($this->isLoggedIn) {
            $row = $this->db->fetchOne(
                "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?",
                [$this->userId, $productId]
            );
        } else {
            $row = $this->db->fetchOne(
                "SELECT quantity FROM cart_sessions WHERE session_id = ? AND product_id = ?",
                [$this->sessionId, $productId]
            );
        }
        return $row ? (int) $row['quantity'] : 0;
    }

    /**
     * Get total number of items in the cart.
     */
    public function getItemCount(): int
    {
        if ($this->isLoggedIn) {
            $row = $this->db->fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart WHERE user_id = ?",
                [$this->userId]
            );
        } else {
            $row = $this->db->fetchOne(
                "SELECT COALESCE(SUM(quantity), 0) AS total FROM cart_sessions WHERE session_id = ?",
                [$this->sessionId]
            );
        }
        return (int) ($row['total'] ?? 0);
    }

    // ─── Totals ─────────────────────────────────────────────

    /**
     * Get the cart subtotal (sum of all line totals).
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        $items = $this->getItems();
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['line_total'];
        }
        return round($subtotal, 2);
    }

    /**
     * Total injera pieces in the cart (quantity × pieces per pack).
     */
    public function getTotalPieces(): int
    {
        $pieces = 0;
        foreach ($this->getItems() as $item) {
            $pieces += (int) $item['quantity'] * max(1, (int) ($item['pieces'] ?? 1));
        }
        return $pieces;
    }

    /**
     * Calculate delivery fee. Free when the cart holds strictly more than
     * the configured minimum injera pieces; otherwise the zone/default fee.
     *
     * @return float
     */
    public function getDeliveryFee(?int $zoneId = null): float
    {
        if ($this->getTotalPieces() > freeDeliveryMinPieces()) {
            return 0.0;
        }

        if ($zoneId !== null && $zoneId > 0) {
            $zone = $this->db->fetchOne("SELECT fee FROM delivery_zones WHERE id = ? AND is_active = 1", [$zoneId]);
            if ($zone) {
                return (float) $zone['fee'];
            }
        }

        return (float) setting('default_delivery_fee', (string) DEFAULT_DELIVERY_FEE);
    }

    public function getDiscount(): float
    {
        $code = $this->getAppliedCouponCode();
        if ($code === '') {
            return 0.0;
        }

        $coupon = $this->db->fetchOne(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
            [$code]
        );
        $subtotal = $this->getSubtotal();
        if (!$coupon || $subtotal < (float) $coupon['min_order_amount']
            || ((int) $coupon['max_uses'] > 0 && (int) $coupon['used_count'] >= (int) $coupon['max_uses'])) {
            return 0.0;
        }

        $discount = $coupon['discount_type'] === 'percentage'
            ? $subtotal * ((float) $coupon['discount_value'] / 100)
            : (float) $coupon['discount_value'];
        return round(min($discount, $subtotal), 2);
    }

    public function getAppliedCouponCode(): string
    {
        return strtoupper(trim((string) ($_SESSION['applied_coupon']['code'] ?? '')));
    }

    /**
     * Get the total (subtotal + delivery - discount).
     *
     * @param  float $discount
     * @return float
     */
    public function getTotal(float $discount = 0.0, ?int $zoneId = null): float
    {
        $subtotal      = $this->getSubtotal();
        $deliveryFee   = $this->getDeliveryFee($zoneId);
        $total         = $subtotal + $deliveryFee - $discount;
        return round(max($total, 0), 2);
    }

    // ─── Clear ──────────────────────────────────────────────

    /**
     * Empty the entire cart.
     *
     * @return bool
     */
    public function clear(): bool
    {
        if ($this->isLoggedIn) {
            $this->db->delete('cart', 'user_id = ?', [$this->userId]);
        } else {
            $this->db->delete('cart_sessions', 'session_id = ?', [$this->sessionId]);
        }
        return true;
    }

    // ─── Merge Guest Cart into User Cart (on login) ─────────

    /**
     * Merge a guest session cart into the logged-in user's cart.
     * Call this after a successful login.
     *
     * @param  string $guestSessionId
     * @param  int    $userId
     * @return void
     */
    public function mergeGuestCart(string $guestSessionId, int $userId): void
    {
        $guestItems = $this->db->query(
            "SELECT product_id, quantity FROM cart_sessions WHERE session_id = ?",
            [$guestSessionId]
        );

        foreach ($guestItems as $item) {
            $existing = $this->db->fetchOne(
                "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
                [$userId, $item['product_id']]
            );

            if ($existing) {
                // Sum quantities
                $newQty = (int) $existing['quantity'] + (int) $item['quantity'];
                $this->db->update('cart', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('cart', [
                    'user_id'    => $userId,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ]);
            }
        }

        // Remove guest cart
        $this->db->delete('cart_sessions', 'session_id = ?', [$guestSessionId]);
    }

    // ─── Cleanup ────────────────────────────────────────────

    /**
     * Remove stale guest cart entries older than a given number of hours.
     *
     * @param  int $hours
     * @return int Number of rows deleted
     */
    public function cleanupStaleGuestCarts(int $hours = 24): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        return $this->db->execute(
            "DELETE FROM cart_sessions WHERE created_at < ?",
            [$cutoff]
        );
    }

    // ─── Private Helpers ────────────────────────────────────

    private function addToUserCart(int $productId, int $quantity): array
    {
        $existing = $this->db->fetchOne(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$this->userId, $productId]
        );

        if ($existing) {
            $newQty = (int) $existing['quantity'] + $quantity;
            $this->db->update('cart', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('cart', [
                'user_id'    => $this->userId,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }

        return ['success' => true, 'message' => 'Product added to cart.'];
    }

    private function addToSessionCart(int $productId, int $quantity): array
    {
        $existing = $this->db->fetchOne(
            "SELECT id, quantity FROM cart_sessions WHERE session_id = ? AND product_id = ?",
            [$this->sessionId, $productId]
        );

        if ($existing) {
            $newQty = (int) $existing['quantity'] + $quantity;
            $this->db->update('cart_sessions', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('cart_sessions', [
                'session_id' => $this->sessionId,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }

        return ['success' => true, 'message' => 'Product added to cart.'];
    }
}
