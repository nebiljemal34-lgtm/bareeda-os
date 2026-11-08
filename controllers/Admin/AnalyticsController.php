<?php

require_once __DIR__ . '/AdminBaseController.php';

class AdminAnalyticsController extends AdminBaseController
{
    public function index(): void
    {
        $days = $this->getInt('days', 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $this->render('admin/analytics/index', [
            'pageTitle' => 'Website Analytics',
            'summary' => Analytics::summary($days),
            'daily' => Analytics::daily($days),
            'topPages' => Analytics::topPages($days),
        ]);
    }
}
