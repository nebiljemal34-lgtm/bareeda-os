<?php
/**
 * Bareeda Injera - Subscription Controller
 *
 * Handles subscription management for customers.
 */

require_once __DIR__ . '/BaseController.php';

class SubscriptionController extends BaseController
{
    /**
     * GET /subscriptions — List user subscriptions.
     */
    public function index(): void
    {
        $this->requireAuth();

        $subModel   = new Subscription();
        $cart       = $GLOBALS['cart'];
        $userId     = currentUserId();
        $subs       = $subModel->getByUser($userId);

        // Get products for new subscription form
        $productModel = new Product();
        $products     = $productModel->getAll(true);

        $this->render('account/subscriptions', [
            'page_title'   => 'My Subscriptions',
            'subscriptions'=> $subs,
            'products'     => $products,
            'cart_count'   => $cart->getItemCount(),
        ]);
    }

    /**
     * POST /subscriptions/create — Create new subscription.
     */
    public function create(): void
    {
        $this->requireAuth();

        $subModel  = new Subscription();
        $productId = (int) ($_POST['product_id'] ?? 0);
        $frequency = $this->input('frequency', 'weekly');

        $result = $subModel->create(currentUserId(), $productId, $frequency);

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        redirect('/subscriptions');
    }

    /**
     * POST /subscriptions/{id}/pause
     */
    public function pause(string $id): void
    {
        $this->requireAuth();
        $subModel = new Subscription();
        $sub = $subModel->getById((int) $id);

        if (!$sub || (int) $sub['user_id'] !== currentUserId()) {
            flash('error', 'Subscription not found.');
            redirect('/subscriptions');
            return;
        }

        $result = $subModel->pause((int) $id);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/subscriptions');
    }

    /**
     * POST /subscriptions/{id}/resume
     */
    public function resume(string $id): void
    {
        $this->requireAuth();
        $subModel = new Subscription();
        $sub = $subModel->getById((int) $id);

        if (!$sub || (int) $sub['user_id'] !== currentUserId()) {
            flash('error', 'Subscription not found.');
            redirect('/subscriptions');
            return;
        }

        $result = $subModel->resume((int) $id);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/subscriptions');
    }

    /**
     * POST /subscriptions/{id}/cancel
     */
    public function cancel(string $id): void
    {
        $this->requireAuth();
        $subModel = new Subscription();
        $sub = $subModel->getById((int) $id);

        if (!$sub || (int) $sub['user_id'] !== currentUserId()) {
            flash('error', 'Subscription not found.');
            redirect('/subscriptions');
            return;
        }

        $result = $subModel->cancel((int) $id);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/subscriptions');
    }
}
