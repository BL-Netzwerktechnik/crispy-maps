<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\Models\LayoutModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class LayoutDatabaseController extends DatabaseController
{

    private const tableName = 'crispy_layouts';
    public const rowsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): LayoutModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new LayoutModel(
            name: $row['name'],
            content: $row['content'],
            author: $row['author'],
            slug: $row['slug'],
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

    public function getLayoutById(int $id): ?LayoutModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getLayoutBySlug(string $slug): ?LayoutModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE slug = :slug LIMIT 1;', self::tableName, $slug));

        $statement->execute([':slug' => $slug]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateLayout(LayoutModel $layoutModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update layout, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':name'] = $layoutModel->getName();
        $Values[':content'] = $layoutModel->getContent();
        $Values[':author'] = $layoutModel->getAuthor();
        $Values[':updated_at'] = Carbon::now($_ENV['TZ'])->toDateTimeString();
        $Values[':id'] = $layoutModel->getId();

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
    public function countAllLayouts(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return PropertyModel[]
     */
    public function fetchAllLayoutsOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return LayoutModel[]
     */
    public function fetchAllLayouts(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function fetchLayoutsPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllLayouts();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchLayoutsPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllLayoutsOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertLayout(LayoutModel $layoutModel): LayoutModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create layout, because no transaction is active.');
        }

        if ($layoutModel->getId() !== null) {
            throw new Exception('Property cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':name'] = $layoutModel->getName();
        $Values[':content'] = $layoutModel->getContent();
        $Values[':author'] = $layoutModel->getAuthor();
        $Values[':slug'] = $layoutModel->getSlug();
        $Values[':updated_at'] = $layoutModel->getUpdatedAt()->toDateTimeString();

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

        return $this->getLayoutById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteLayout(LayoutModel $layoutModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete layout, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $layoutModel->getId());

        return $statement->execute();
    }
}
