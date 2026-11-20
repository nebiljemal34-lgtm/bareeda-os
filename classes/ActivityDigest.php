<?php
/**
 * Bareeda Injera — Activity Sentinel digest builder.
 *
 * Shared by the hourly cron worker and the manual trigger endpoint so the
 * message you see in Telegram is always the same code path.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/Analytics.php';
require_once __DIR__ . '/TelegramNotifier.php';

class ActivityDigest
{
    /**
     * Build and send the digest for the last `$windowSeconds`.
     * @return int outbox id
     */
    public static function send(int $windowSeconds = 3600, string $title = 'HOURLY ACTIVITY DIGEST'): int
    {
        $db    = Database::getInstance();
        // MySQL stores created_at in UTC while PHP runs on Addis time; compare
        // UTC to UTC or every window lands three hours in the future.
        $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);

        $breakdown = Analytics::pageViewBreakdown($since);
        $traffic   = $breakdown['totals'];
        $counts = $db->fetchOne(
            "SELECT
                COALESCE(SUM(event_type = 'cart_add'), 0)     AS cart_adds,
                COALESCE(SUM(event_type = 'registration'), 0) AS registrations,
                COALESCE(SUM(event_type = 'failed_login'), 0) AS failed_logins,
                COALESCE(SUM(event_type = 'payment'), 0)      AS payments
             FROM analytics_events WHERE created_at >= ?",
            [$since]
        );
        $orders = $db->fetchOne(
            "SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue
             FROM orders WHERE created_at >= ? AND status <> 'cancelled'",
            [$since]
        );
        $topPages = $breakdown['top_pages'];
        $ipFlags  = $breakdown['ip_flags'] ?? [];
        $newIps = $db->query(
            "SELECT ip, country, MIN(created_at) AS first_seen
             FROM analytics_events
             WHERE ip <> '' AND created_at >= ?
               AND NOT EXISTS (
                   SELECT 1 FROM analytics_events earlier
                    WHERE earlier.ip = analytics_events.ip
                      AND earlier.created_at < ?
               )
             GROUP BY ip, country ORDER BY first_seen ASC LIMIT 12",
            [$since, $since]
        );

        $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        // $since is UTC; date() renders in Addis, so both labels are local.
        $startLabel = date('H:i', strtotime($since . ' UTC'));
        $lines   = [];
        $lines[] = '<b>' . $title . '</b> — ' . $startLabel . '–' . date('H:i') . ' (Addis)';
        $lines[] = '';
        $lines[] = '👁 Page views: ' . (int) ($traffic['page_views'] ?? 0)
            . ' · Visitors: ' . (int) ($traffic['visitors'] ?? 0)
            . ' · Unique IPs: ' . (int) ($traffic['unique_ips'] ?? 0);

        // Traffic that was kept out of the headline numbers. Shown, not hidden:
        // a wave of scanner hits is itself useful information.
        $filtered = [];
        $botHits = (int) ($traffic['bot_hits'] ?? 0);
        if ($botHits > 0) {
            $filtered[] = '🤖 ' . $botHits . ' bot hit' . ($botHits === 1 ? '' : 's')
                . ' (' . (int) $traffic['bot_ips'] . ' IP' . ((int) $traffic['bot_ips'] === 1 ? '' : 's') . ')';
        }
        $unverified = (int) ($traffic['unverified'] ?? 0);
        if ($unverified > 0) {
            $filtered[] = '🌍 ' . $unverified . ' unverified foreign hit' . ($unverified === 1 ? '' : 's')
                . ' (' . (int) $traffic['unverified_ips'] . ' IP' . ((int) $traffic['unverified_ips'] === 1 ? '' : 's') . ')';
        }
        if ($filtered) {
            $lines[] = 'Filtered: ' . implode(' · ', $filtered);
        }

        $lines[] = '🛒 Orders: ' . (int) ($orders['orders'] ?? 0)
            . ' · Revenue: ' . number_format((float) ($orders['revenue'] ?? 0), 2) . ' ETB';
        $lines[] = '➕ Cart adds: ' . (int) ($counts['cart_adds'] ?? 0)
            . ' · Registrations: ' . (int) ($counts['registrations'] ?? 0)
            . ' · Payments: ' . (int) ($counts['payments'] ?? 0)
            . ' · Failed logins: ' . (int) ($counts['failed_logins'] ?? 0);

        if (!empty($topPages)) {
            $lines[] = '';
            $lines[] = '<b>Top pages</b>';
            foreach ($topPages as $page) {
                $lines[] = '• ' . $e($page['path']) . ' — ' . (int) $page['views'] . ' views';
            }
        }

        if (!empty($newIps)) {
            $lines[] = '';
            $lines[] = '<b>New IP addresses</b>';
            foreach ($newIps as $row) {
                $country = (string) ($row['country'] ?? '') !== '' ? ' (' . $row['country'] . ')' : '';
                $flag = $ipFlags[(string) $row['ip']] ?? '';
                $note = $flag === 'bot' ? ' · 🤖 bot' : ($flag === 'unverified' ? ' · 🌍 unverified' : '');
                $lines[] = '• ' . $e($row['ip']) . $country . ' — ' . date('H:i', strtotime((string) $row['first_seen'] . ' UTC')) . ' (Addis)' . $note;
            }
        } else {
            $lines[] = '';
            $lines[] = 'No new IP addresses in this window.';
        }

        $id = TelegramNotifier::enqueue('hourly_digest', implode("\n", $lines));
        TelegramNotifier::attemptSend($id);
        return $id;
    }
}
