<?php
/**
 * Bareeda Injera - Checkout Controller
 *
 * Handles checkout page and order processing.
 */

require_once __DIR__ . '/BaseController.php';

class CheckoutController extends BaseController
{
    /**
     * GET /checkout — Show checkout page.
     */
    public function index(): void
    {
        $this->requireAuth();

        $cart  = $GLOBALS['cart'];
        $items = $cart->getItems();

        if (empty($items)) {
            flash('warning', 'Your cart is empty.');
            redirect('/products');
            return;
        }

        $user = Auth::user();
        $db   = Database::getInstance();
        $zones = $db->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY fee ASC");

        $this->render('checkout/index', [
            'page_title'   => 'Checkout',
            'items'        => $items,
            'cart_count'   => $cart->getItemCount(),
            'subtotal'     => $cart->getSubtotal(),
            'delivery_fee' => $cart->getDeliveryFee(),
            'total'        => $cart->getTotal(),
            'user'         => $user,
            'zones'        => $zones,
        ]);
    }

    /**
     * POST /checkout — Process order.
     */
    public function process(): void
    {
        $this->requireAuth();

        $cart = $GLOBALS['cart'];
        $items = $cart->getItems();

        if (empty($items)) {
            flash('error', 'Your cart is empty.');
            redirect('/products');
            return;
        }

        // Gather delivery info
        $fullName    = $this->input('full_name', '');
        $phone       = $this->input('phone', '');
        $address     = $this->input('address', '');
        $city        = $this->input('city', '');
        $notes       = $this->input('delivery_notes', '');
        $payment     = $this->input('payment_method', 'cod');

        // Validation
        if (empty($fullName) || empty($phone) || empty($address) || empty($city)) {
            flash('error', 'Please fill in all required delivery fields.');
            redirect('/checkout');
            return;
        }

        $deliveryAddress = "{$fullName}, {$phone}, {$address}, {$city}";

        // Build cart items for order
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'product_id' => (int) $item['product_id'],
                'quantity'   => (int) $item['quantity'],
            ];
        }

        $orderModel = new Order();
        $result = $orderModel->create(
            currentUserId(),
            $orderItems,
            $deliveryAddress,
            $payment,
            $notes
        );

        if ($result['success']) {
            $cart->clear();
            redirect('/order/confirmation/' . $result['order_number']);
        } else {
            flash('error', $result['message']);
            redirect('/checkout');
        }
    }
}
