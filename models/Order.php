<?php
/**
 * Bareeda Injera - Order Model
 *
 * Handles order creation, retrieval, status management,
 * and admin order listing with pagination.
 */

require_once __DIR__ . '/../config/database.php';

class Order
{
    /** @var Database */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Create ─────────────────────────────────────────────

    /**
     * Place a new order.
     *
     * @param  int    $userId
     * @param  array  $items         [['product_id' => int, 'quantity' => int], ...]
     * @param  string $deliveryAddress
     * @param  string $paymentMethod  cod | bank_transfer | mobile_money
     * @param  string $notes
     * @param  string $couponCode     Optional coupon code
     * @return array  ['success' => bool, 'message' => string, 'order_id' => int|null, 'order_number' => string|null]
     */
    public function create(int $userId, array $items, string $deliveryAddress, string $paymentMethod, string $notes = '', string $couponCode = ''): array
    {
        // Validate payment method
        $allowedMethods = ['cod', 'bank_transfer', 'mobile_money'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            return ['success' => false, 'message' => 'Invalid payment method.', 'order_id' => null, 'order_number' => null];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'Cart is empty.', 'order_id' => null, 'order_number' => null];
        }

        if (trim($deliveryAddress) === '') {
            return ['success' => false, 'message' => 'Delivery address is required.', 'order_id' => null, 'order_number' => null];
        }

