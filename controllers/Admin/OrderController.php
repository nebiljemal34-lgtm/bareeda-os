<?php
/**
 * Bareeda Injera - Admin Order Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminOrderController extends AdminBaseController
{
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }

    /**
     * List all orders with optional status and date filters.
     */
    public function index(): void
    {
        $page       = max(1, $this->getInt('page', 1));
        $perPage    = ADMIN_ITEMS_PER_PAGE;
        $offset     = ($page - 1) * $perPage;
        $status     = trim($this->get('status', ''));
        $dateFrom   = trim($this->get('date_from', ''));
        $dateTo     = trim($this->get('date_to', ''));

        // Build query with filters
        $sql    = "SELECT o.*,
                          COALESCE(u.first_name, o.guest_name, 'Guest') AS first_name,
                          COALESCE(u.last_name, '') AS last_name,
                          COALESCE(u.email, o.guest_email, '') AS email,
                          COALESCE(u.phone, o.guest_phone, '') AS phone
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id";
        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[]  = 'o.status = ?';
            $params[] = $status;
        }

        if ($dateFrom !== '') {
            $where[]  = 'DATE(o.created_at) >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[]  = 'DATE(o.created_at) <= ?';
            $params[] = $dateTo;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        // Count total for pagination
        $countSql = preg_replace('/SELECT o\.\*,.*?FROM/', 'SELECT COUNT(*) AS total FROM', $sql);
        // Simpler: run a separate count query
        $countSql2 = "SELECT COUNT(*) AS total FROM orders o";
        $countParams = [];
        if (!empty($where)) {
            $countSql2 .= ' WHERE ' . implode(' AND ', $where);
            $countParams = $params;
        }
        $countRow = $this->db->fetchOne($countSql2, $countParams);
        $total = (int) ($countRow['total'] ?? 0);

        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $orders = $this->db->query($sql, $params);
        $totalPages = max(1, ceil($total / $perPage));

        $this->render('admin/order/index', [
            'pageTitle'  => 'Orders',
            'orders'     => $orders,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'status'     => $status,
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
        ]);
    }

    /**
     * Show order detail.
     */
    public function show(string $id): void
    {
        $order = $this->orderModel->getById((int) $id);

        if (!$order) {
            flash('error', 'Order not found.');
            redirect('/admin/orders');
        }

        $payments = new PaymentGateway();

        $this->render('admin/order/show', [
            'pageTitle' => 'Order #' . $order['order_number'],
            'order'     => $order,
            'payment_transactions' => $payments->getByOrder((int) $id),
            'payment_providers' => PaymentGateway::providers(),
            'order_events' => $this->orderModel->getEvents((int) $id),
        ]);
    }

    /**
     * Update order status (POST).
     */
    public function updateStatus(string $id): void
    {
        $newStatus = $this->post('status', '');

        $result = $this->orderModel->updateStatus((int) $id, $newStatus);

        if ($result) {
            flash('success', 'Order status updated to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
        } else {
            flash('error', 'Failed to update order status. Invalid status value.');
        }

        redirect('/admin/orders/' . $id);
    }

    /**
     * Update payment status (POST).
     */
    public function updatePaymentStatus(string $id): void
    {
        $newStatus = $this->post('payment_status', '');

        $result = $this->orderModel->updatePaymentStatus((int) $id, $newStatus);

        if ($result) {
            flash('success', 'Payment status updated to ' . ucfirst($newStatus) . '.');
        } else {
            flash('error', 'Failed to update payment status. Invalid status value.');
        }

        redirect('/admin/orders/' . $id);
    }
}
