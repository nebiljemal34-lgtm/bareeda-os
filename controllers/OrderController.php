<?php
/**
 * Bareeda Injera - Order Controller
 *
 * Handles order listing, detail, and confirmation pages.
 */

require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController
{
    /**
     * GET /orders — Order history.
     */
    public function index(): void
    {
        $this->requireAuth();

        $orderModel = new Order();
        $cart       = $GLOBALS['cart'];
        $userId     = currentUserId();
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $limit      = 15;
        $offset     = ($page - 1) * $limit;

        $orders     = $orderModel->getByUser($userId, $limit, $offset);
        $totalItems = $orderModel->countByUser($userId);
        $totalPages = max(1, ceil($totalItems / $limit));

        $this->render('account/orders', [
            'page_title'   => 'My Orders',
            'orders'       => $orders,
            'cart_count'   => $cart->getItemCount(),
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'total_items'  => $totalItems,
        ]);
    }

    /**
     * GET /orders/{id} — Single order detail.
     */
    public function show(string $id): void
    {
        $this->requireAuth();

        $orderModel = new Order();
        $cart       = $GLOBALS['cart'];
        $order      = $orderModel->getById((int) $id);

        if (!$order || (int) $order['user_id'] !== currentUserId()) {
            flash('error', 'Order not found.');
            redirect('/orders');
            return;
        }

        $this->render('account/order-detail', [
            'page_title' => 'Order ' . $order['order_number'],
            'order'      => $order,
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * GET /order/confirmation/{orderNumber} — Order confirmation.
     */
    public function confirmation(string $orderNumber): void
    {
        $orderModel = new Order();
        $cart       = $GLOBALS['cart'];
        $order      = $orderModel->getByOrderNumber($orderNumber);

        $isGuestOrder = $order && $order['user_id'] === null;
        $guestTokenOk = $order && isset($_SESSION['guest_order_tokens'][$orderNumber])
            && (int) $_SESSION['guest_order_tokens'][$orderNumber] === (int) $order['id'];
        $ownerOk = $order && $order['user_id'] !== null && isLoggedIn()
            && (int) $order['user_id'] === currentUserId();

        if (!$order || !($guestTokenOk || $ownerOk)) {
            flash('error', 'Order not found.');
            redirect(isLoggedIn() ? '/orders' : '/products');
            return;
        }

        $this->render('checkout/success', [
            'page_title'     => 'Order Confirmed',
            'order'          => $order,
            'is_guest_order' => $isGuestOrder,
            'cart_count'     => 0,
        ]);
    }
}
