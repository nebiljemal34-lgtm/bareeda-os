<?php
/**
 * Bareeda Injera - Admin Product Controller
 */

require_once __DIR__ . '/AdminBaseController.php';

class AdminProductController extends AdminBaseController
{
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    /**
     * List all products with pagination and optional search.
     */
    public function index(): void
    {
        $page    = max(1, $this->getInt('page', 1));
        $perPage = ADMIN_ITEMS_PER_PAGE;
        $offset  = ($page - 1) * $perPage;
        $search  = trim($this->get('search', ''));

        if ($search !== '') {
            $products = $this->productModel->search($search, false);
            $total    = count($products);
            // Manual pagination for search results
            $products = array_slice($products, $offset, $perPage);
        } else {
            $products = $this->productModel->getAll(false, 0, $perPage, $offset, 'created_at', 'DESC');
            $total    = $this->productModel->count(false);
        }

        $totalPages = max(1, ceil($total / $perPage));

        $this->render('admin/product/index', [
            'pageTitle'  => 'Products',
            'products'   => $products,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'search'     => $search,
        ]);
    }

    /**
     * Show create product form.
     */
    public function create(): void
    {
        $categories = $this->db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        $this->render('admin/product/form', [
            'pageTitle'  => 'Add Product',
            'product'    => null,
            'categories' => $categories,
            'isEdit'     => false,
        ]);
    }

    /**
     * Handle product creation (POST).
     */
    public function store(): void
    {
        $data = [
            'name'           => $this->post('name', ''),
            'name_am'        => $this->post('name_am', ''),
            'description'    => $this->post('description', ''),
            'description_am' => $this->post('description_am', ''),
            'price'          => $this->post('price', 0),
            'weight_kg'      => $this->post('weight_kg', ''),
            'stock_quantity' => $this->post('stock_quantity', 0),
            'is_active'      => $this->post('is_active', 0) ? 1 : 0,
        ];

        // Handle category IDs
        $categoryIds = $this->post('category_ids', []);
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }
        $data['category_ids'] = array_map('intval', $categoryIds);

        // Handle image upload
        $imageUrl = $this->handleImageUpload();
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        }

        // Validation
        if (empty($data['name'])) {
            flash('error', 'Product name (English) is required.');
            redirect('/admin/products/create');
        }
        if ((float) $data['price'] <= 0) {
            flash('error', 'Price must be greater than zero.');
            redirect('/admin/products/create');
        }

        $result = $this->productModel->create($data);

        if ($result['success']) {
            flash('success', 'Product created successfully.');
            redirect('/admin/products');
        } else {
            flash('error', $result['message']);
            redirect('/admin/products/create');
        }
    }

    /**
     * Show edit product form.
     */
    public function edit(string $id): void
    {
        $product = $this->productModel->getById((int) $id);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect('/admin/products');
        }

        $categories = $this->db->query("SELECT * FROM categories ORDER BY sort_order ASC");

        // Get current category IDs for this product
        $currentCatIds = [];
        if (!empty($product['categories'])) {
            foreach ($product['categories'] as $cat) {
                $currentCatIds[] = $cat['id'];
            }
        }

        $this->render('admin/product/form', [
            'pageTitle'     => 'Edit Product',
            'product'       => $product,
            'categories'    => $categories,
            'currentCatIds' => $currentCatIds,
            'isEdit'        => true,
        ]);
    }

    /**
     * Handle product update (POST).
     */
    public function update(string $id): void
    {
        $product = $this->productModel->getById((int) $id);
        if (!$product) {
            flash('error', 'Product not found.');
            redirect('/admin/products');
        }

        $data = [
            'name'           => $this->post('name', ''),
            'name_am'        => $this->post('name_am', ''),
            'description'    => $this->post('description', ''),
            'description_am' => $this->post('description_am', ''),
            'price'          => $this->post('price', 0),
            'weight_kg'      => $this->post('weight_kg', ''),
            'stock_quantity' => $this->post('stock_quantity', 0),
            'is_active'      => $this->post('is_active', 0) ? 1 : 0,
        ];

        // Handle category IDs
        $categoryIds = $this->post('category_ids', []);
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }
        $data['category_ids'] = array_map('intval', $categoryIds);

        // Handle image upload (optional on update)
        $imageUrl = $this->handleImageUpload();
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        }

        // Validation
        if (empty($data['name'])) {
            flash('error', 'Product name (English) is required.');
            redirect('/admin/products/' . $id . '/edit');
        }
        if ((float) $data['price'] <= 0) {
            flash('error', 'Price must be greater than zero.');
            redirect('/admin/products/' . $id . '/edit');
        }

        $result = $this->productModel->update((int) $id, $data);

        if ($result['success']) {
            flash('success', 'Product updated successfully.');
        } else {
            flash('error', $result['message']);
        }

        redirect('/admin/products');
    }

    /**
     * Handle product deletion (POST).
     */
    public function delete(string $id): void
    {
        $result = $this->productModel->delete((int) $id, false);

        if ($result['success']) {
            flash('success', 'Product deactivated successfully.');
        } else {
            flash('error', $result['message']);
        }

        redirect('/admin/products');
    }

    /**
     * Handle product image upload.
     *
     * @return string|null The image URL path or null on failure
     */
    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['image']['name']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['image'];

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            flash('error', 'Invalid image type. Allowed: JPEG, PNG, WebP, GIF.');
            return null;
        }

        // Validate file size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            flash('error', 'Image too large. Maximum size: ' . (MAX_UPLOAD_SIZE / 1024 / 1024) . ' MB.');
            return null;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename  = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

        // Ensure upload directory exists
        $uploadDir = UPLOAD_PATH . 'products/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/products/' . $filename;
        }

        flash('error', 'Failed to upload image.');
        return null;
    }
}
