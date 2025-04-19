<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\Controllers\PluginController;
use Crispy\Enums\Permissions;
use Crispy\Models\LayoutModel;
use Crispy\Models\PluginModel;
use Crispy\Models\TemplateModel;
use Crispy\Models\RoleModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class PluginDatabaseController extends DatabaseController
{

    private PluginController $pluginController;


    private const tableName = 'cmscontrol_plugins';
    public const rowsPerPage = 15;

    public function __construct()
    {
        $this->pluginController = new PluginController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): null|PluginModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        if($this->pluginController->canLoadPlugin($row['name'])) {  
            return $this->pluginController->getPlugin($row['name']);
        }
        $this->beginTransaction();
        $this->deactivate($row['name']);
        $this->commitTransaction();
        return null;
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

    public function getPluginByPath(string $path): ?PluginModel
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE name = :path LIMIT 1;', self::tableName));

        $statement->execute([':path' => $path]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    /**
     * @return int
     */
    public function countAllPlugins(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return PluginModel[]
     */
    public function fetchAllPluginsOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return PluginModel[]
     */
    public function fetchAllPlugins(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function fetchPluginsPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllPlugins();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchPluginsPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllPluginsOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function activate(string $name): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot activate plugin, because no transaction is active.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':name'] = $name;

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

        return true;
    }

    public function deactivate(string $name): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete plugin, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE name = :name;', self::tableName));

        $statement->bindValue(':name', $name);

        return $statement->execute();
    }
}
