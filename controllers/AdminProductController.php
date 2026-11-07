<?php
/**
 * Bareeda Injera - Admin Product Controller
 */

require_once __DIR__ . '/BaseController.php';

class AdminProductController extends BaseController
{
    public function index(): void
    {
        $this->requireAdmin();
        $product = new Product();
        $cart    = $GLOBALS['cart'];
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $limit   = ADMIN_ITEMS_PER_PAGE;
        $offset  = ($page - 1) * $limit;

        $products   = $product->getAll(false, 0, $limit, $offset);
        $totalItems = $product->count(false);
        $totalPages = max(1, ceil($totalItems / $limit));

        $this->render('admin/products/index', [
            'page_title'   => 'Manage Products',
            'cart_count'   => $cart->getItemCount(),
            'products'     => $products,
            'current_page' => $page,
            'total_pages'  => $totalPages,
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $cart = $GLOBALS['cart'];
        $db   = Database::getInstance();
        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('admin/products/form', [
            'page_title'  => 'Add Product',
            'cart_count'  => $cart->getItemCount(),
            'product'     => null,
            'categories'  => $categories,
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $product = new Product();

        $data = [
            'name'            => $this->input('name', ''),
            'name_am'         => $this->input('name_am', ''),
            'description'     => $this->rawInput('description', ''),
            'description_am'  => $this->rawInput('description_am', ''),
            'price'           => $this->rawInput('price', '0'),
            'weight_kg'       => $this->rawInput('weight_kg', ''),
            'stock_quantity'  => $this->rawInput('stock_quantity', '0'),
            'is_active'       => $this->rawInput('is_active', '1'),
            'image_url'       => $this->input('image_url', ''),
            'category_ids'    => $_POST['category_ids'] ?? [],
        ];

        $result = $product->create($data);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/admin/products');
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $product = new Product();
        $cart    = $GLOBALS['cart'];
        $item    = $product->getById((int) $id);

        if (!$item) {
            flash('error', 'Product not found.');
            redirect('/admin/products');
            return;
        }

        $db = Database::getInstance();
        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('admin/products/form', [
            'page_title'  => 'Edit Product',
            'cart_count'  => $cart->getItemCount(),
            'product'     => $item,
            'categories'  => $categories,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $product = new Product();

        $data = [
            'name'            => $this->input('name', ''),
            'name_am'         => $this->input('name_am', ''),
            'description'     => $this->rawInput('description', ''),
            'description_am'  => $this->rawInput('description_am', ''),
            'price'           => $this->rawInput('price', '0'),
            'weight_kg'       => $this->rawInput('weight_kg', ''),
            'stock_quantity'  => $this->rawInput('stock_quantity', '0'),
            'is_active'       => $this->rawInput('is_active', '1'),
            'image_url'       => $this->input('image_url', ''),
            'category_ids'    => $_POST['category_ids'] ?? [],
        ];

        $result = $product->update((int) $id, $data);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/admin/products/' . $id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requireAdmin();
        $product = new Product();
        $result  = $product->delete((int) $id);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect('/admin/products');
    }
}
