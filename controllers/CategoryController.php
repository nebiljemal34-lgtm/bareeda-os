<?php
/**
 * Bareeda Injera - Category Controller
 *
 * Handles product listing by category.
 */

require_once __DIR__ . '/BaseController.php';

class CategoryController extends BaseController
{
    /**
     * GET /categories/{id}
     */
    public function show(string $id): void
    {
        $product    = new Product();
        $cart       = $GLOBALS['cart'];
        $categoryId = (int) $id;
        $db         = Database::getInstance();

        $category   = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
        if (!$category) {
            flash('error', 'Category not found.');
            redirect('/products');
            return;
        }

        $products = $product->getByCategory($categoryId, true);
        $categories = $db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('product/index', [
            'page_title'  => $category['name'],
            'products'    => $products,
            'categories'  => $categories,
            'cart_count'  => $cart->getItemCount(),
            'current_page'=> 1,
            'total_pages' => 1,
            'total_items' => count($products),
            'sort_by'     => 'name',
            'sort_dir'    => 'ASC',
            'current_category' => $categoryId,
            'search_query'     => '',
        ]);
    }
}
