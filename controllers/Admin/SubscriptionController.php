<?php
/**
 * Bareeda Injera - Admin Subscription Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminSubscriptionController extends AdminBaseController
{
    private $subscriptionModel;

    public function __construct()
    {
        parent::__construct();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * List all subscriptions with pagination.
     */
    public function index(): void
    {
        $page    = max(1, $this->getInt('page', 1));
        $perPage = ADMIN_ITEMS_PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        $subscriptions = $this->subscriptionModel->getAll($perPage, $offset);
        $total         = $this->subscriptionModel->count();
        $totalPages    = max(1, ceil($total / $perPage));

        $this->render('admin/subscription/index', [
            'pageTitle'     => 'Subscriptions',
            'subscriptions' => $subscriptions,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'total'         => $total,
        ]);
    }

    /**
     * Show subscription detail.
     */
    public function show(string $id): void
    {
        $subscription = $this->subscriptionModel->getById((int) $id);

        if (!$subscription) {
            flash('error', 'Subscription not found.');
            redirect('/admin/subscriptions');
        }

        // Get customer info
        $customer = $this->db->fetchOne(
            "SELECT id, email, first_name, last_name, phone, address, city FROM users WHERE id = ?",
            [$subscription['user_id']]
        );

        // Get delivery schedule
        $deliveries = $this->db->query(
            "SELECT * FROM subscription_deliveries WHERE subscription_id = ? ORDER BY scheduled_date DESC",
            [(int) $id]
        );

        $this->render('admin/subscription/show', [
            'pageTitle'    => 'Subscription #' . $id,
            'subscription' => $subscription,
            'customer'     => $customer,
            'deliveries'   => $deliveries,
        ]);
    }

    /**
     * Update subscription status (POST) - pause/resume/cancel.
     */
    public function updateStatus(string $id): void
    {
        $action = $this->post('action', '');
        $subId  = (int) $id;

        switch ($action) {
            case 'pause':
                $result = $this->subscriptionModel->pause($subId);
                break;
            case 'resume':
                $result = $this->subscriptionModel->resume($subId);
                break;
            case 'cancel':
                $result = $this->subscriptionModel->cancel($subId);
                break;
            default:
                flash('error', 'Invalid action.');
                redirect('/admin/subscriptions/' . $id);
                return;
        }

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        redirect('/admin/subscriptions/' . $id);
    }
}
