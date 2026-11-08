<?php

class PaymentGateway
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function providers(): array
    {
        return [
            'cbe' => ['name' => 'Commercial Bank of Ethiopia', 'short_name' => 'CBE', 'channel' => 'bank'],
            'awash' => ['name' => 'Awash Bank', 'short_name' => 'Awash', 'channel' => 'bank'],
            'dashen' => ['name' => 'Dashen Bank', 'short_name' => 'Dashen', 'channel' => 'bank'],
            'abyssinia' => ['name' => 'Bank of Abyssinia', 'short_name' => 'Abyssinia', 'channel' => 'bank'],
            'ebirr' => ['name' => 'eBirr Wallet', 'short_name' => 'eBirr', 'channel' => 'wallet'],
            'telebirr' => ['name' => 'Telebirr', 'short_name' => 'Telebirr', 'channel' => 'wallet'],
            'cbe_birr' => ['name' => 'CBE Birr', 'short_name' => 'CBE Birr', 'channel' => 'wallet'],
        ];
    }

    public static function paymentMethodForProvider(string $provider): string
    {
        $metadata = self::providers()[$provider] ?? null;
        if (!$metadata) {
            return '';
        }
        if ($provider === 'ebirr') {
            return 'ebirr';
        }
        return $metadata['channel'] === 'bank' ? 'bank_transfer' : 'mobile_money';
    }

    public static function onlineProviders(): array
    {
        if (PAYMENT_MODE !== 'simulation' || (defined('APP_ENV') && APP_ENV === 'production')) {
            return [];
        }
        return self::providers();
    }

    public function createForOrder(int $orderId, string $provider): array
    {
        $providers = self::onlineProviders();
        if (!isset($providers[$provider])) {
            throw new RuntimeException('Online payments are not available for this provider.');
        }

        $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order || $order['payment_status'] !== 'pending' || $order['status'] === 'cancelled') {
            throw new RuntimeException('This order cannot accept a new payment.');
        }

        $existing = $this->db->fetchOne(
            "SELECT * FROM payment_transactions WHERE order_id = ? AND provider = ? AND status = 'pending' ORDER BY id DESC LIMIT 1",
            [$orderId, $provider]
        );
        if ($existing) {
            return $this->getByPublicId($existing['public_id']);
        }

        $publicId = $this->uuid();
        $this->db->insert('payment_transactions', [
            'public_id' => $publicId,
            'order_id' => $orderId,
            'provider' => $provider,
            'channel' => $providers[$provider]['channel'],
            'mode' => 'simulation',
            'status' => 'pending',
            'amount' => $order['total'],
            'currency' => CURRENCY_CODE,
        ]);

        return $this->getByPublicId($publicId);
    }

    public function getByPublicId(string $publicId): ?array
    {
        return $this->db->fetchOne(
            "SELECT pt.*, o.order_number, o.user_id, o.status AS order_status, o.payment_status AS order_payment_status
             FROM payment_transactions pt
             INNER JOIN orders o ON o.id = pt.order_id
             WHERE pt.public_id = ?",
            [$publicId]
        );
    }

    public function getByOrder(int $orderId): array
    {
        return $this->db->query(
            "SELECT * FROM payment_transactions WHERE order_id = ? ORDER BY id DESC",
            [$orderId]
        );
    }

    public function getEvents(int $transactionId): array
    {
        return $this->db->query(
            "SELECT event_id, event_type, processing_result, signature_valid, created_at
             FROM payment_events WHERE transaction_id = ? ORDER BY id DESC LIMIT 20",
            [$transactionId]
        );
    }

    public function simulate(string $publicId, string $outcome): array
    {
        if (PAYMENT_MODE !== 'simulation' || (defined('APP_ENV') && APP_ENV === 'production')) {
            throw new RuntimeException('Payment simulation is disabled.');
        }

        $transaction = $this->getByPublicId($publicId);
        if (!$transaction || $transaction['mode'] !== 'simulation') {
            throw new RuntimeException('Simulation transaction not found.');
        }
        if ($transaction['status'] === 'paid') {
            return ['success' => true, 'status' => 'paid', 'duplicate' => false];
        }
        if (!in_array($outcome, ['success', 'failed'], true)) {
            throw new InvalidArgumentException('Invalid simulation outcome.');
        }

        $payload = [
            'event_id' => 'evt_' . bin2hex(random_bytes(12)),
            'transaction_id' => $publicId,
            'status' => $outcome === 'success' ? 'succeeded' : 'failed',
            'amount' => number_format((float) $transaction['amount'], 2, '.', ''),
            'currency' => $transaction['currency'],
            'provider_reference' => 'SIM-' . strtoupper(bin2hex(random_bytes(8))),
            'failure_reason' => $outcome === 'failed' ? 'Simulated customer cancellation' : null,
        ];
        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $rawBody, PAYMENT_WEBHOOK_SECRET);

        return $this->handleCallback($transaction['provider'], $rawBody, $signature);
    }

    public function handleCallback(string $provider, string $rawBody, string $signature): array
    {
        if (PAYMENT_WEBHOOK_SECRET === '') {
            return ['success' => false, 'code' => 503, 'message' => 'Payment callbacks are not configured.'];
        }

        $expected = hash_hmac('sha256', $rawBody, PAYMENT_WEBHOOK_SECRET);
        if ($signature === '' || !hash_equals($expected, $signature)) {
            return ['success' => false, 'code' => 401, 'message' => 'Invalid payment signature.'];
        }

        if (!isset(self::providers()[$provider])) {
            return ['success' => false, 'code' => 404, 'message' => 'Unknown payment provider.'];
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return ['success' => false, 'code' => 400, 'message' => 'Invalid JSON payload.'];
        }

        foreach (['event_id', 'transaction_id', 'status', 'amount', 'currency'] as $field) {
            if (!isset($payload[$field]) || !is_scalar($payload[$field])) {
                return ['success' => false, 'code' => 422, 'message' => 'Missing or invalid callback fields.'];
            }
        }
        if (!in_array($payload['status'], ['succeeded', 'failed'], true)) {
            return ['success' => false, 'code' => 422, 'message' => 'Unsupported payment status.'];
        }

        $eventId = substr((string) $payload['event_id'], 0, 120);
        $publicId = (string) $payload['transaction_id'];
        $payloadHash = hash('sha256', $rawBody);

        return $this->db->transaction(function ($db) use ($provider, $payload, $rawBody, $payloadHash, $eventId, $publicId) {
            $transaction = $db->fetchOne(
                "SELECT pt.*, o.status AS order_status, o.payment_status AS order_payment_status
                 FROM payment_transactions pt
                 INNER JOIN orders o ON o.id = pt.order_id
                 WHERE pt.public_id = ? FOR UPDATE",
                [$publicId]
            );
            if (!$transaction || $transaction['provider'] !== $provider) {
                return ['success' => false, 'code' => 404, 'message' => 'Payment transaction not found.'];
            }

            $existingEvent = $db->fetchOne("SELECT * FROM payment_events WHERE event_id = ? FOR UPDATE", [$eventId]);
            if ($existingEvent) {
                if (!hash_equals($existingEvent['payload_hash'], $payloadHash)) {
                    return ['success' => false, 'code' => 409, 'message' => 'Event ID replayed with different content.'];
                }
                return ['success' => true, 'code' => 200, 'status' => $transaction['status'], 'duplicate' => true];
            }

            $eventType = $payload['status'] === 'succeeded' ? 'payment.succeeded' : 'payment.failed';
            $eventDbId = $db->insert('payment_events', [
                'event_id' => $eventId,
                'transaction_id' => $transaction['id'],
                'provider' => $provider,
                'event_type' => $eventType,
                'payload_hash' => $payloadHash,
                'payload' => $rawBody,
                'signature_valid' => 1,
                'processing_result' => 'received',
            ]);

            $result = 'processed';
            $amountMatches = abs((float) $payload['amount'] - (float) $transaction['amount']) < 0.001;
            $currencyMatches = strtoupper((string) $payload['currency']) === $transaction['currency'];
            if (!$amountMatches || !$currencyMatches) {
                $db->update('payment_events', ['processing_result' => 'amount_mismatch'], 'id = ?', [$eventDbId]);
                return ['success' => false, 'code' => 422, 'message' => 'Payment amount or currency does not match.'];
            }

            if ($transaction['status'] === 'paid') {
                $result = 'already_paid';
            } elseif ($payload['status'] === 'succeeded') {
                $reference = substr((string) ($payload['provider_reference'] ?? ''), 0, 120);
                if ($reference === '') {
                    $db->update('payment_events', ['processing_result' => 'missing_reference'], 'id = ?', [$eventDbId]);
                    return ['success' => false, 'code' => 422, 'message' => 'Provider reference is required.'];
                }

                $referenceOwner = $db->fetchOne(
                    "SELECT id FROM payment_transactions WHERE provider = ? AND provider_reference = ? AND id != ?",
                    [$provider, $reference, $transaction['id']]
                );
                if ($referenceOwner) {
                    $db->update('payment_events', ['processing_result' => 'reference_conflict'], 'id = ?', [$eventDbId]);
                    return ['success' => false, 'code' => 409, 'message' => 'Provider reference is already in use.'];
                }

                $db->execute(
                    "UPDATE payment_transactions
                     SET status = 'paid', provider_reference = ?, failure_reason = NULL, verified_at = NOW()
                     WHERE id = ?",
                    [$reference, $transaction['id']]
                );

                if ($transaction['order_status'] !== 'cancelled') {
                    $db->update('orders', ['payment_status' => 'paid'], 'id = ?', [$transaction['order_id']]);
                    if ($transaction['order_payment_status'] !== 'paid') {
                        $db->insert('order_events', [
                            'order_id' => $transaction['order_id'],
                            'actor_user_id' => null,
                            'event_type' => 'payment_automatic',
                            'from_status' => $transaction['order_payment_status'],
                            'to_status' => 'paid',
                            'note' => $provider . ' callback verified: ' . $reference,
                        ]);
                    }
                    $result = 'paid';
                } else {
                    $result = 'paid_cancelled_order';
                }
            } else {
                $db->update('payment_transactions', [
                    'status' => 'failed',
                    'failure_reason' => substr((string) ($payload['failure_reason'] ?? 'Payment failed'), 0, 255),
                ], 'id = ?', [$transaction['id']]);
                $result = 'failed';
            }

            $db->update('payment_events', ['processing_result' => $result], 'id = ?', [$eventDbId]);
            $fresh = $db->fetchOne("SELECT status FROM payment_transactions WHERE id = ?", [$transaction['id']]);
            return ['success' => true, 'code' => 200, 'status' => $fresh['status'], 'result' => $result, 'duplicate' => false];
        });
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
