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
use Domain\Category\Category;
use Domain\Shared\Slugger;
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
    private MySQLCategoryRepository $categoryRepo;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $categoryRepo = new MySQLCategoryRepository($pdo);
        $this->categoryRepo = $categoryRepo;
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
        $allCategories = $this->listCategoriesUseCase->execute();
        $categories = $allCategories;
        $categoryNameById = [];
        $rootSlugOrder = ['men' => 0, 'ladies' => 1, 'unisex' => 2];
        $rootOrderById = [];

        foreach ($allCategories as $categoryItem) {
            $categoryNameById[$categoryItem->getId()] = $categoryItem->getName();

            $slug = strtolower($categoryItem->getSlug());
            if (array_key_exists($slug, $rootSlugOrder)) {
                $rootOrderById[$categoryItem->getId()] = $rootSlugOrder[$slug];
            }
        }

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

        // Keep parent categories and their subcategories adjacent for readability.
        usort($categories, function ($a, $b) use ($rootOrderById, $categoryNameById) {
            $buildSortKey = static function ($category) use ($rootOrderById, $categoryNameById): array {
                $slug = strtolower($category->getSlug());
                $id = (int) $category->getId();
                $parentId = $category->getParentId();

                if (array_key_exists($id, $rootOrderById) || in_array($slug, ['men', 'ladies', 'unisex'], true)) {
                    $groupOrder = $rootOrderById[$id] ?? 99;
                    return [$groupOrder, 0, strtolower($category->getName()), ''];
                }

                $groupOrder = ($parentId !== null && array_key_exists($parentId, $rootOrderById))
                    ? $rootOrderById[$parentId]
                    : 99;
                $parentName = ($parentId !== null && isset($categoryNameById[$parentId]))
                    ? strtolower($categoryNameById[$parentId])
                    : 'zzzz';

                return [$groupOrder, 1, $parentName, strtolower($category->getName())];
            };

            $aKey = $buildSortKey($a);
            $bKey = $buildSortKey($b);

            for ($i = 0; $i < count($aKey); $i++) {
                if ($aKey[$i] === $bKey[$i]) {
                    continue;
                }
                return $aKey[$i] <=> $bKey[$i];
            }

            return 0;
        });

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
                'categoryNameById' => $categoryNameById,
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
        $collectionRoots = $this->getCollectionRoots();

        return [
            'view' => 'categories/insert_category.php',
            'data' => [
                'collectionRoots' => $collectionRoots
            ]
        ];
    }

    public function store(): void
    {
        try {
            $name = trim($_POST['name'] ?? '');
            $rawParentId = (int) ($_POST['parent_id'] ?? 0);
            $parentId = $this->sanitizeCollectionParentId($rawParentId);
            if ($rawParentId > 0 && $parentId === null) {
                throw new \Exception('Invalid collection group selected.');
            }
            if (!$this->isCollectionRootSlug(Slugger::fromString($name)) && $parentId === null) {
                throw new \Exception('Collection group is required for subcategories.');
            }

            $dto = new CreateCategoryDTO(
                $name,
                $_POST['status'] ?? 'active',
                $parentId
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
        $collectionRoots = $this->getCollectionRoots();

        if (!$category) {
            return [
                'view' => 'errors/404.php',
                'data' => []
            ];
        }

        return [
            'view' => 'categories/edit_categories.php',
            'data' => [
                'category' => $category,
                'collectionRoots' => $collectionRoots
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

            $currentCategory = $this->findCategoryUseCase->execute($id);
            if (!$currentCategory) {
                throw new \Exception('Category not found.');
            }
            $isCollectionRoot = $this->isCollectionRootSlug($currentCategory->getSlug());

            $rawParentId = (int) ($_POST['parent_id'] ?? 0);
            $parentId = $this->sanitizeCollectionParentId($rawParentId);
            if ($rawParentId > 0 && $parentId === null) {
                throw new \Exception('Invalid collection group selected.');
            }
            if ($parentId === $id) {
                $parentId = null;
            }
            if ($isCollectionRoot) {
                $parentId = null;
            } elseif ($parentId === null) {
                throw new \Exception('Collection group is required for subcategories.');
            }
            $status = $isCollectionRoot ? 'active' : ($_POST['status'] ?? 'active');

            $dto = new UpdateCategoryDTO(
                $id,
                trim($_POST['name'] ?? ''),
                $status,
                $parentId
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

    private function getCollectionRoots(): array
    {
        $definitions = [
            ['slug' => 'men', 'label' => "Men's Collection"],
            ['slug' => 'ladies', 'label' => "Ladies' Collection"],
            ['slug' => 'unisex', 'label' => 'Couples & Unisex'],
        ];

        $roots = [];

        foreach ($definitions as $definition) {
            $category = $this->categoryRepo->findBySlug($definition['slug']);
            if (!$category) {
                $category = Category::create(
                    $definition['label'],
                    $definition['slug'],
                    null,
                    'active'
                );
                $this->categoryRepo->save($category);
            } elseif ($category->getStatus() !== 'active') {
                $category->updateDetails(
                    $category->getName(),
                    $category->getSlug(),
                    $category->getParentId(),
                    'active'
                );
                $this->categoryRepo->save($category);
            }

            $roots[] = [
                'id' => (int) $category->getId(),
                'slug' => $definition['slug'],
                'label' => $definition['label'],
            ];
        }

        return $roots;
    }

    private function sanitizeCollectionParentId(int $parentId): ?int
    {
        if ($parentId <= 0) {
            return null;
        }

        $allowedIds = array_map(
            static fn(array $root): int => (int) $root['id'],
            $this->getCollectionRoots()
        );

        if (!in_array($parentId, $allowedIds, true)) {
            return null;
        }

        return $parentId;
    }

    private function isCollectionRootSlug(string $slug): bool
    {
        return in_array(
            strtolower(trim($slug)),
            ['men', 'ladies', 'unisex'],
            true
        );
    }
}
