<?php
/**
 * Bareeda Injera - Subscription Model
 *
 * Manages recurring injera delivery subscriptions:
 * create, pause, resume, cancel, and schedule deliveries.
 */

require_once __DIR__ . '/../config/database.php';

class Subscription
{
    /** @var Database */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Create ─────────────────────────────────────────────

    /**
     * Create a new subscription.
     *
     * @param  int    $userId
     * @param  int    $productId
     * @param  string $frequency  weekly | biweekly | monthly
     * @return array  ['success' => bool, 'message' => string, 'subscription_id' => int|null]
     */
    public function create(int $userId, int $productId, string $frequency): array
    {
        // Validate frequency
        $allowedFrequencies = ['weekly', 'biweekly', 'monthly'];
        if (!in_array($frequency, $allowedFrequencies)) {
            return ['success' => false, 'message' => 'Invalid frequency. Choose weekly, biweekly, or monthly.', 'subscription_id' => null];
        }

        // Validate product exists and is active
        $product = $this->db->fetchOne(
            "SELECT id, name, is_active FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found or unavailable.', 'subscription_id' => null];
        }

        // Validate user exists
        $user = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.', 'subscription_id' => null];
        }

        // Check for duplicate active subscription
        $existing = $this->db->fetchOne(
            "SELECT id FROM subscriptions WHERE user_id = ? AND product_id = ? AND status = 'active'",
            [$userId, $productId]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'You already have an active subscription for this product.', 'subscription_id' => null];
        }

        // Calculate next delivery date
        $nextDeliveryDate = $this->calculateNextDeliveryDate($frequency);

        // Create subscription
        $subscriptionId = $this->db->insert('subscriptions', [
            'user_id'            => $userId,
            'product_id'         => $productId,
            'frequency'          => $frequency,
            'next_delivery_date' => $nextDeliveryDate,
            'status'             => 'active',
        ]);

        // Create initial scheduled delivery
        $this->createScheduledDelivery((int) $subscriptionId, $nextDeliveryDate);

        return [
            'success'         => true,
            'message'         => 'Subscription created successfully! Next delivery: ' . $nextDeliveryDate,
            'subscription_id' => (int) $subscriptionId,
        ];
    }

    // ─── Read Operations ────────────────────────────────────

    /**
     * Get a subscription by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT s.*, p.name AS product_name, p.name_am AS product_name_am,
                    p.price AS product_price, p.image_url AS product_image
             FROM subscriptions s
             INNER JOIN products p ON s.product_id = p.id
             WHERE s.id = ?",
            [$id]
        );
    }

    /**
     * Get all subscriptions for a user.
     *
     * @param  int $userId
     * @return array
     */
    public function getByUser(int $userId): array
    {
        return $this->db->query(
            "SELECT s.*, p.name AS product_name, p.name_am AS product_name_am,
                    p.price AS product_price, p.image_url AS product_image
             FROM subscriptions s
             INNER JOIN products p ON s.product_id = p.id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get active subscriptions only.
     */
    public function getActiveByUser(int $userId): array
    {
        return $this->db->query(
            "SELECT s.*, p.name AS product_name, p.name_am AS product_name_am,
                    p.price AS product_price, p.image_url AS product_image
             FROM subscriptions s
             INNER JOIN products p ON s.product_id = p.id
             WHERE s.user_id = ? AND s.status = 'active'
             ORDER BY s.next_delivery_date ASC",
            [$userId]
        );
    }

    /**
     * Get upcoming scheduled deliveries for a user.
     *
     * @param  int $userId
     * @param  int $days  How many days ahead to look
     * @return array
     */
    public function getNextDeliveries(int $userId, int $days = 30): array
    {
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        $today      = date('Y-m-d');

        return $this->db->query(
            "SELECT sd.*, s.frequency, s.status AS sub_status,
                    p.name AS product_name, p.name_am AS product_name_am,
                    p.price AS product_price
             FROM subscription_deliveries sd
             INNER JOIN subscriptions s ON sd.subscription_id = s.id
             INNER JOIN products p ON s.product_id = p.id
             WHERE s.user_id = ?
               AND sd.scheduled_date BETWEEN ? AND ?
               AND sd.status = 'pending'
             ORDER BY sd.scheduled_date ASC",
            [$userId, $today, $futureDate]
        );
    }

    /**
     * Get all subscriptions (admin view).
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            "SELECT s.*, u.email, u.first_name, u.last_name,
                    p.name AS product_name, p.price AS product_price
             FROM subscriptions s
             INNER JOIN users u ON s.user_id = u.id
             INNER JOIN products p ON s.product_id = p.id
             ORDER BY s.created_at DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Count subscriptions.
     */
    public function count(?string $status = null): int
    {
        $sql    = "SELECT COUNT(*) AS total FROM subscriptions";
        $params = [];
        if ($status !== null) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }

    // ─── Status Management ──────────────────────────────────

    /**
     * Pause a subscription.
     *
     * @param  int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function pause(int $id): array
    {
        $sub = $this->getById($id);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription not found.'];
        }
        if ($sub['status'] !== 'active') {
            return ['success' => false, 'message' => 'Only active subscriptions can be paused.'];
        }

        $this->db->update('subscriptions', ['status' => 'paused'], 'id = ?', [$id]);

        return ['success' => true, 'message' => 'Subscription paused.'];
    }

    /**
     * Resume a paused subscription.
     *
     * @param  int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function resume(int $id): array
    {
        $sub = $this->getById($id);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription not found.'];
        }
        if ($sub['status'] !== 'paused') {
            return ['success' => false, 'message' => 'Only paused subscriptions can be resumed.'];
        }

        // Recalculate next delivery date from today
        $nextDate = $this->calculateNextDeliveryDate($sub['frequency']);

        $this->db->update('subscriptions', [
            'status'             => 'active',
            'next_delivery_date' => $nextDate,
        ], 'id = ?', [$id]);

        // Create next scheduled delivery
        $this->createScheduledDelivery($id, $nextDate);

        return ['success' => true, 'message' => 'Subscription resumed. Next delivery: ' . $nextDate];
    }

    /**
     * Cancel a subscription permanently.
     *
     * @param  int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancel(int $id): array
    {
        $sub = $this->getById($id);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription not found.'];
        }
        if ($sub['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'Subscription is already cancelled.'];
        }

        $this->db->update('subscriptions', ['status' => 'cancelled'], 'id = ?', [$id]);

        // Mark all pending deliveries as skipped
        $this->db->execute(
            "UPDATE subscription_deliveries SET status = 'skipped' WHERE subscription_id = ? AND status = 'pending'",
            [$id]
        );

        return ['success' => true, 'message' => 'Subscription cancelled.'];
    }

    // ─── Delivery Processing ────────────────────────────────

    /**
     * Mark a delivery as completed and schedule the next one.
     *
     * @param  int $deliveryId
     * @return array ['success' => bool, 'message' => string]
     */
    public function markDelivered(int $deliveryId): array
    {
        $delivery = $this->db->fetchOne(
            "SELECT * FROM subscription_deliveries WHERE id = ? AND status = 'pending'",
            [$deliveryId]
        );

        if (!$delivery) {
            return ['success' => false, 'message' => 'Delivery not found or already processed.'];
        }

        $this->db->update('subscription_deliveries', [
            'status'       => 'delivered',
            'delivered_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$deliveryId]);

        // Get subscription to schedule next delivery
        $sub = $this->getById((int) $delivery['subscription_id']);
        if ($sub && $sub['status'] === 'active') {
            $nextDate = $this->calculateNextDeliveryDate($sub['frequency'], $delivery['scheduled_date']);
            $this->db->update('subscriptions', ['next_delivery_date' => $nextDate], 'id = ?', [$sub['id']]);
            $this->createScheduledDelivery($sub['id'], $nextDate);
        }

        return ['success' => true, 'message' => 'Delivery marked as completed.'];
    }

    /**
     * Process due subscriptions (cron job entry point).
     * Creates orders for subscriptions whose delivery date is today.
     *
     * @return array Summary of processed subscriptions
     */
    public function processDueSubscriptions(): array
    {
        $today = date('Y-m-d');

        $dueSubscriptions = $this->db->query(
            "SELECT s.*, u.email, u.first_name, u.last_name, u.address, u.city, u.phone,
                    p.name AS product_name, p.price AS product_price, p.id AS product_id
             FROM subscriptions s
             INNER JOIN users u ON s.user_id = u.id
             INNER JOIN products p ON s.product_id = p.id
             WHERE s.next_delivery_date <= ? AND s.status = 'active'",
            [$today]
        );

        $processed = 0;
        $failed    = 0;

        foreach ($dueSubscriptions as $sub) {
            try {
                // Create an order for this subscription delivery
                $deliveryAddress = $sub['address'] ?? '';
                if ($sub['city']) {
                    $deliveryAddress .= ', ' . $sub['city'];
                }

                $items = [
                    [
                        'product_id' => $sub['product_id'],
                        'quantity'   => 1,
                    ],
                ];

                // Use the Order model to create the order
                $orderModel = new Order();
                $result = $orderModel->create(
                    (int) $sub['user_id'],
                    $items,
                    $deliveryAddress,
                    'mobile_money',
                    'Subscription delivery - ' . $sub['frequency']
                );

                if ($result['success']) {
                    // Mark the delivery as done
                    $pendingDelivery = $this->db->fetchOne(
                        "SELECT id FROM subscription_deliveries WHERE subscription_id = ? AND scheduled_date <= ? AND status = 'pending' ORDER BY scheduled_date ASC LIMIT 1",
                        [$sub['id'], $today]
                    );

                    if ($pendingDelivery) {
                        $this->db->update('subscription_deliveries', [
                            'status'       => 'delivered',
                            'delivered_at' => date('Y-m-d H:i:s'),
                        ], 'id = ?', [$pendingDelivery['id']]);
                    }

                    // Schedule next delivery
                    $nextDate = $this->calculateNextDeliveryDate($sub['frequency'], $today);
                    $this->db->update('subscriptions', ['next_delivery_date' => $nextDate], 'id = ?', [$sub['id']]);
                    $this->createScheduledDelivery($sub['id'], $nextDate);

                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                $this->logError("Failed to process subscription {$sub['id']}: " . $e->getMessage());
            }
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    // ─── Private Helpers ────────────────────────────────────

    /**
     * Calculate the next delivery date based on frequency.
     *
     * @param  string      $frequency
     * @param  string|null $fromDate  Base date (default: today)
     * @return string      Y-m-d
     */
    private function calculateNextDeliveryDate(string $frequency, ?string $fromDate = null): string
    {
        $base = $fromDate ? new DateTime($fromDate) : new DateTime('today');

        switch ($frequency) {
            case 'weekly':
                $base->modify('+7 days');
                break;
            case 'biweekly':
                $base->modify('+14 days');
                break;
            case 'monthly':
                $base->modify('+1 month');
                break;
            default:
                $base->modify('+7 days');
        }

        return $base->format('Y-m-d');
    }

    /**
     * Create a scheduled delivery record.
     */
    private function createScheduledDelivery(int $subscriptionId, string $date): void
    {
        $this->db->insert('subscription_deliveries', [
            'subscription_id' => $subscriptionId,
            'scheduled_date'  => $date,
            'status'          => 'pending',
        ]);
    }

    /**
     * Log errors.
     */
    private function logError(string $message): void
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__) . '/logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        @file_put_contents(
            $logDir . 'subscription_errors.log',
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        error_log("[Subscription] {$message}");
    }
}
