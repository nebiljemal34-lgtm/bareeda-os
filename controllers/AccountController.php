<?php
/**
 * Bareeda Injera - Account Controller
 *
 * Handles account dashboard, profile, and password management.
 */

require_once __DIR__ . '/BaseController.php';

class AccountController extends BaseController
{
    /**
     * GET /account — Dashboard.
     */
    public function index(): void
    {
        $this->requireAuth();

        $cart       = $GLOBALS['cart'];
        $user       = $GLOBALS['auth']->user();
        $orderModel = new Order();
        $subModel   = new Subscription();

        $recentOrders     = $orderModel->getByUser((int) $user['id'], 5, 0);
        $totalOrders      = $orderModel->countByUser((int) $user['id']);
        $activeSubscriptions = $subModel->getActiveByUser((int) $user['id']);

        $this->render('account/index', [
            'page_title'          => 'My Account',
            'user'                => $user,
            'cart_count'          => $cart->getItemCount(),
            'recent_orders'       => $recentOrders,
            'total_orders'        => $totalOrders,
            'active_subscriptions'=> $activeSubscriptions,
        ]);
    }

    /**
     * POST /account/update — Update profile.
     */
    public function update(): void
    {
        $this->requireAuth();

        $user      = $GLOBALS['auth']->user();
        $firstName = $this->input('first_name', '');
        $lastName  = $this->input('last_name', '');
        $phone     = $this->input('phone', '');
        $address   = $this->input('address', '');
        $city      = $this->input('city', '');

        if (empty($firstName) || empty($lastName)) {
            flash('error', 'First name and last name are required.');
            redirect('/account');
            return;
        }

        $db = Database::getInstance();
        $db->update('users', [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'address'    => $address,
            'city'       => $city,
        ], 'id = ?', [(int) $user['id']]);

        // Refresh session
        unset($_SESSION['user']);
        flash('success', 'Profile updated successfully.');
        redirect('/account');
    }

    /**
     * POST /account/change-password — Change password.
     */
    public function changePassword(): void
    {
        $this->requireAuth();

        $currentPassword = $this->rawInput('current_password', '');
        $newPassword     = $this->rawInput('new_password', '');
        $confirmPassword = $this->rawInput('confirm_password', '');

        $user = $GLOBALS['auth']->user();
        $db   = Database::getInstance();
        $full = $db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [(int) $user['id']]);

        if (!password_verify($currentPassword, $full['password_hash'])) {
            flash('error', 'Current password is incorrect.');
            redirect('/account');
            return;
        }

        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            flash('error', 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.');
            redirect('/account');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', 'New passwords do not match.');
            redirect('/account');
            return;
        }

        $auth = $GLOBALS['auth'];
        $auth->updatePassword((int) $user['id'], $newPassword);

        flash('success', 'Password changed successfully.');
        redirect('/account');
    }
}
