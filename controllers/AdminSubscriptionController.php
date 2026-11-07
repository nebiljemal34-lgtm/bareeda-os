<?php
/**
 * Bareeda Injera - Admin Subscription Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminSubscriptionController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $subModel   = new Subscription();
        $cart       = $GLOBALS['cart'];
        $page       = max(1, (int) ($_GET['page'] ?? 1));
        $limit      = ADMIN_ITEMS_PER_PAGE;
        $offset     = ($page - 1) * $limit;

        $subs       = $subModel->getAll($limit, $offset);
        $totalItems = $subModel->count();
        $totalPages = max(1, ceil($totalItems / $limit));

        $this->render('admin/subscriptions/index', [
            'page_title'   => 'Manage Subscriptions',
            'cart_count'   => $cart->getItemCount(),
            'subscriptions'=> $subs,
            'current_page' => $page,
            'total_pages'  => $totalPages,
        ]);
    }

    public function processDue(): void
    {
        $this->requireAdmin();
        $subModel = new Subscription();
        $result   = $subModel->processDueSubscriptions();

        flash('success', "Processed: {$result['processed']}, Failed: {$result['failed']}");
        redirect('/admin/subscriptions');
    }
}
