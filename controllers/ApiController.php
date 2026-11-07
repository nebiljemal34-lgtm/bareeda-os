<?php
/**
 * Bareeda Injera - API Controller
 *
 * JSON API endpoints for products and cart.
 */

require_once __DIR__ . '/BaseController.php';

class ApiController extends BaseController
{
    /**
     * GET /api/products
     */
    public function products(): void
    {
        $product = new Product();
        $items   = $product->getAll(true);
        json_response(['data' => $items]);
    }

    /**
     * GET /api/products/{id}
     */
    public function product(string $id): void
    {
        $product = new Product();
        $item    = $product->getById((int) $id);

        if (!$item) {
            json_response(['error' => 'Product not found'], 404);
            return;
        }

        json_response(['data' => $item]);
    }

    /**
     * GET /api/cart
     */
    public function cart(): void
    {
        $cart  = $GLOBALS['cart'];
        $items = $cart->getItems();

        json_response([
            'items'    => $items,
            'count'    => $cart->getItemCount(),
            'subtotal' => $cart->getSubtotal(),
            'total'    => $cart->getTotal(),
        ]);
    }

    /**
     * GET /api/cart/count
     */
    public function cartCount(): void
    {
        $cart = $GLOBALS['cart'];
        json_response(['count' => $cart->getItemCount()]);
    }
}
