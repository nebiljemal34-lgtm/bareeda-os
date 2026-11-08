<?php

require_once __DIR__ . '/AdminBaseController.php';

class AdminTelegramController extends AdminBaseController
{
    public function index(): void
    {
        $status = (string) $this->get('status', '');
        if (!in_array($status, ['simulated', 'pending', 'sent', 'failed'], true)) {
            $status = '';
        }

        $counts = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'simulated'), 0) AS simulated,
                    COALESCE(SUM(status = 'pending'), 0) AS pending,
                    COALESCE(SUM(status = 'sent'), 0) AS sent,
                    COALESCE(SUM(status = 'failed'), 0) AS failed
             FROM telegram_outbox"
        );

        $where  = $status !== '' ? 'WHERE status = ?' : '';
        $params = $status !== '' ? [$status] : [];
        $total  = (int) $this->db->fetchOne("SELECT COUNT(*) AS n FROM telegram_outbox $where", $params)['n'];
        $per    = ADMIN_ITEMS_PER_PAGE;
        $pages  = max(1, (int) ceil($total / $per));
        $page   = max(1, min($pages, $this->getInt('page', 1)));
        $rows   = $this->db->query(
            "SELECT * FROM telegram_outbox $where ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per),
            $params
        );

        $this->render('admin/telegram/index', [
            'pageTitle'   => 'Telegram Alerts',
            'rows'        => $rows,
            'counts'      => $counts,
            'status'      => $status,
            'page'        => $page,
            'pages'       => $pages,
            'live'        => TelegramNotifier::isLive(),
            'credentials' => TelegramNotifier::credentialsReady(),
            'digest_enabled' => SiteSettings::get('telegram_digest_enabled', '1') === '1',
            'digest_hour'    => SiteSettings::get('telegram_digest_hour', '8'),
            'last_digest'    => SiteSettings::get('telegram_last_digest_date'),
        ]);
    }

    public function retry(string $id): void
    {
        $row = $this->db->fetchOne("SELECT status FROM telegram_outbox WHERE id = ?", [(int) $id]);
        if (!$row || !in_array($row['status'], ['failed', 'pending'], true)) {
            flash('error', 'Only failed or pending alerts can be retried.');
            redirect('/admin/telegram');
        }
        $result = TelegramNotifier::attemptSend((int) $id);
        if ($result['ok']) {
            flash('success', 'Alert delivered to Telegram.');
        } else {
            flash('error', $result['error']);
        }
        redirect('/admin/telegram');
    }
}