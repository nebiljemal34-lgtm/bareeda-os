<?php
/**
 * Bareeda Injera - Home Controller
 *
 * Handles the public home page.
 */

require_once __DIR__ . '/BaseController.php';

class HomeController extends BaseController
{
    /**
     * GET / — Home page with featured products.
     */
    public function index(): void
    {
        $product = new Product();
        $featured = $product->getFeatured(6);
        $cart = $GLOBALS['cart'];
        $cartCount = $cart->getItemCount();

        $this->render('home/index', [
            'page_title'  => 'Home',
            'featured'    => $featured,
            'cart_count'  => $cartCount,
        ]);
    }
}
