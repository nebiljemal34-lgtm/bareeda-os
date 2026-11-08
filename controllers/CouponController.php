<?php
/**
 * Bareeda Injera - Coupon Controller
 *
 * Handles coupon application and removal.
 */

require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController
{
    /**
 * POST /coupon/apply
     */
    public function apply(): void
    {
        $code = strtoupper(trim($this->input('coupon_code', '')));

        if (empty($code)) {
            flash('error', 'Please enter a coupon code.');
            redirect_back();
            return;
        }

        $db = Database::getInstance();
        $coupon = $db->fetchOne(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
            [$code]
        );

        if (!$coupon) {
            flash('error', 'Invalid or expired coupon code.');
            redirect_back();
            return;
        }
        if ($GLOBALS['cart']->getSubtotal() < (float) $coupon['min_order_amount']) {
            flash('error', 'This coupon requires a minimum subtotal of ' . formatPrice((float) $coupon['min_order_amount']) . '.');
            redirect_back();
            return;
        }
        if ((int) $coupon['max_uses'] > 0 && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            flash('error', 'This coupon has reached its usage limit.');
            redirect_back();
            return;
        }

        $_SESSION['applied_coupon'] = $coupon;
        flash('success', 'Coupon "' . htmlspecialchars($coupon['code']) . '" applied!');
        redirect_back();
    }

    /**
     * POST /coupon/remove
     */
    public function remove(): void
    {
        unset($_SESSION['applied_coupon']);
        flash('success', 'Coupon removed.');
        redirect_back();
    }
}
