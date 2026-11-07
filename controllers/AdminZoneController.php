<?php
/**
 * Bareeda Injera - Admin Delivery Zone Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminZoneController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $cart = $GLOBALS['cart'];

        $zones = $db->query("SELECT * FROM delivery_zones ORDER BY fee ASC");

        $this->render('admin/zones/index', [
            'page_title' => 'Delivery Zones',
            'cart_count' => $cart->getItemCount(),
            'zones'      => $zones,
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $db       = Database::getInstance();
        $zoneName = $this->input('zone_name', '');
        $fee      = (float) ($this->rawInput('fee', '0'));

        if (empty($zoneName)) {
            flash('error', 'Zone name is required.');
            redirect('/admin/delivery-zones');
            return;
        }

        $db->insert('delivery_zones', [
            'zone_name' => $zoneName,
            'fee'       => $fee,
            'is_active' => 1,
        ]);

        flash('success', 'Zone created.');
        redirect('/admin/delivery-zones');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $db       = Database::getInstance();
        $zoneName = $this->input('zone_name', '');
        $fee      = (float) ($this->rawInput('fee', '0'));

        if (empty($zoneName)) {
            flash('error', 'Zone name is required.');
            redirect('/admin/delivery-zones');
            return;
        }

        $db->update('delivery_zones', [
            'zone_name' => $zoneName,
            'fee'       => $fee,
        ], 'id = ?', [(int) $id]);

        flash('success', 'Zone updated.');
        redirect('/admin/delivery-zones');
    }

    public function toggle(string $id): void
    {
        $this->requireAdmin();
        $db = Database::getInstance();
        $db->execute("UPDATE delivery_zones SET is_active = NOT is_active WHERE id = ?", [(int) $id]);
        flash('success', 'Zone toggled.');
        redirect('/admin/delivery-zones');
    }
}
