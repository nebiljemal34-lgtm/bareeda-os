<?php
/**
 * Bareeda Injera - Admin Coupon Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminCouponController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $cart = $GLOBALS['cart'];

        $coupons = $db->query("SELECT * FROM coupons ORDER BY id DESC");

        $this->render('admin/coupons/index', [
            'page_title' => 'Manage Coupons',
            'cart_count' => $cart->getItemCount(),
            'coupons'    => $coupons,
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();

        $code       = strtoupper(trim($this->input('code', '')));
        $type       = $this->input('discount_type', 'percentage');
        $value      = (float) ($this->rawInput('discount_value', '0'));
        $minOrder   = (float) ($this->rawInput('min_order_amount', '0'));
        $maxUses    = (int) ($this->rawInput('max_uses', '0'));
        $expiresAt  = $this->rawInput('expires_at', '') ?: null;

        if (empty($code)) {
            flash('error', 'Coupon code is required.');
            redirect('/admin/coupons');
            return;
        }

        $db->insert('coupons', [
            'code'             => $code,
            'discount_type'    => $type,
            'discount_value'   => $value,
            'min_order_amount' => $minOrder,
            'max_uses'         => $maxUses,
            'is_active'        => 1,
            'expires_at'       => $expiresAt,
        ]);

        flash('success', 'Coupon created.');
        redirect('/admin/coupons');
    }

    public function toggle(string $id): void
    {
        $this->requireAdmin();
        $db = Database::getInstance();
        $db->execute("UPDATE coupons SET is_active = NOT is_active WHERE id = ?", [(int) $id]);
        flash('success', 'Coupon toggled.');
        redirect('/admin/coupons');
    }
}
