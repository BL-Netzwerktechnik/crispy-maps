<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\Enums\PageProperties;
use Crispy\Models\CategoryModel;
use Crispy\Models\LayoutModel;
use Crispy\Models\PageModel;
use Crispy\Models\TemplateModel;
use Exception;
use PDO;

class PageDatabaseController extends DatabaseController
{

    private const tableName = 'crispy_pages';
    public const rowsPerPage = 15;

    private CategoryDatabaseController $categoryDatabaseController;

    public function __construct()
    {
        $this->categoryDatabaseController = new CategoryDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): PageModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new PageModel(
            name: $row['name'],
            content: $row['content'],
            author: $row['author'],
            properties: $row['properties'],
            slug: $row['slug'],
            category: $row['category'] === null ? null : $this->categoryDatabaseController->getCategoryById($row['category']),
            template: $row['template'],
            computedUrl: $row['computed_url'],
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

    public function getPageById(int $id): ?PageModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getPageByComputedUrl(string $computed_url): ?PageModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE computed_url = :computed_url LIMIT 1;', self::tableName));

        $statement->execute([':computed_url' => $computed_url]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getPageBySlug(string $slug): ?PageModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE slug = :slug LIMIT 1;', self::tableName, $slug));

        $statement->execute([':slug' => $slug]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }


    public function checkSlugCollision(PageModel $pageModel): bool
    {
        $query = sprintf(
            '(SELECT id FROM %s WHERE computed_url = :category %s) 
         UNION 
         (SELECT id FROM %s WHERE computed_url = :category)',
            self::tableName,
            $pageModel->getId() !== null ? 'AND id != :id' : '',
            'crispy_categories'
        );

        $params = [':category' => $pageModel->getComputedUrl()];
        if ($pageModel->getId() !== null) {
            $params[':id'] = $pageModel->getId();
        }

        $statement = $this->getDatabaseConnector()->prepare($query);
        $statement->execute($params);

        Logger::getLogger(__METHOD__)
            ->info('Checking for slug collision', [
                'category' => $pageModel->getComputedUrl(),
                'id' => $pageModel->getId(),
                'result' => $statement->rowCount() > 0
            ]);

        return $statement->rowCount() > 0;
    }

    public function updatePage(PageModel $pageModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update page, because no transaction is active.');
        }

        $pageModel->computeUrl();

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':name'] = $pageModel->getName();
        $Values[':content'] = $pageModel->getContent();
        $Values[':author'] = $pageModel->getAuthor();
        $Values[':properties'] = $pageModel->getProperties();
        $Values[':slug'] = $pageModel->getSlug();
        $Values[':template'] = $pageModel->getTemplate()?->getId();
        $Values[':updated_at'] = Carbon::now($_ENV['TZ'])->toDateTimeString();
        $Values[':id'] = $pageModel->getId();
        $Values[':category'] = $pageModel->getCategory()?->getId();
        $Values[':computed_url'] = $pageModel->getComputedUrl();

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
    public function countAllPages(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return PageModel[]
     */
    public function fetchAllPagesOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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


    /**
     * Undocumented function
     *
     * @param string $Order
     * @param string $OrderCol
     * @return PageModel[]
     */
    public function fetchAllPages(string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @param CategoryModel $category
     * @return PageModel[]
     */
    public function fetchAllByCategory(?CategoryModel $category): array
    {
        if ($category === null) {
            $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE category IS NULL;', self::tableName));
            $statement->execute();
        } else {
            $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE category = :category;', self::tableName));
            $statement->execute([':category' => $category->getId()]);
        }



        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }


    public function fetchPagesPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllPages();

        return ceil($rows / $rowsPerPage);
    }



    /**
     * Undocumented function
     *
     * @param TemplateModel $template
     * @return TemplateModel[]
     */
    public function fetchPagesByTemplate(TemplateModel $template): array
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE template = %s LIMIT 1;', self::tableName, $template->getId()));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchPagesPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllPagesOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertPage(PageModel $pageModel): PageModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create page, because no transaction is active.');
        }

        if ($pageModel->getId() !== null) {
            throw new Exception('Page cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $pageModel->computeUrl();

        $Values[':name'] = $pageModel->getName();
        $Values[':content'] = $pageModel->getContent();
        $Values[':author'] = $pageModel->getAuthor();
        $Values[':slug'] = $pageModel->getSlug();
        $Values[':properties'] = $pageModel->getProperties();
        $Values[':template'] = $pageModel->getTemplate()?->getId();
        $Values[':updated_at'] = $pageModel->getUpdatedAt()->toDateTimeString();
        $Values[':category'] = $pageModel->getCategory()?->getId();
        $Values[':computed_url'] = $pageModel->getComputedUrl();

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        Logger::getLogger(__METHOD__)->info('SQL Query', [sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues))]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues)));

        if (!$statement->execute($Values)) {
            throw new Exception('Page could not be created.');
        }

        return $this->getPageById($this->getDatabaseConnector()->lastInsertId());
    }

    /**
     * Undocumented function
     *
     * @param PageProperties $pageProperties
     * @return PageModel[]
     */
    public function fetchAllPagesByProperty(PageProperties $pageProperties): array
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf(
            'SELECT * FROM %s WHERE (properties & :property) = :property;',
            self::tableName
        ));

        $statement->execute([':property' => $pageProperties->value]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function fetchFrontpagePage(): ?PageModel
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf(
            'SELECT * FROM %s WHERE (properties & :property) = :property LIMIT 1;',
            self::tableName
        ));

        $statement->execute([':property' => PageProperties::OPTION_FRONTPAGE->value]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function deletePage(PageModel $pageModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete Page, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $pageModel->getId());

        return $statement->execute();
    }
}
