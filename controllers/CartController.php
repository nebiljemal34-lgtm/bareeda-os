<?php
/**
 * Bareeda Injera - Cart Controller
 *
 * Handles shopping cart operations.
 */

require_once __DIR__ . '/BaseController.php';

class CartController extends BaseController
{
    /**
     * GET /cart — View cart.
     */
    public function index(): void
    {
        $cart  = $GLOBALS['cart'];
        $items = $cart->getItems();

        $discount = $cart->getDiscount();

        $this->render('cart/index', [
            'page_title'   => 'Shopping Cart',
            'items'        => $items,
            'cart_count'   => $cart->getItemCount(),
            'subtotal'     => $cart->getSubtotal(),
            'discount'     => $discount,
            'coupon_code'  => $cart->getAppliedCouponCode(),
            'delivery_fee' => $cart->getDeliveryFee(),
            'total'        => $cart->getTotal($discount),
        ]);
    }

    /**
     * POST /cart/add — Add product to cart.
     */
    public function add(): void
    {
        $cart      = $GLOBALS['cart'];
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int) ($_POST['quantity'] ?? 1));

        $result = $cart->add($productId, $quantity);

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        redirect_back();
    }

    /**
     * POST /cart/update — Update item quantity.
     */
    public function update(): void
    {
        $cart      = $GLOBALS['cart'];
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity  = max(0, (int) ($_POST['quantity'] ?? 1));

        $result = $cart->update($productId, $quantity);

        if ($result['success']) {
            flash('success', $result['message']);
        } else {
            flash('error', $result['message']);
        }

        redirect('/cart');
    }

    /**
     * POST /cart/remove — Remove item from cart.
     */
    public function remove(): void
    {
        $cart      = $GLOBALS['cart'];
        $productId = (int) ($_POST['product_id'] ?? 0);

        $cart->remove($productId);
        flash('success', 'Item removed from cart.');

        redirect('/cart');
    }

    /**
     * POST /cart/clear — Empty the entire cart.
     */
    public function clear(): void
    {
        $cart = $GLOBALS['cart'];
        $cart->clear();
        flash('success', 'Cart cleared.');

        redirect('/cart');
    }
}
