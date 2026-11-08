<?php

require_once __DIR__ . '/BaseController.php';

class PaymentController extends BaseController
{
    private $payments;

    public function __construct()
    {
        $this->payments = new PaymentGateway();
    }

    public function show(string $publicId): void
    {
        $transaction = $this->ownedTransaction($publicId);
        $providers = PaymentGateway::providers();

        $this->render('payment/show', [
            'page_title' => 'Payment ' . $transaction['order_number'],
            'transaction' => $transaction,
            'provider' => $providers[$transaction['provider']],
            'events' => $this->payments->getEvents((int) $transaction['id']),
            'cart_count' => $GLOBALS['cart']->getItemCount(),
            'simulation' => $transaction['mode'] === 'simulation',
        ]);
    }

    public function simulate(string $publicId): void
    {
        $transaction = $this->ownedTransaction($publicId);
        $outcome = $this->rawInput('outcome', '');

        try {
            $result = $this->payments->simulate($publicId, $outcome);
            if ($result['success'] && $result['status'] === 'paid') {
                flash('success', 'Payment verified automatically. Your order is marked as paid.');
            } elseif ($result['success']) {
                flash('error', 'The simulated payment failed. You can retry securely.');
            } else {
                flash('error', $result['message'] ?? 'Payment could not be verified.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('/payment/' . $transaction['public_id']);
    }

    public function retry(string $publicId): void
    {
        $transaction = $this->ownedTransaction($publicId);
        if (!in_array($transaction['status'], ['failed', 'expired'], true)) {
            redirect('/payment/' . $publicId);
        }

        try {
            $newTransaction = $this->payments->createForOrder((int) $transaction['order_id'], $transaction['provider']);
            redirect('/payment/' . $newTransaction['public_id']);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/payment/' . $publicId);
        }
    }

    public function status(string $publicId): void
    {
        $transaction = $this->ownedTransaction($publicId);
        $this->json([
            'status' => $transaction['status'],
            'order_payment_status' => $transaction['order_payment_status'],
            'provider_reference' => $transaction['provider_reference'],
            'verified_at' => $transaction['verified_at'],
        ]);
    }

    public function callback(string $provider): void
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $signature = $_SERVER['HTTP_X_PAYMENT_SIGNATURE'] ?? '';
        $result = $this->payments->handleCallback($provider, $rawBody, $signature);
        $this->json($result, $result['code'] ?? 200);
    }

    private function ownedTransaction(string $publicId): array
    {
        $this->requireAuth();
        $transaction = $this->payments->getByPublicId($publicId);
        if (!$transaction || (int) $transaction['user_id'] !== currentUserId()) {
            flash('error', 'Payment transaction not found.');
            redirect('/orders');
        }
        return $transaction;
    }
}
