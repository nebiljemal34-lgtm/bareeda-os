<?php
/**
 * Bareeda Injera - Admin Order Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminOrderController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $orderModel = new Order();
        $cart       = $GLOBALS['cart'];
        $status     = $_GET['status'] ?? null;
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $limit      = ADMIN_ITEMS_PER_PAGE;
        $offset     = ($page - 1) * $limit;

        $orders     = $orderModel->getAll($status, $limit, $offset);
        $totalItems = $orderModel->countAll($status);
        $totalPages = max(1, ceil($totalItems / $limit));

        $this->render('admin/orders/index', [
            'page_title'   => 'Manage Orders',
            'cart_count'   => $cart->getItemCount(),
            'orders'       => $orders,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'filter_status'=> $status,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAdmin();
        $orderModel = new Order();
        $cart       = $GLOBALS['cart'];
        $order      = $orderModel->getById((int) $id);

        if (!$order) {
            flash('error', 'Order not found.');
            redirect('/admin/orders');
            return;
        }

        $this->render('admin/orders/detail', [
            'page_title' => 'Order ' . $order['order_number'],
            'cart_count' => $cart->getItemCount(),
            'order'      => $order,
        ]);
    }

    public function updateStatus(string $id): void
    {
        $this->requireAdmin();
        $orderModel = new Order();
        $status     = $this->input('status', '');
        $result     = $orderModel->updateStatus((int) $id, $status);

        flash($result ? 'success' : 'error', $result ? 'Status updated.' : 'Invalid status.');
        redirect('/admin/orders/' . $id);
    }

    public function updatePayment(string $id): void
    {
        $this->requireAdmin();
        $orderModel = new Order();
        $status     = $this->input('payment_status', '');
        $result     = $orderModel->updatePaymentStatus((int) $id, $status);

        flash($result ? 'success' : 'error', $result ? 'Payment status updated.' : 'Invalid status.');
        redirect('/admin/orders/' . $id);
    }
}
