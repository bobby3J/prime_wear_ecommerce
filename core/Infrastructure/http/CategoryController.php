<?php
namespace Infrastructure\Http;

use Core\Infrastructure\Persistence\Database;
use Application\DTO\CreateCategoryDTO;
use Application\DTO\UpdateCategoryDTO;
use Application\Usecases\Category\CreateCategoryUseCase;
use Application\Usecases\Category\UpdateCategoryUseCase;
use Application\Usecases\Category\DeleteCategoryUseCase;
use Application\Usecases\Category\FindCategoryUseCase;
use Application\Usecases\Category\ListCategoriesUseCase;
use Infrastructure\Persistence\MySQLCategoryRepository;
use Infrastructure\Persistence\MySQLProductImageRepository;
use Infrastructure\Files\ProductImageDeleter;

class CategoryController
{
    private CreateCategoryUseCase $createCategoryUseCase;
    private UpdateCategoryUseCase $updateCategoryUseCase;
    private DeleteCategoryUseCase $deleteCategoryUseCase;
    private FindCategoryUseCase $findCategoryUseCase;
    private ListCategoriesUseCase $listCategoriesUseCase;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $categoryRepo = new MySQLCategoryRepository($pdo);
        $imageRepo = new MySQLProductImageRepository($pdo);
        $fileDeleter = new ProductImageDeleter();

        $this->createCategoryUseCase = new CreateCategoryUseCase($categoryRepo);
        $this->updateCategoryUseCase = new UpdateCategoryUseCase($categoryRepo);
        $this->deleteCategoryUseCase = new DeleteCategoryUseCase(
            $categoryRepo,
            $imageRepo,
            $fileDeleter
        );
        $this->findCategoryUseCase = new FindCategoryUseCase($categoryRepo);
        $this->listCategoriesUseCase = new ListCategoriesUseCase($categoryRepo);
    }

    public function view(): array
    {
        $categories = $this->listCategoriesUseCase->execute();

        // Read filters from query string
        $search = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
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

        // Apply filters in-memory (name + status)
        $categories = array_values(array_filter($categories, function ($category) use ($search, $status) {
            if ($search !== '' && stripos($category->getName(), $search) === false) {
                return false;
            }

            if ($status !== '' && $category->getStatus() !== $status) {
                return false;
            }

            return true;
        }));

        // Pagination: compute totals and slice the current page
        $total = count($categories);
        $totalPages = (int) ceil($total / $perPage);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $categories = array_slice($categories, $offset, $perPage);

        return [
            'view' => 'categories/view_categories.php',
            'data' => [
                'categories' => $categories,
                'filters' => [
                    'q' => $search,
                    'status' => $status
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

    public function create(): array
    {
        return [
            'view' => 'categories/insert_category.php',
            'data' => []
        ];
    }

    public function store(): void
    {
        try {
            $dto = new CreateCategoryDTO(
                trim($_POST['name'] ?? ''),
                $_POST['status'] ?? 'active'
            );

            $this->createCategoryUseCase->execute($dto);

            header("Location: /admin/categories/view?created=1");
            exit;
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    public function edit(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $category = $this->findCategoryUseCase->execute($id);

        if (!$category) {
            return [
                'view' => 'errors/404.php',
                'data' => []
            ];
        }

        return [
            'view' => 'categories/edit_categories.php',
            'data' => [
                'category' => $category
            ]
        ];
    }

    public function update(): void
    {
        try {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('Invalid category ID.');
            }

            $dto = new UpdateCategoryDTO(
                $id,
                trim($_POST['name'] ?? ''),
                $_POST['status'] ?? 'active'
            );

            $this->updateCategoryUseCase->execute($dto);

            header("Location: /admin/categories/view?updated=1");
            exit;
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    public function delete(): array
    {
        try {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new \Exception('Invalid category ID.');
            }

            $this->deleteCategoryUseCase->execute($id);

            $result = $this->view();
            $result['data']['deleted'] = true;
            return $result;
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
            return $this->view();
        }
    }
}
