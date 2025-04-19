<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class TemplateDatabaseController extends DatabaseController
{

    private const tableName = 'crispy_templates';
    public const rowsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): TemplateModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new TemplateModel(
            name: $row['name'],
            content: $row['content'],
            author: $row['author'],
            directory: $row['directory'],
            slug: $row['slug'],
            layout: $row['layout'],
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

    /**
     * Undocumented function
     *
     * @param LayoutModel $layout
     * @return LayoutModel[]
     */
    public function fetchTemplatesByLayout(LayoutModel $layout): array
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE layout = %s LIMIT 1;', self::tableName, $layout->getId()));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function getTemplateById(int $id): ?TemplateModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getTemplateBySlug(string $slug): ?TemplateModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE slug = :slug LIMIT 1;', self::tableName, $slug));

        $statement->execute([':slug' => $slug]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateTemplate(TemplateModel $templateModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update template, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':name'] = $templateModel->getName();
        $Values[':content'] = $templateModel->getContent();
        $Values[':author'] = $templateModel->getAuthor();
        $Values[':directory'] = $templateModel->getDirectory();
        $Values[':slug'] = $templateModel->getSlug();
        $Values[':layout'] = $templateModel->getLayout()?->getId();
        $Values[':updated_at'] = Carbon::now($_ENV['TZ'])->toDateTimeString();
        $Values[':id'] = $templateModel->getId();

        $Columns = [];
        foreach ($Values as $Column => $Value) {
            if ($Column !== ':id') {
                $Columns[] = substr($Column, 1) . ' = ' . $Column;
            }
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns)));

        return $statement->execute($Values);
    }

    public function fetchAllTemplatesInDirectory(?string $directory): array
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE directory = :directory;', self::tableName));

        $statement->execute([':directory' => $directory]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function buildVirtualFileSystem(?string $directory): array
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf("SELECT * FROM %s WHERE (directory LIKE :directory OR directory LIKE :directory_level_two) AND directory NOT LIKE :directory_level_three;", self::tableName));

        if($directory == "/") {
            $directory = null;
        }

        $parameters = [
            ':directory' => sprintf('%s', $directory),
            ':directory_level_two' => sprintf('%s/%%', $directory),
            ':directory_level_three' => sprintf('%s/%%/%%', $directory)
        ];

        $statement->execute($parameters);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {

            if (!isset($_rows['directories'][$Row['directory']])) {
                $_rows['directories'][$Row['directory']] = [];
            }
            $_rows["files"][] = $this->ConvertRowToClass($Row);
        }


        return $_rows;
    }

    /**
     * @return int
     */
    public function countAllTemplates(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return TemplateModel[]
     */
    public function fetchAllTemplatesOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return TemplateModel[]
     */
    public function fetchAllTemplates(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function fetchTemplatesPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllTemplates();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchTemplatesPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllTemplatesOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertTemplates(TemplateModel $templateModel): TemplateModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create template, because no transaction is active.');
        }

        if ($templateModel->getId() !== null) {
            throw new Exception('Template cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':name'] = $templateModel->getName();
        $Values[':content'] = $templateModel->getContent();
        $Values[':author'] = $templateModel->getAuthor();
        $Values[':slug'] = $templateModel->getSlug();
        $Values[':directory'] = $templateModel->getDirectory();
        $Values[':layout'] = $templateModel->getLayout()?->getId();
        $Values[':updated_at'] = $templateModel->getUpdatedAt()->toDateTimeString();

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

        return $this->getTemplateById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteTemplate(TemplateModel $templateModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $templateModel->getId());

        return $statement->execute();
    }
}
