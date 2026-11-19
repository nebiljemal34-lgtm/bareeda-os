<?php
/**
 * Bareeda Injera - ERP client
 *
 * Server-side HTTP access to the ERP verification API. The browser never talks
 * to the ERP: the website forwards a validated token and returns the JSON or
 * PNG bytes. cURL is used when the extension is present, otherwise PHP streams.
 */

class ErpClient
{
    private const CONNECT_TIMEOUT = 3;
    private const JSON_TIMEOUT    = 6;
    private const QR_TIMEOUT      = 8;
    private const MAX_BYTES       = 2097152; // 2 MB

    /** @var string */
    private $base;

    public function __construct(?string $base = null)
    {
        $configured = $base !== null ? $base : ERP_API_BASE_URL;

        // Accept either the origin (http://host:3001) or the API root
        // (http://host:3001/api) in configuration.
        $configured = rtrim(trim($configured), '/');
        if (substr($configured, -4) === '/api') {
            $configured = substr($configured, 0, -4);
        }

        $this->base = $configured;
    }

    /**
     * Fetch the public verification state for a token.
     *
     * @return array{ok: bool, status: int, data: array|null, error: string}
     */
    public function verification(string $token): array
    {
        $response = $this->get('/api/verify/' . rawurlencode($token), self::JSON_TIMEOUT);

        if (!$response['ok']) {
            return ['ok' => false, 'status' => $response['status'], 'data' => null, 'error' => $response['error']];
        }

        $payload = json_decode($response['body'], true);
        if (!is_array($payload) || !isset($payload['state']) || !is_string($payload['state'])) {
            return ['ok' => false, 'status' => 502, 'data' => null, 'error' => 'Malformed verification response'];
        }

        $allowed = ['unknown', 'active', 'revoked', 'altered'];
        if (!in_array($payload['state'], $allowed, true)) {
            return ['ok' => false, 'status' => 502, 'data' => null, 'error' => 'Unexpected verification state'];
        }

        return ['ok' => true, 'status' => 200, 'data' => $this->normalize($payload), 'error' => ''];
    }

    /**
     * Fetch the QR PNG for a token. The ERP is the only place the code is
     * rendered (EC level M, 720px, margin 1, dark #00283f); the website just
     * forwards the bytes.
     *
     * @return array{ok: bool, status: int, body: string|null, error: string}
     */
    public function qr(string $token): array
    {
        $response = $this->get('/api/verify/' . rawurlencode($token) . '/qr', self::QR_TIMEOUT);

        if (!$response['ok']) {
            return ['ok' => false, 'status' => $response['status'], 'body' => null, 'error' => $response['error']];
        }

        // Only pass through a real PNG; never echo an unexpected payload.
        if (strncmp($response['body'], "\x89PNG\r\n\x1A\n", 8) !== 0) {
            return ['ok' => false, 'status' => 502, 'body' => null, 'error' => 'Malformed QR response'];
        }

        return ['ok' => true, 'status' => 200, 'body' => $response['body'], 'error' => ''];
    }

    /**
     * Shape the ERP payload into the fields the page is allowed to display.
     */
    private function normalize(array $payload): array
    {
        $text = static function ($value): string {
            return is_scalar($value) ? (string) $value : '';
        };

        $attachments = [];
        foreach ((array) ($payload['attachments'] ?? []) as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $attachments[] = [
                'label'     => $text($attachment['label'] ?? ''),
                'number'    => $text($attachment['number'] ?? ''),
                'printable' => !empty($attachment['printable']),
            ];
        }

        return [
            'state'           => $text($payload['state']),
            'reference'       => $text($payload['reference'] ?? ''),
            'direction'       => $text($payload['direction'] ?? ''),
            'date'            => $text($payload['date'] ?? ''),
            'subject'         => $text($payload['subject'] ?? ''),
            'organisation'    => $text($payload['organisation'] ?? ''),
            'correspondent'   => $text($payload['correspondent'] ?? ''),
            'signatoryTitle'  => $text($payload['signatoryTitle'] ?? ''),
            'issuedAt'        => $text($payload['issuedAt'] ?? ''),
            // Anti-phishing code derived from the ERP-held snapshot; the
            // customer compares it with the code printed beside the QR.
            'checkCode'       => preg_match('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $text($payload['checkCode'] ?? '')) === 1
                ? $text($payload['checkCode'])
                : '',
            'revokedAt'       => $text($payload['revokedAt'] ?? ''),
            'revokedReason'   => $text($payload['revokedReason'] ?? ''),
            'attachments'     => $attachments,
        ];
    }

    /**
     * @return array{ok: bool, status: int, body: string, error: string}
     */
    private function get(string $path, int $timeout): array
    {
        $url = $this->base . $path;

        if (function_exists('curl_init')) {
            return $this->getWithCurl($url, $timeout);
        }

        return $this->getWithStreams($url, $timeout);
    }

    private function getWithCurl(string $url, int $timeout): array
    {
        $body     = '';
        $tooLarge = false;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, image/png',
                'User-Agent: BareedaWebsite/1.0 (+https://www.bareeda-injera.com)',
            ],
            CURLOPT_WRITEFUNCTION  => static function ($handle, $chunk) use (&$body, &$tooLarge) {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                    $tooLarge = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($tooLarge) {
            return ['ok' => false, 'status' => 502, 'body' => '', 'error' => 'Response too large'];
        }

        if ($result === false) {
            app_log('ERP request failed: ' . ($error !== '' ? $error : 'connection error'), 'error');
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Connection failed'];
        }

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => $body,
            'error'  => $status >= 200 && $status < 300 ? '' : 'HTTP ' . $status,
        ];
    }

    private function getWithStreams(string $url, int $timeout): array
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'ignore_errors'   => true,
                'follow_location' => 0,
                'max_redirects'   => 1,
                'header'          => "Accept: application/json, image/png\r\n"
                    . "User-Agent: BareedaWebsite/1.0 (+https://www.bareeda-injera.com)\r\n",
            ],
        ]);

        $handle = @fopen($url, 'rb', false, $context);
        if ($handle === false) {
            app_log('ERP request failed: stream connection error', 'error');
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Connection failed'];
        }

        $meta    = stream_get_meta_data($handle);
        $headers = isset($meta['wrapper_data']) && is_array($meta['wrapper_data']) ? $meta['wrapper_data'] : [];
        $body    = stream_get_contents($handle, self::MAX_BYTES + 1);
        fclose($handle);

        $status = 0;
        foreach ($headers as $line) {
            if (is_string($line) && preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches)) {
                $status = (int) $matches[1];
            }
        }

        if ($body === false) {
            app_log('ERP request failed: stream read error', 'error');
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Connection failed'];
        }

        if (strlen($body) > self::MAX_BYTES) {
            return ['ok' => false, 'status' => 502, 'body' => '', 'error' => 'Response too large'];
        }

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => (string) $body,
            'error'  => $status >= 200 && $status < 300 ? '' : 'HTTP ' . $status,
        ];
    }
}
