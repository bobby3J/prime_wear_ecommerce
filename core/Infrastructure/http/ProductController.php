<?php
namespace Infrastructure\Http;

use Core\Infrastructure\Persistence\Database;
use Application\DTO\CreateProductDTO;
use Application\DTO\UpdateProductDTO;
use Application\UseCases\Product\CreateProductUseCase;
use Application\Usecases\Product\AddProductImageUseCase;
use Application\Usecases\Product\DeleteProductUsecase;
use Application\Usecases\Product\UpdateProductUseCase;
use Application\Usecases\Category\ListCategoriesUseCase;
use Infrastructure\Persistence\MySQLProductRepository;
use Infrastructure\Persistence\MySQLProductImageRepository;
use Infrastructure\Persistence\MySQLCategoryRepository;
use Infrastructure\Files\ProductImageUploader;
use Infrastructure\Files\ProductImageDeleter;
use Application\Usecases\Product\FindProductUseCase;
use Application\Usecases\Product\ListProductsViewUseCase;
use Domain\Product\ProductReadRepository;
use Infrastructure\Persistence\MySQLProductReadRepository;

class ProductController
{
    private CreateProductUseCase $createProductUseCase;
    private AddProductImageUseCase $addProductImageUseCase;
    private ProductImageUploader $imageUploader;
    private MySQLProductImageRepository $imageRepo;
    private ProductImageDeleter $fileDeleter;
    private DeleteProductUsecase $deleteProductUsecase;
    private FindProductUseCase $findProductUseCase;
    private ListProductsViewUseCase $listProductsViewUseCase;
    private UpdateProductUseCase $updateProductUseCase;
    private ListCategoriesUseCase $listCategoriesUseCase;

    public function __construct()
    {
        $pdo = Database::getConnection();

        $productRepo = new MySQLProductRepository($pdo);
        $imageRepo = new MySQLProductImageRepository($pdo);
        $fileDeleter = new ProductImageDeleter();
        $this->imageRepo = $imageRepo;
        $this->fileDeleter = $fileDeleter;

        $categoryRepo = new MySQLCategoryRepository($pdo);
        $this->listCategoriesUseCase = new ListCategoriesUseCase($categoryRepo);
        
        // read repository (for DTO / view with category name)
        $readRepo = new MySQLProductReadRepository($pdo);
        $this->listProductsViewUseCase = new ListProductsViewUseCase($readRepo);

        // create product
        $this->createProductUseCase = new CreateProductUseCase($productRepo);
      
        // find product
        $this->findProductUseCase = new FindProductUseCase($productRepo);
        $this->updateProductUseCase = new UpdateProductUseCase($productRepo);

       // upload image
        $this->imageUploader = new ProductImageUploader();
        $this->addProductImageUseCase = new AddProductImageUseCase($imageRepo);

        // delete product
        $this->deleteProductUsecase = new DeleteProductUsecase(
            $productRepo,
            $imageRepo,
            $fileDeleter
        );

        // // TEMP manual wiring (later: DI container)
        // $repository = new MySQLProductRepository($pdo);
        // $this->createProductUseCase = new CreateProductUseCase($repository);

        // $this->imageUploader = new ProductImageUploader();
    }