        // Process in a transaction
        try {
            return $this->db->transaction(function ($db) use ($userId, $items, $deliveryAddress, $paymentMethod, $notes, $couponCode) {

                // 1. Fetch product details and validate stock
                $orderItems   = [];
                $subtotal     = 0.0;

                foreach ($items as $item) {
                    $productId = (int) $item['product_id'];
                    $quantity  = (int) $item['quantity'];

                    if ($quantity < 1) {
                        throw new InvalidArgumentException("Invalid quantity for product ID {$productId}.");
                    }

                    $product = $db->fetchOne(
                        "SELECT id, name, price, stock_quantity, is_active FROM products WHERE id = ? FOR UPDATE",
                        [$productId]
                    );

                    if (!$product || !$product['is_active']) {
                        throw new InvalidArgumentException("Product ID {$productId} is not available.");
                    }

                    if ($product['stock_quantity'] < $quantity) {
                        throw new InvalidArgumentException("Insufficient stock for '{$product['name']}'. Only {$product['stock_quantity']} available.");
                    }

                    $lineTotal = round($product['price'] * $quantity, 2);
                    $subtotal += $lineTotal;

                    $orderItems[] = [
                        'product_id'  => $productId,
                        'quantity'    => $quantity,
                        'unit_price'  => $product['price'],
                        'total_price' => $lineTotal,
                    ];

                    // Decrease stock
                    $db->execute(
                        "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                        [$quantity, $productId]
                    );
                }

                $subtotal = round($subtotal, 2);

                // 2. Calculate delivery fee
                $deliveryFee = 0.0;
                if (defined('FREE_DELIVERY_THRESHOLD') && $subtotal >= FREE_DELIVERY_THRESHOLD) {
                    $deliveryFee = 0.0;
                } else {
                    $deliveryFee = defined('DEFAULT_DELIVERY_FEE') ? (float) DEFAULT_DELIVERY_FEE : 50.0;
                }

                // 3. Apply coupon if provided
                $discountAmount = 0.0;
                $couponId       = null;

                if ($couponCode !== '') {
                    $coupon = $db->fetchOne(
                        "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
                        [strtoupper(trim($couponCode))]
                    );

                    if ($coupon) {
                        // Check minimum order
                        if ($subtotal >= (float) $coupon['min_order_amount']) {
                            // Check max uses
                            if ((int) $coupon['max_uses'] === 0 || (int) $coupon['used_count'] < (int) $coupon['max_uses']) {
                                if ($coupon['discount_type'] === 'percentage') {
                                    $discountAmount = round($subtotal * ((float) $coupon['discount_value'] / 100), 2);
                                } else {
                                    $discountAmount = (float) $coupon['discount_value'];
                                }

                                // Don't let discount exceed subtotal
                                $discountAmount = min($discountAmount, $subtotal);
                                $couponId       = (int) $coupon['id'];

                                // Increment coupon usage
                                $db->execute(
                                    "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                                    [$couponId]
                                );
                            }
                        }
                    }
                }

                // 4. Check minimum order amount
                $afterDiscount = $subtotal - $discountAmount;
                if (defined('MIN_ORDER_AMOUNT') && $afterDiscount < MIN_ORDER_AMOUNT) {
                    throw new InvalidArgumentException("Minimum order amount is " . MIN_ORDER_AMOUNT . " " . CURRENCY_SYMBOL . ".");
                }

                $total = round($afterDiscount + $deliveryFee, 2);

                // 5. Generate order number
                $orderNumber = $this->generateOrderNumber();

                // 6. Insert order
                $orderId = $db->insert('orders', [
                    'user_id'          => $userId,
                    'order_number'     => $orderNumber,
                    'subtotal'         => $subtotal,
                    'delivery_fee'     => $deliveryFee,
                    'discount_amount'  => $discountAmount,
                    'total'            => $total,
                    'status'           => 'pending',
                    'payment_method'   => $paymentMethod,
                    'payment_status'   => 'pending',
                    'delivery_address' => $deliveryAddress,
                    'delivery_notes'   => $notes,
                ]);

                // 7. Insert order items
                foreach ($orderItems as $oi) {
                    $db->insert('order_items', [
                        'order_id'    => $orderId,
                        'product_id'  => $oi['product_id'],
                        'quantity'    => $oi['quantity'],
                        'unit_price'  => $oi['unit_price'],
                        'total_price' => $oi['total_price'],
                    ]);
                }

                return [
                    'success'      => true,
                    'message'      => 'Order placed successfully!',
                    'order_id'     => (int) $orderId,
                    'order_number' => $orderNumber,
                    'total'        => $total,
                ];
            });
        } catch (InvalidArgumentException $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'order_id' => null, 'order_number' => null];
        } catch (Throwable $e) {
            $this->logError('Order creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while placing your order. Please try again.', 'order_id' => null, 'order_number' => null];
        }
    }

    // ─── Read Operations ────────────────────────────────────

    /**
     * Get a single order by ID with its items.
     *
     * @param  int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, u.email, u.first_name, u.last_name, u.phone
             FROM orders o
             INNER JOIN users u ON o.user_id = u.id
             WHERE o.id = ?",
            [$id]
        );

        if ($order) {
            $order['items'] = $this->db->query(
                "SELECT oi.*, p.name, p.name_am, p.image_url
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$id]
            );
        }

        return $order;
    }

    /**
     * Get an order by order number.
     *
     * @param  string $orderNumber
     * @return array|null
     */
    public function getByOrderNumber(string $orderNumber): ?array
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, u.email, u.first_name, u.last_name, u.phone
             FROM orders o
             INNER JOIN users u ON o.user_id = u.id
             WHERE o.order_number = ?",
            [$orderNumber]
        );

        if ($order) {
            $order['items'] = $this->db->query(
                "SELECT oi.*, p.name, p.name_am, p.image_url
                 FROM order_items oi
                 INNER JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$order['id']]
            );
        }

        return $order;
    }

    /**
     * Get orders for a specific user.
     *
     * @param  int $userId
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    /**
     * Count orders for a user.
     */
    public function countByUser(int $userId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM orders WHERE user_id = ?",
            [$userId]
        );
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Get all orders (admin) with optional status filter and pagination.
     *
     * @param  string|null $status  Filter by status (null = all)
     * @param  int         $limit
     * @param  int         $offset
     * @return array
     */
    public function getAll(?string $status = null, int $limit = 25, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.email, u.first_name, u.last_name, u.phone
                FROM orders o
                INNER JOIN users u ON o.user_id = u.id";

        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->query($sql, $params);
    }

    /**
     * Count all orders (admin) with optional status filter.
     */
    public function countAll(?string $status = null): int
    {
        $sql    = "SELECT COUNT(*) AS total FROM orders o";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    // ─── Status Updates ─────────────────────────────────────

    /**
     * Update order status.
     *
     * @param  int    $id
     * @param  string $status
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        // If cancelling, restore stock
        if ($status === 'cancelled') {
            $this->restoreStockOnCancel($id);
        }

        $affected = $this->db->update('orders', ['status' => $status], 'id = ?', [$id]);
        return $affected > 0;
    }

    /**
     * Update payment status.
     *
     * @param  int    $id
     * @param  string $status  pending | paid | refunded
     * @return bool
     */
    public function updatePaymentStatus(int $id, string $status): bool
    {
        $allowed = ['pending', 'paid', 'refunded'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        $affected = $this->db->update('orders', ['payment_status' => $status], 'id = ?', [$id]);
        return $affected > 0;
    }

    // ─── Order Statistics (Admin Dashboard) ─────────────────

    /**
     * Get order statistics for the admin dashboard.
     *
     * @return array
     */
    public function getStats(): array
    {
        $today = date('Y-m-d');

        $todayOrders = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS revenue FROM orders WHERE DATE(created_at) = ? AND status != 'cancelled'",
            [$today]
        );

        $pendingOrders = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM orders WHERE status = 'pending'"
        );

        $totalRevenue = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total), 0) AS revenue FROM orders WHERE status != 'cancelled' AND payment_status = 'paid'"
        );

        return [
            'today_orders'   => (int) ($todayOrders['cnt'] ?? 0),
            'today_revenue'  => (float) ($todayOrders['revenue'] ?? 0),
            'pending_orders' => (int) ($pendingOrders['cnt'] ?? 0),
            'total_revenue'  => (float) ($totalRevenue['revenue'] ?? 0),
        ];
    }

    // ─── Private Helpers ────────────────────────────────────

    /**
     * Generate a unique order number.
     * Format: BI-YYYYMMDD-XXXXXX
     */
    private function generateOrderNumber(): string
    {
        $date   = date('Ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));
        $number = "BI-{$date}-{$random}";

        // Ensure uniqueness
        $existing = $this->db->fetchOne("SELECT id FROM orders WHERE order_number = ?", [$number]);
        if ($existing) {
            return $this->generateOrderNumber(); // Retry
        }

        return $number;
    }

    /**
     * Restore product stock when an order is cancelled.
     */
    private function restoreStockOnCancel(int $orderId): void
    {
        // Only restore if the order wasn't already cancelled
        $order = $this->db->fetchOne("SELECT status FROM orders WHERE id = ?", [$orderId]);
        if (!$order || $order['status'] === 'cancelled') {
            return;
        }

        $items = $this->db->query(
            "SELECT product_id, quantity FROM order_items WHERE order_id = ?",
            [$orderId]
        );

        foreach ($items as $item) {
            $this->db->execute(
                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?",
                [$item['quantity'], $item['product_id']]
            );
        }
    }

    /**
     * Log errors to file.
     */
    private function logError(string $message): void
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__) . '/logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents(
            $logDir . 'order_errors.log',
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        error_log("[Order] {$message}");
    }
}
