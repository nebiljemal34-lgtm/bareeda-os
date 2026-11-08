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
        $cart  = $GLOBALS['cart'];
        $items = $cart->getItems();

        if (empty($items)) {
            flash('warning', 'Your cart is empty.');
            redirect('/products');
            return;
        }

        $user = $GLOBALS['auth']->user();
        $db   = Database::getInstance();
        $zones = $db->query("SELECT * FROM delivery_zones WHERE is_active = 1 ORDER BY fee ASC");
        if (empty($zones)) {
            flash('error', 'Online ordering is temporarily unavailable because no delivery zones are active.');
            redirect('/products');
        }

        $selectedZoneId = (int) old('delivery_zone_id', 0);
        if ($selectedZoneId === 0) {
            foreach ($zones as $zone) {
                if ($zone['zone_name'] === ($user['city'] ?? '')) {
                    $selectedZoneId = (int) $zone['id'];
                    break;
                }
            }
        }
        if ($selectedZoneId === 0) {
            $selectedZoneId = (int) $zones[0]['id'];
        }
        $discount = $cart->getDiscount();
        $isGuest  = !isLoggedIn();

        $this->render('checkout/index', [
            'page_title'   => 'Checkout',
            'items'        => $items,
            'cart_count'   => $cart->getItemCount(),
            'subtotal'     => $cart->getSubtotal(),
            'discount'     => $discount,
            'delivery_fee' => $cart->getDeliveryFee($selectedZoneId),
            'total'        => $cart->getTotal($discount, $selectedZoneId),
            'total_pieces' => $cart->getTotalPieces(),
            'free_delivery_min_pieces' => freeDeliveryMinPieces(),
            'user'         => $user,
            'is_guest'     => $isGuest,
            'zones'        => $zones,
            'selected_zone_id' => $selectedZoneId,
            'accepting_orders' => setting('accepting_orders', '1') === '1',
            'payment_providers' => $isGuest ? [] : PaymentGateway::onlineProviders(),
        ]);
    }

    /**
     * POST /checkout — Process order.
     */
    public function process(): void
    {
        if (setting('accepting_orders', '1') !== '1') {
            flash('error', 'Online ordering is temporarily paused. Please contact us for assistance.');
            redirect('/products');
        }

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
        $email       = $this->input('email', '');
        $address     = $this->input('address', '');
        $zoneId      = (int) $this->rawInput('delivery_zone_id', 0);
        $notes       = $this->input('delivery_notes', '');
        $payment     = $this->input('payment_method', 'cod');
        $provider    = '';
        $isGuest     = !isLoggedIn();

        // Validation
        if (empty($fullName) || empty($phone) || empty($address) || $zoneId < 1) {
            flash('error', 'Please fill in all required delivery fields.');
            redirect('/checkout');
            return;
        }

        if ($email !== '' && !is_valid_email($email)) {
            flash('error', 'Please enter a valid email address or leave it blank.');
            redirect('/checkout');
            return;
        }

        // Guests check out with cash on delivery; online payment needs an account.
        if ($isGuest && $payment !== 'cod') {
            flash('error', 'Please use Cash on Delivery to place your order.');
            redirect('/checkout');
            return;
        }

        $zone = Database::getInstance()->fetchOne("SELECT zone_name FROM delivery_zones WHERE id = ? AND is_active = 1", [$zoneId]);
        if (!$zone) {
            flash('error', 'The selected delivery zone is not available.');
            redirect('/checkout');
            return;
        }
        $city = $zone['zone_name'];

        if ($payment !== 'cod') {
            $providers = PaymentGateway::onlineProviders();
            if (!isset($providers[$payment])) {
                flash('error', 'The selected online payment method is not available.');
                redirect('/checkout');
                return;
            }
            $provider = $payment;
            $payment = PaymentGateway::paymentMethodForProvider($provider);
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
            $notes,
            $cart->getAppliedCouponCode(),
            $zoneId,
            ['name' => $fullName, 'phone' => $phone, 'email' => $email]
        );

        if (!$result['success']) {
            flash('error', $result['message']);
            redirect('/checkout');
        }

        $cart->clear();
        unset($_SESSION['applied_coupon']);

        // Let a guest revisit their own confirmation page during this session.
        if ($isGuest) {
            $_SESSION['guest_order_tokens'][$result['order_number']] = (int) $result['order_id'];
        }

        TelegramNotifier::notify('new_order', [
            'order' => $orderModel->getById((int) $result['order_id']),
            'lines' => array_map(fn(array $item) => [
                'name'       => $item['name'],
                'quantity'   => (int) $item['quantity'],
                'pieces'     => packPieces($item),
                'line_total' => (float) $item['line_total'],
            ], $items),
            'guest' => $isGuest,
        ]);

        if ($provider !== '') {
            try {
                $payments = new PaymentGateway();
                $transaction = $payments->createForOrder((int) $result['order_id'], $provider);
                redirect('/payment/' . $transaction['public_id']);
            } catch (Throwable $e) {
                flash('error', 'Your order was saved, but payment initialization failed. Please contact support with order ' . $result['order_number'] . '.');
                redirect('/orders/' . $result['order_id']);
            }
        }

        redirect('/order/confirmation/' . $result['order_number']);
    }
}
