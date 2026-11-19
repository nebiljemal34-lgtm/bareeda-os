<?php
/**
 * Bareeda Injera - Public letter verification
 *
 * The QR printed on a letter points at /verify/l/<token> on the company
 * website. This controller validates the token, asks the ERP (server-side)
 * for the public state, and renders the result. There is no auth on these
 * routes: a printed letter must be verifiable by anyone holding it.
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../classes/ErpClient.php';
require_once __DIR__ . '/../classes/VerifyStore.php';

class VerifyController extends BaseController
{
    private const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{20,44}$/';

    /**
     * GET /verify, /verify/l/{token}, /verify/{token}
     */
    public function show(string $token = ''): void
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'");

        $token = $this->resolveToken($token);

        // ?qr=1 on the page route serves the PNG instead.
        if ($token !== '' && isset($_GET['qr']) && !in_array($_GET['qr'], ['', '0'], true)) {
            $this->qr($token);
            return;
        }

        if ($token === '') {
            $this->renderPage(null, '', 200);
            return;
        }

        if (!$this->isValidToken($token)) {
            $this->renderPage($this->emptyResult('unknown'), $token, 404);
            return;
        }

        // 1) Snapshot replication: the ERP pushes each letter here at issue,
        //    so verification needs no factory connection at scan time.
        $replicated = VerifyStore::find($token);
        if ($replicated !== null) {
            // renderPage() runs present() itself; hand it the raw snapshot.
            $raw = is_array($replicated['result'] ?? null) ? $replicated['result'] : [];
            $raw['state'] = ($replicated['state'] ?? 'Active') === 'Revoked'
                ? 'revoked'
                : ($raw['state'] ?? 'active');
            if ($raw['state'] === 'unknown') {
                $this->renderPage($this->emptyResult('unknown'), $token, 404);
                return;
            }
            $this->renderPage($raw, $token, 200);
            return;
        }

        // 2) Fallback: live ERP lookup (older letters that predate replication).
        $response = (new ErpClient())->verification($token);

        if ($response['ok']) {
            $result = $response['data'];
            if ($result['state'] === 'unknown') {
                $this->renderPage($this->emptyResult('unknown'), $token, 404);
                return;
            }
            $this->renderPage($result, $token, 200);
            return;
        }

        if ($response['status'] === 404) {
            $this->renderPage($this->emptyResult('unknown'), $token, 404);
            return;
        }

        $this->renderPage(
            $this->emptyResult('unavailable'),
            $token,
            $response['status'] === 429 ? 429 : 503
        );
    }

    /**
     * GET /verify/l/{token}/qr, /verify/{token}/qr, /api/verify/{token}/qr
     */
    public function qr(string $token = ''): void
    {
        header('X-Robots-Tag: noindex, nofollow');

        $token = $this->resolveToken($token);
        if (!$this->isValidToken($token)) {
            $this->abort(404, 'This code is not recognised.');
            return;
        }

        $response = (new ErpClient())->qr($token);

        if (!$response['ok']) {
            if ($response['status'] === 404) {
                $this->abort(404, 'This code is not recognised.');
            } elseif ($response['status'] === 429) {
                header('Retry-After: 60');
                $this->abort(429, 'Too many requests. Please try again shortly.');
            } else {
                $this->abort(503, 'The verification service is unavailable.');
            }
            return;
        }

        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($response['body']));
        header('Cache-Control: public, max-age=86400, immutable');
        // PHP's session cache limiter ran at boot; drop its anti-cache pair.
        header_remove('Expires');
        header_remove('Pragma');
        echo $response['body'];
        exit;
    }

    /**
     * POST /api/verify/ingest — the ERP pushes a letter's frozen public
     * snapshot here at issue/reissue/revoke. Authenticated by a shared
     * secret header so only the factory ERP may write.
     */
    public function ingest(): void
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'");

        $expected = (string) ERP_INGEST_SECRET;
        $given = (string) ($_SERVER['HTTP_X_INGEST_SECRET'] ?? '');
        if ($expected === '' || !hash_equals($expected, $given)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['token'], $payload['result'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Malformed payload']);
            return;
        }

        if (!VerifyStore::upsert($payload)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Rejected']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }

    /**
     * GET /api/verify/{token} — JSON passthrough for scanner apps.
     */
    public function jsonState(string $token = ''): void
    {        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'");

        $token = $this->resolveToken($token);

        if (!$this->isValidToken($token)) {
            $this->json(['state' => 'unknown'], 404);
            return;
        }

        // Snapshot replication first: no factory connection at scan time.
        $replicated = VerifyStore::find($token);
        if ($replicated !== null) {
            $result = $this->present($replicated['result'] ?? []);
            $result['state'] = ($replicated['state'] ?? 'Active') === 'Revoked'
                ? 'revoked'
                : ($result['state'] ?? 'active');
            $this->json($result, $result['state'] === 'unknown' ? 404 : 200);
            return;
        }

        $response = (new ErpClient())->verification($token);

        if ($response['ok']) {
            $this->json($response['data'], 200);
            return;
        }

        if ($response['status'] === 404) {
            $this->json(['state' => 'unknown'], 404);
            return;
        }

        $this->json(
            ['state' => 'unavailable', 'error' => 'Verification service unavailable.'],
            $response['status'] === 429 ? 429 : 503
        );
    }

    // ─── Token handling ─────────────────────────────────────

    private function resolveToken(string $pathToken): string
    {
        $token = trim($pathToken);

        if ($token === '' && isset($_GET['token']) && is_string($_GET['token'])) {
            $token = trim($_GET['token']);
        }

        return $token === '' ? '' : $this->extractToken($token);
    }

    /**
     * Accept a bare code or a pasted verification URL.
     */
    private function extractToken(string $value): string
    {
        if (strpbrk($value, '/?') === false) {
            return $value;
        }

        $query = parse_url($value, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $parts);
            if (isset($parts['token']) && is_string($parts['token']) && $parts['token'] !== '') {
                return trim($parts['token']);
            }
        }

        $path = parse_url($value, PHP_URL_PATH);
        if (is_string($path)) {
            $segments = array_values(array_filter(explode('/', $path), static function ($segment) {
                return $segment !== '';
            }));
            $last = end($segments);
            if (is_string($last)) {
                return trim($last);
            }
        }

        return $value;
    }

    private function isValidToken(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }

    // ─── Rendering ──────────────────────────────────────────

    private function renderPage(?array $result, string $token, int $status): void
    {
        if ($status !== 200) {
            http_response_code($status);
        }

        $this->render('verify/show', [
            'page_title'    => 'Verify a Letter',
            'page_robots'   => 'noindex, nofollow',
            'page_css'      => '/assets/css/verify.css?v=20261120',
            'verify_state'  => $result === null ? null : $result['state'],
            'verify'        => $result === null ? null : $this->present($result),
            'verify_token'  => $token,
        ]);
    }

    private function present(array $result): array
    {
        return [
            'state'          => $result['state'],
            'reference'      => $result['reference'],
            'checkCode'      => $result['checkCode'] ?? '',
            'withheld'       => !empty($result['detailsWithheld']),
            'sealed'         => !empty($result['sealed']),
            'keyId'          => $result['keyId'] ?? '',
            'letter_date'    => $this->formatDate($result['date'] ?? ''),
            'subject'        => $result['subject'],
            'issued_to'      => $result['organisation'],
            'signed_by'      => $result['signatoryTitle'],
            'recorded'       => $this->formatMoment($result['issuedAt']),
            'cancelled'      => $this->formatMoment($result['revokedAt']),
            'revoked_reason' => $result['revokedReason'],
            'attachments'    => $result['attachments'],
        ];
    }

    private function emptyResult(string $state): array
    {
        return [
            'state'          => $state,
            'reference'      => '',
            'checkCode'      => '',
            'withheld'       => false,
            'sealed'         => false,
            'keyId'          => '',
            'date'           => '',
            'subject'        => '',
            'organisation'   => '',
            'signatoryTitle' => '',
            'issuedAt'       => '',
            'revokedAt'      => '',
            'revokedReason'  => '',
            'attachments'    => [],
        ];
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $date = new DateTimeImmutable($value, new DateTimeZone('Africa/Addis_Ababa'));
            return $date->format('j F Y');
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function formatMoment(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $moment = new DateTimeImmutable($value);
            return $moment
                ->setTimezone(new DateTimeZone('Africa/Addis_Ababa'))
                ->format('j F Y, H:i');
        } catch (Throwable $e) {
            return $value;
        }
    }

    private function abort(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
        echo $message;
        exit;
    }
}
