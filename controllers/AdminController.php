<?php
/**
 * Bareeda Injera - Admin Controller
 *
 * Admin dashboard overview.
 */

require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController
{
    public function dashboard(): void
    {
        $this->requireAdmin();

        $orderModel = new Order();
        $subModel   = new Subscription();
        $product    = new Product();
        $cart       = $GLOBALS['cart'];

        $stats       = $orderModel->getStats();
        $recentOrders = $orderModel->getAll(null, 10, 0);
        $lowStock    = $product->getLowStock(10);
        $subCount    = $subModel->count('active');

        $this->render('admin/dashboard', [
            'page_title'    => 'Admin Dashboard',
            'cart_count'    => $cart->getItemCount(),
            'stats'         => $stats,
            'recent_orders' => $recentOrders,
            'low_stock'     => $lowStock,
            'sub_count'     => $subCount,
        ]);
    }
}
