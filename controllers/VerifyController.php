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
     * GET /api/verify/{token} — JSON passthrough for scanner apps.
     */
    public function jsonState(string $token = ''): void
    {
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store, max-age=0');

        $token = $this->resolveToken($token);

        if (!$this->isValidToken($token)) {
            $this->json(['state' => 'unknown'], 404);
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
            'page_css'      => '/assets/css/verify.css',
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
            'letter_date'    => $this->formatDate($result['date']),
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
