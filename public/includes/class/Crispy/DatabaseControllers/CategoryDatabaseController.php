<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\Models\CategoryModel;
use Crispy\Models\jsTreeItem;
use Crispy\Models\jsTreeItemModel;
use Crispy\Models\jsTreeStateModel;
use Exception;
use PDO;

class CategoryDatabaseController extends DatabaseController
{

    private const tableName = 'crispy_categories';
    public const rowsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): CategoryModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new CategoryModel(
            name: $row['name'],
            properties: $row['properties'],
            slug: $row['slug'],
            parent: $row['parent'] === null ? null : $this->getCategoryById($row['parent']),
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ']),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ']),
            id: $row['id'],
        );
    }

    public function beginTransaction()
    {
        if ($this->getDatabaseConnector()->inTransaction()) {
            throw new Exception('Cannot begin transaction, because transaction is already active.');
        }
        $this->getDatabaseConnector()->beginTransaction();
    }

    public function commitTransaction()
    {
        if (!$this->getDatabaseConnector()->inTransaction()) {
            throw new Exception('Cannot commit transaction, because no transaction is active.');
        }
        $this->getDatabaseConnector()->commit();
    }

    public function rollbackTransaction()
    {
        if (!$this->getDatabaseConnector()->inTransaction()) {
            throw new Exception('Cannot rollback transaction, because no transaction is active.');
        }
        $this->getDatabaseConnector()->rollBack();
    }

    public function checkSlugCollision(CategoryModel $categoryModel): bool
    {
        $query = sprintf(
            '(SELECT id FROM %s WHERE computed_url = :category %s) 
         UNION 
         (SELECT id FROM %s WHERE computed_url = :category)',
            self::tableName,
            $categoryModel->getId() !== null ? 'AND id != :id' : '',
            'crispy_pages'
        );

        $params = [':category' => $categoryModel->getComputedUrl()];
        if ($categoryModel->getId() !== null) {
            $params[':id'] = $categoryModel->getId();
        }

        $statement = $this->getDatabaseConnector()->prepare($query);
        $statement->execute($params);

        Logger::getLogger(__METHOD__)
            ->info('Checking for slug collision', [
                'category' => $categoryModel->getComputedUrl(),
                'id' => $categoryModel->getId(),
                'result' => $statement->rowCount() > 0
            ]);

        return $statement->rowCount() > 0;
    }


    public function getCategoryById(int $id): ?CategoryModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getChildrenByParentCategory(CategoryModel $parent): array
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE parent = :parent LIMIT 1;', self::tableName));

        $statement->execute([':parent' => $parent->getId()]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function getCategoryBySlug(string $slug): ?CategoryModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE slug = :slug LIMIT 1;', self::tableName, $slug));

        $statement->execute([':slug' => $slug]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateCategory(CategoryModel $categoryModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update template, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $categoryModel->computeUrl();

        $Values[':name'] = $categoryModel->getName();
        $Values[':properties'] = $categoryModel->getPropertiesAsInt();
        $Values[':slug'] = $categoryModel->getSlug();
        $Values[':parent'] = $categoryModel->getParent() === null ? null : $categoryModel->getParent()->getId();
        #$Values[':updated_at'] = Carbon::now($_ENV['TZ'])->toDateTimeString();
        $Values[':id'] = $categoryModel->getId();
        $Values[':computed_url'] = $categoryModel->getComputedUrl();


        $Columns = [];
        foreach ($Values as $Column => $Value) {
            if ($Column !== ':id') {
                $Columns[] = substr($Column, 1) . ' = ' . $Column;
            }
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns)));

        return $statement->execute($Values);
    }

    /**
     * @return int
     */
    public function countAllCategories(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return CategoryModel[]
     */
    public function fetchAllCategoriesOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT * FROM %s ORDER BY $OrderCol $Order LIMIT $Limit OFFSET $Offset;", self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function generateJsTreeJson(bool $withPages = false): string
    {
        $categories = $this->fetchAllCategoriesWithoutParent();

        $jsTreeItems = new jsTreeItemModel(
            id: "category-id-root",
            text: Translation::fetch('CMSControl.Views.Categories.JsTree.Root_Tree'),
            icon: "fas fa-folder-open",
            state: new jsTreeStateModel(
                disabled: true,
            ),
            children: [],
            li_attr: []
        );

        if ($withPages) {
            foreach ((new PageDatabaseController())->fetchAllByCategory(null) as $page) {
                $jsTreeItems->addChildren($page->toJsTreeItem());
            }
        }

        foreach ($categories as $category) {
            $jsTreeItems->addChildren($category->toJsTreeItem($withPages));
        }

        return json_encode($jsTreeItems->toArray());
    }

    /**
     * Undocumented function
     *
     * @param string $Order
     * @param string $OrderCol
     * @return CategoryModel[]
     */
    public function fetchAllCategories(string $Order = 'ASC', string $OrderCol = 'id'): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT * FROM %s ORDER BY $OrderCol $Order;", self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    /**
     * Undocumented function
     *
     * @param string $Order
     * @param string $OrderCol
     * @return CategoryModel[]
     */
    public function fetchAllCategoriesWithoutParent(string $Order = 'ASC', string $OrderCol = 'id'): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT * FROM %s WHERE parent IS NULL ORDER BY $OrderCol $Order;", self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function fetchTemplatesCategories(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllCategories();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchTemplatesCategories($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllCategoriesOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertCategories(CategoryModel $categoryModel): CategoryModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create category, because no transaction is active.');
        }

        if ($categoryModel->getId() !== null) {
            throw new Exception('Category cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $categoryModel->computeUrl();

        $Values[':name'] = $categoryModel->getName();
        $Values[':properties'] = $categoryModel->getPropertiesAsInt();
        $Values[':slug'] = $categoryModel->getSlug();
        $Values[':parent'] = $categoryModel->getParent() === null ? null : $categoryModel->getParent()->getId();
        $Values[':computed_url'] = $categoryModel->getComputedUrl();
        #$Values[':updated_at'] = $categoryModel->getUpdatedAt()->toDateTimeString();

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        Logger::getLogger(__METHOD__)->info('SQL Query', [sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues))]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues)));

        if (!$statement->execute($Values)) {
            throw new Exception('Layout could not be created.');
        }

        return $this->getCategoryById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteCategory(CategoryModel $categoryModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $categoryModel->getId());

        return $statement->execute();
    }
}
