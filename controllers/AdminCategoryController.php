<?php
/**
 * Bareeda Injera - Admin Category Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminCategoryController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $cart = $GLOBALS['cart'];

        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('admin/categories/index', [
            'page_title'  => 'Manage Categories',
            'cart_count'  => $cart->getItemCount(),
            'categories'  => $categories,
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $name = $this->input('name', '');
        $nameAm = $this->input('name_am', '');
        $sortOrder = (int) ($this->rawInput('sort_order', '0'));

        if (empty($name)) {
            flash('error', 'Category name is required.');
            redirect('/admin/categories');
            return;
        }

        $db->insert('categories', [
            'name'       => $name,
            'name_am'    => $nameAm ?: null,
            'sort_order' => $sortOrder,
        ]);

        flash('success', 'Category created.');
        redirect('/admin/categories');
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $db   = Database::getInstance();
        $name = $this->input('name', '');
        $nameAm = $this->input('name_am', '');
        $sortOrder = (int) ($this->rawInput('sort_order', '0'));

        if (empty($name)) {
            flash('error', 'Category name is required.');
            redirect('/admin/categories');
            return;
        }

        $db->update('categories', [
            'name'       => $name,
            'name_am'    => $nameAm ?: null,
            'sort_order' => $sortOrder,
        ], 'id = ?', [(int) $id]);

        flash('success', 'Category updated.');
        redirect('/admin/categories');
    }

    public function delete(string $id): void
    {
        $this->requireAdmin();
        $db = Database::getInstance();
        $db->delete('categories', 'id = ?', [(int) $id]);
        flash('success', 'Category deleted.');
        redirect('/admin/categories');
    }
}
