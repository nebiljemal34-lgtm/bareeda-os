<?php
/**
 * Bareeda Injera - Admin User Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminUserController extends AdminBaseController
{
    /**
     * List all customers with pagination.
     */
    public function index(): void
    {
        $page    = max(1, $this->getInt('page', 1));
        $perPage = ADMIN_ITEMS_PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        $users = $this->db->query(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                    (SELECT COALESCE(SUM(total), 0) FROM orders o WHERE o.user_id = u.id AND o.payment_status = 'paid') AS total_spent
             FROM users u
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $countRow = $this->db->fetchOne("SELECT COUNT(*) AS total FROM users");
        $total      = (int) ($countRow['total'] ?? 0);
        $totalPages = max(1, ceil($total / $perPage));

        $this->render('admin/user/index', [
            'pageTitle'  => 'Customers',
            'users'      => $users,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ]);
    }

    /**
     * Show customer detail with order history.
     */
    public function show(string $id): void
    {
        $user = $this->db->fetchOne(
            "SELECT id, email, first_name, last_name, phone, address, city, role, created_at, updated_at
             FROM users WHERE id = ?",
            [(int) $id]
        );

        if (!$user) {
            flash('error', 'Customer not found.');
            redirect('/admin/users');
        }

        // Get order history
        $orderModel = new Order();
        $orders = $orderModel->getByUser((int) $id, 50, 0);

        // Get subscriptions
        $subModel = new Subscription();
        $subscriptions = $subModel->getByUser((int) $id);

        // Stats
        $statsRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS total_orders, COALESCE(SUM(total), 0) AS total_spent
             FROM orders WHERE user_id = ? AND payment_status = 'paid'",
            [(int) $id]
        );

        $this->render('admin/user/show', [
            'pageTitle'     => $user['first_name'] . ' ' . $user['last_name'],
            'user'          => $user,
            'orders'        => $orders,
            'subscriptions' => $subscriptions,
            'stats'         => $statsRow,
        ]);
    }

    /**
     * Update user role (POST).
     */
    public function updateRole(string $id): void
    {
        $user = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [(int) $id]);
        if (!$user) {
            flash('error', 'User not found.');
            redirect('/admin/users');
        }

        $newRole = $this->post('role', 'customer');
        $allowedRoles = ['customer', 'admin'];

        if (!in_array($newRole, $allowedRoles)) {
            flash('error', 'Invalid role.');
            redirect('/admin/users/' . $id);
        }

        // Prevent removing own admin role
        if ((int) $id === currentUserId() && $newRole !== 'admin') {
            flash('error', 'You cannot change your own role.');
            redirect('/admin/users/' . $id);
        }

        $this->db->update('users', ['role' => $newRole], 'id = ?', [(int) $id]);

        flash('success', 'User role updated to ' . ucfirst($newRole) . '.');
        redirect('/admin/users/' . $id);
    }
}
