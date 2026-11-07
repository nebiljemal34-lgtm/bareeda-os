<?php
/**
 * Bareeda Injera - Admin User Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminUserController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $cart = $GLOBALS['cart'];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = ADMIN_ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $users = $db->query(
            "SELECT id, email, first_name, last_name, phone, role, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
        $countRow = $db->fetchOne("SELECT COUNT(*) AS total FROM users");
        $totalItems = (int) $countRow['total'];
        $totalPages = max(1, ceil($totalItems / $limit));

        $this->render('admin/users/index', [
            'page_title'   => 'Manage Users',
            'cart_count'   => $cart->getItemCount(),
            'users'        => $users,
            'current_page' => $page,
            'total_pages'  => $totalPages,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $cart = $GLOBALS['cart'];

        $user = $db->fetchOne(
            "SELECT id, email, first_name, last_name, phone, address, city, role, created_at, updated_at FROM users WHERE id = ?",
            [(int) $id]
        );

        if (!$user) {
            flash('error', 'User not found.');
            redirect('/admin/users');
            return;
        }

        $orderModel = new Order();
        $orders = $orderModel->getByUser((int) $id, 20, 0);
        $subModel = new Subscription();
        $subs = $subModel->getByUser((int) $id);

        $this->render('admin/users/detail', [
            'page_title' => 'User: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']),
            'cart_count' => $cart->getItemCount(),
            'user'       => $user,
            'orders'     => $orders,
            'subs'       => $subs,
        ]);
    }
}
