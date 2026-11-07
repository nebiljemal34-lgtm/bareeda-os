<?php
/**
 * Bareeda Injera - Admin Dashboard Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminDashboardController extends AdminBaseController
{
    /**
     * Dashboard overview with key stats.
     */
    public function index(): void
    {
        $orderModel = new Order();
        $productModel = new Product();
        $subscriptionModel = new Subscription();

        // Core stats
        $stats = $orderModel->getStats();

        // Total orders count
        $totalOrders = $orderModel->countAll();

        // Active subscriptions count
        $activeSubscriptions = $subscriptionModel->count('active');

        // Recent orders (last 10)
        $recentOrders = $orderModel->getAll(null, 10, 0);

        // Low stock products (threshold: 10)
        $lowStockProducts = $productModel->getLowStock(10);

        $this->render('admin/dashboard/index', [
            'pageTitle'           => 'Dashboard',
            'stats'               => $stats,
            'totalOrders'         => $totalOrders,
            'activeSubscriptions' => $activeSubscriptions,
            'recentOrders'        => $recentOrders,
            'lowStockProducts'    => $lowStockProducts,
        ]);
    }
}
