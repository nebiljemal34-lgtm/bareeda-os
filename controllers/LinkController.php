<?php

require_once __DIR__ . '/BaseController.php';

class LinkController extends BaseController
{
    public function index(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        readfile(BASE_PATH . 'links/index.html');
    }
}
