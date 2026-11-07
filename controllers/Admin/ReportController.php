<?php
/**
 * Bareeda Injera - Admin Report Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminReportController extends AdminBaseController
{
    /**
     * Sales report with daily/weekly/monthly aggregation.
     */
    public function sales(): void
    {
        $period   = trim($this->get('period', 'daily'));
        $dateFrom = trim($this->get('date_from', date('Y-m-01')));
        $dateTo   = trim($this->get('date_to', date('Y-m-d')));

        // Determine group-by format
        switch ($period) {
            case 'weekly':
                $dateFormat = '%x-W%v';
                $label      = 'Week';
                $groupBy    = "YEARWEEK(o.created_at)";
                break;
            case 'monthly':
                $dateFormat = '%Y-%m';
                $label      = 'Month';
                $groupBy    = "DATE_FORMAT(o.created_at, '%Y-%m')";
                break;
            default:
                $dateFormat = '%Y-%m-%d';
                $label      = 'Day';
                $groupBy    = "DATE(o.created_at)";
                $period     = 'daily';
        }

        // Summary stats for the date range
        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS order_count,
                    COALESCE(SUM(total), 0) AS total_sales,
                    COALESCE(AVG(total), 0) AS avg_order_value
             FROM orders
             WHERE DATE(created_at) BETWEEN ? AND ?
               AND status != 'cancelled'",
            [$dateFrom, $dateTo]
        );

        // Breakdown by period
        $breakdown = $this->db->query(
            "SELECT DATE_FORMAT(o.created_at, ?) AS period_label,
                    {$groupBy} AS period_sort,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(o.total), 0) AS total_sales,
                    COALESCE(AVG(o.total), 0) AS avg_order_value
             FROM orders o
             WHERE DATE(o.created_at) BETWEEN ? AND ?
               AND o.status != 'cancelled'
             GROUP BY period_sort
             ORDER BY period_sort ASC",
            [$dateFormat, $dateFrom, $dateTo]
        );

        $this->render('admin/report/sales', [
            'pageTitle' => 'Sales Report',
            'summary'   => $summary,
            'breakdown' => $breakdown,
            'period'    => $period,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'label'     => $label,
        ]);
    }

    /**
     * Product performance report.
     */
    public function products(): void
    {
        // Top selling products
        $topProducts = $this->db->query(
            "SELECT p.id, p.name, p.image_url,
                    COALESCE(SUM(oi.quantity), 0) AS total_qty_sold,
                    COALESCE(SUM(oi.total_price), 0) AS total_revenue
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
             WHERE p.is_active = 1
             GROUP BY p.id
             ORDER BY total_revenue DESC
             LIMIT 20"
        );

        // Low stock products
        $productModel = new Product();
        $lowStock = $productModel->getLowStock(15);

        // Products never sold
        $neverSold = $this->db->query(
            "SELECT p.id, p.name, p.stock_quantity, p.price
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             WHERE oi.id IS NULL AND p.is_active = 1
             ORDER BY p.created_at DESC
             LIMIT 10"
        );

        $this->render('admin/report/products', [
            'pageTitle'   => 'Product Report',
            'topProducts' => $topProducts,
            'lowStock'    => $lowStock,
            'neverSold'   => $neverSold,
        ]);
    }

    /**
     * Customer analytics report.
     */
    public function customers(): void
    {
        // Total customers
        $totalCustomers = $this->db->fetchOne("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer'");

        // New customers this month
        $monthStart = date('Y-m-01');
        $newThisMonth = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer' AND created_at >= ?",
            [$monthStart]
        );

        // Returning customer rate (customers with more than 1 order)
        $returning = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM (
                SELECT user_id FROM orders GROUP BY user_id HAVING COUNT(*) > 1
            ) AS sub"
        );

        $totalWithOrders = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_id) AS cnt FROM orders"
        );

        $returningRate = 0;
        if ($totalWithOrders && (int) $totalWithOrders['cnt'] > 0) {
            $returningRate = round(((int) ($returning['cnt'] ?? 0) / (int) $totalWithOrders['cnt']) * 100, 1);
        }

        // Top customers by total spent
        $topCustomers = $this->db->query(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                    COUNT(o.id) AS total_orders,
                    COALESCE(SUM(o.total), 0) AS total_spent
             FROM users u
             INNER JOIN orders o ON u.id = o.user_id
             WHERE o.status != 'cancelled'
             GROUP BY u.id
             ORDER BY total_spent DESC
             LIMIT 20"
        );

        $this->render('admin/report/customers', [
            'pageTitle'      => 'Customer Report',
            'totalCustomers' => (int) ($totalCustomers['cnt'] ?? 0),
            'newThisMonth'   => (int) ($newThisMonth['cnt'] ?? 0),
            'returningRate'  => $returningRate,
            'topCustomers'   => $topCustomers,
        ]);
    }
}
