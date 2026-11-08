<?php
/**
 * Bareeda Injera - Page Controller
 *
 * Handles static pages: About, Contact, Privacy, Terms.
 */

require_once __DIR__ . '/BaseController.php';

class PageController extends BaseController
{
    /**
     * GET /about
     */
    public function about(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('home/about', [
            'page_title' => 'About Us',
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * GET /contact
     */
    public function contact(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('home/contact', [
            'page_title' => 'Contact Us',
            'cart_count' => $cart->getItemCount(),
        ]);
    }

    /**
     * POST /contact — Handle contact form.
     */
    public function submitContact(): void
    {
        $name    = $this->input('name', '');
        $email   = $this->input('email', '');
        $subject = $this->input('subject', '');
        $message = $this->input('message', '');

        if (empty($name) || empty($email) || empty($message)) {
            flash('error', 'Please fill in all required fields.');
            redirect('/contact');
            return;
        }

        // Log the contact message
        app_log("Contact form: {$name} ({$email}) - {$subject}: {$message}");

        TelegramNotifier::notify('contact_message', [
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        flash('success', 'Thank you for your message! We will get back to you soon.');
        redirect('/contact');
    }

    /**
     * GET /privacy
     */
    public function privacy(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('home/about', [
            'page_title' => 'Privacy Policy',
            'cart_count' => $cart->getItemCount(),
            'is_privacy' => true,
        ]);
    }

    /**
     * GET /terms
     */
    public function terms(): void
    {
        $cart = $GLOBALS['cart'];
        $this->render('home/about', [
            'page_title' => 'Terms of Service',
            'cart_count' => $cart->getItemCount(),
            'is_terms'   => true,
        ]);
    }
}