    /**
     * Handles GET /admin/products/list
     */
    public function view(): array
    {
        $products = $this->listProductsViewUseCase->execute();
        $categories = $this->getProductAssignableCategories();
        $assignableCategoryIds = array_map(
            static fn($category): int => (int) $category->getId(),
            $categories
        );

        // Products must belong to assignable subcategories, not root categories.
        $products = array_values(array_filter(
            $products,
            static fn($product): bool => in_array((int) $product->categoryId, $assignableCategoryIds, true)
        ));

        // Read filter/sort inputs from the query string
        $search = trim($_GET['q'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $stockLevel = $_GET['stock_level'] ?? '';
        $priceMinRaw = $_GET['price_min'] ?? '';
        $priceMaxRaw = $_GET['price_max'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $lowStockThreshold = 5;
        $perPageRaw = $_GET['per_page'] ?? 10;
        $perPage = (int) $perPageRaw;
        $allowedPerPage = [10, 25, 50];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $page = (int) ($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        // Normalize numeric filters (ignore non-numeric inputs)
        $priceMin = is_numeric($priceMinRaw) ? (float) $priceMinRaw : null;
        $priceMax = is_numeric($priceMaxRaw) ? (float) $priceMaxRaw : null;

        // Apply filters in-memory (search, category, price range, stock level)
        $products = array_values(array_filter($products, function ($product) use ($search, $categoryId, $stockLevel, $lowStockThreshold, $priceMin, $priceMax) {
            if ($search !== '' && stripos($product->name, $search) === false) {
                return false;
            }

            if ($categoryId > 0 && (int) $product->categoryId !== $categoryId) {
                return false;
            }

            if ($priceMin !== null && $product->price < $priceMin) {
                return false;
            }

            if ($priceMax !== null && $product->price > $priceMax) {
                return false;
            }

            // Stock level buckets (threshold is configurable above)
            switch ($stockLevel) {
                case 'out_of_stock':
                    return $product->stock === 0;
                case 'low_stock':
                    return $product->stock > 0 && $product->stock <= $lowStockThreshold;
                case 'in_stock':
                    return $product->stock > $lowStockThreshold;
                default:
                    return true;
            }
        }));

        // Apply sorting after filtering
        if ($sort !== '') {
            usort($products, function ($a, $b) use ($sort) {
                switch ($sort) {
                    case 'name_asc':
                        return strcasecmp($a->name, $b->name);
                    case 'name_desc':
                        return strcasecmp($b->name, $a->name);
                    case 'price_asc':
                        return $a->price <=> $b->price;
                    case 'price_desc':
                        return $b->price <=> $a->price;
                    case 'stock_asc':
                        return $a->stock <=> $b->stock;
                    case 'stock_desc':
                        return $b->stock <=> $a->stock;
                    default:
                        return 0;
                }
            });
        }

        // Pagination: compute totals and slice the current page
        $total = count($products);
        $totalPages = (int) ceil($total / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $products = array_slice($products, $offset, $perPage);

        return [
            'view' => 'products/view_products.php',
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'filters' => [
                    'q' => $search,
                    'category_id' => $categoryId,
                    'stock_level' => $stockLevel,
                    'price_min' => $priceMinRaw,
                    'price_max' => $priceMaxRaw,
                    'sort' => $sort,
                    'low_stock_threshold' => $lowStockThreshold
                ],
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]
        ];
    }

    /**
     * Handles GET /admin/products/create
     */
    public function create(): array
    {
        return [
            'view' => 'products/create_product.php',
            'data' => [
                'categories' => $this->getProductAssignableCategories()
            ]
        ];
    }

    /**
     * Handles POST /admin/products/create
     */
    public function store(): void
    {

        try {
            // Debug: Output file upload array
            if (!isset($_FILES['image'])) {
                echo '<pre>No file uploaded. $_FILES["image"] is not set.</pre>';
            } else {
                echo '<pre>$_FILES["image"]: ' . print_r($_FILES['image'], true) . '</pre>';
            }

            // 1. Extract HTTP input
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            if (!$this->isAssignableCategoryId($categoryId)) {
                throw new \Exception('Please select a valid subcategory.');
            }

            $dto = new CreateProductDTO(
                $categoryId,
                trim($_POST['name']),  
                $_POST['description'] ?? null,
                (float) $_POST['price'],
                (int) $_POST['stock']
            );

            // 2. Execute use case (Create Product-Application concern)
            $product = $this->createProductUseCase->execute($dto);

            // 3. Upload image (Infrastructure concern)
            if (!empty($_FILES['image']['tmp_name'])) {
                $path = $this->imageUploader->upload($_FILES['image']);

                // persist image path (repo / query)
                $this->addProductImageUseCase->execute(
                    $product->getId(),
                    $path,
                    true // isPrimary
                );
            }

            // 4. HTTP response: Redirect back to the form with success flag
            header("Location: /admin/products/view?success=1");
            exit;

        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    /**
     * Handles GET /admin/products/edit?id={id}
     */
    public function edit(): array
    {
        $id = (int) ($_GET['id'] ?? 0);

        $product = $this->findProductUseCase->execute($id);

        if (!$product) {
            http_response_code(404);
            return [
                'view' => 'errors/404.php',
                'data' => []
            ];
        }

        $images = $this->imageRepo->findByProductId($id);
        $imagePath = $images[0]['image_path'] ?? null;

        return [
            'view' => 'products/edit_product.php',
            'data' => [
                'product' => $product,
                'imagePath' => $imagePath,
                'categories' => $this->getProductAssignableCategories()
            ]
        ];
    }

    /**
     * Handles POST /admin/products/edit
     */
    public function update(): void
    {
        try {
            $productId = (int) ($_POST['id'] ?? 0);

            if ($productId <= 0) {
                throw new \Exception('Invalid product ID.');
            }

            $categoryId = (int) ($_POST['category_id'] ?? 0);
            if (!$this->isAssignableCategoryId($categoryId)) {
                throw new \Exception('Please select a valid subcategory.');
            }

            $dto = new UpdateProductDTO(
                $productId,
                $categoryId,
                trim($_POST['name']),
                $_POST['description'] ?? null,
                (float) $_POST['price'],
                (int) $_POST['stock']
            );

            $this->updateProductUseCase->execute($dto);

            if (!empty($_FILES['image']['tmp_name'])) {
                $newPath = $this->imageUploader->upload($_FILES['image']);

                $images = $this->imageRepo->findByProductId($productId);
                foreach ($images as $image) {
                    $path = $image['image_path'] ?? $image['path'] ?? null;
                    if (is_string($path) && $path !== '') {
                        $this->fileDeleter->delete($path);
                    }
                }

                $this->imageRepo->deleteByProductId($productId);

                $this->addProductImageUseCase->execute(
                    $productId,
                    $newPath,
                    true
                );
            }

            header("Location: /admin/products/view?updated=1");
            exit;

        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    /**
     * Handles GET /admin/products/delete?id={id}
     */
    public function delete(): array
    {
        try {
            $productId = (int) ($_GET['id'] ?? 0);

            if ($productId <= 0) {
                throw new \Exception('Invalid product ID.');
            }

            $this->deleteProductUsecase->execute($productId);

            $result = $this->view();
            $result['data']['deleted'] = true;
            return $result;
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
            return $this->view();
        }
    }
   
    private function getProductAssignableCategories(): array
    {
        $categories = $this->listCategoriesUseCase->execute();

        return array_values(array_filter(
            $categories,
            function ($category): bool {
                $slug = strtolower($category->getSlug());
                if (in_array($slug, ['men', 'ladies', 'unisex'], true)) {
                    return false;
                }

                if ($category->getStatus() !== 'active') {
                    return false;
                }

                return $category->getParentId() !== null;
            }
        ));
    }

    private function isAssignableCategoryId(int $categoryId): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        foreach ($this->getProductAssignableCategories() as $category) {
            if ((int) $category->getId() === $categoryId) {
                return true;
            }
        }

        return false;
    }

}
