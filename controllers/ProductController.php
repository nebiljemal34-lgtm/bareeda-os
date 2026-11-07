<?php
/**
 * Bareeda Injera - Product Controller
 *
 * Handles product listing, detail, and search.
 */

require_once __DIR__ . '/BaseController.php';

class ProductController extends BaseController
{
    /**
     * GET /products — All products with pagination.
     */
    public function index(): void
    {
        $product  = new Product();
        $cart     = $GLOBALS['cart'];
        $page     = max(1, (int) ($_GET['page'] ?? 1));
        $limit    = ITEMS_PER_PAGE;
        $offset   = ($page - 1) * $limit;
        $sortBy   = $_GET['sort'] ?? 'created_at';
        $sortDir  = $_GET['dir'] ?? 'DESC';

        $products    = $product->getAll(true, 0, $limit, $offset, $sortBy, $sortDir);
        $totalItems  = $product->count(true);
        $totalPages  = max(1, ceil($totalItems / $limit));

        // Fetch categories for sidebar filter
        $db = Database::getInstance();
        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('product/index', [
            'page_title'  => 'Our Products',
            'products'    => $products,
            'categories'  => $categories,
            'cart_count'  => $cart->getItemCount(),
            'current_page'=> $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'sort_by'     => $sortBy,
            'sort_dir'    => $sortDir,
            'current_category' => 0,
            'search_query'     => '',
        ]);
    }

    /**
     * GET /products/{id} — Single product detail.
     */
    public function show(string $id): void
    {
        $product = new Product();
        $cart    = $GLOBALS['cart'];
        $item    = $product->getById((int) $id);

        if (!$item) {
            http_response_code(404);
            $this->render('home/index', [
                'page_title' => 'Not Found',
                'featured'   => [],
                'cart_count' => $cart->getItemCount(),
                'not_found'  => true,
            ]);
            return;
        }

        // Related products (same category, exclude current)
        $related = [];
        if (!empty($item['categories'])) {
            $catId = $item['categories'][0]['id'] ?? 0;
            if ($catId > 0) {
                $all = $product->getByCategory($catId);
                foreach ($all as $r) {
                    if ($r['id'] != $item['id']) {
                        $related[] = $r;
                    }
                }
                $related = array_slice($related, 0, 4);
            }
        }

        $this->render('product/show', [
            'page_title' => $item['name'],
            'product'    => $item,
            'related'    => $related,
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * GET /search?q= — Search products.
     */
    public function search(): void
    {
        $product  = new Product();
        $cart     = $GLOBALS['cart'];
        $query    = trim($_GET['q'] ?? '');
        $products = $query !== '' ? $product->search($query) : [];

        $db = Database::getInstance();
        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('product/index', [
            'page_title'  => 'Search: ' . htmlspecialchars($query),
            'products'    => $products,
            'categories'  => $categories,
            'cart_count'  => $cart->getItemCount(),
            'current_page'=> 1,
            'total_pages' => 1,
            'total_items' => count($products),
            'sort_by'     => 'name',
            'sort_dir'    => 'ASC',
            'current_category' => 0,
            'search_query'     => $query,
        ]);
    }
}
