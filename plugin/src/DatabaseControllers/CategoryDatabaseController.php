<?php

namespace blfilme\lostplaces\DatabaseControllers;

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\Enums\MarkerColors;
use blfilme\lostplaces\Models\CategoryModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\DatabaseControllers\DatabaseController;
use Exception;
use PDO;

class CategoryDatabaseController extends DatabaseController
{

    private const tableName = 'lostplaces_categories';
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
            id: $row['id'],
            name: $row['name'],
            description: $row['description'],
            icon: IconProviderController::fetchFromConfig($row['icon']),
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ'] ?? 'UTC'),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ'] ?? 'UTC'),
        );
    }
    public function getCategoryById(int $id): ?CategoryModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Getting category by ID', ['id' => $id]);

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }


    public function updateCategory(CategoryModel $categoryModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update category, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':id'] = $categoryModel->getId();
        $Values[':name'] = $categoryModel->getName();
        $Values[':description'] = $categoryModel->getDescription();
        $Values[':icon'] = $categoryModel->getIcon()->getName();
        $Values[':updated_at'] = $categoryModel->getUpdatedAt()->toDateTimeString();


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

    public function insertCategory(CategoryModel $categoryModel): CategoryModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create category, because no transaction is active.');
        }

        if ($categoryModel->getId() !== null) {
            throw new Exception('Navbar cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];


        $Values[':name'] = $categoryModel->getName();
        $Values[':description'] = $categoryModel->getDescription();
        $Values[':icon'] = $categoryModel->getIcon()->getName();
        $Values[':updated_at'] = $categoryModel->getUpdatedAt()->toDateTimeString();

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
