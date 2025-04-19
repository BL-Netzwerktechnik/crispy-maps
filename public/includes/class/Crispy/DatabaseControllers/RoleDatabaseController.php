<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\Enums\Permissions;
use Crispy\Models\jsTreeItem;
use Crispy\Models\jsTreeItemModel;
use Crispy\Models\jsTreeStateModel;
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use Crispy\Models\RoleModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class RoleDatabaseController extends DatabaseController
{

    private const tableName = 'cmscontrol_roles';
    public const rowsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
    }

    public static function getDefaultRole(): RoleModel
    {
        return new RoleModel(
            id: -1,
            name: '',
            permissions: null,
        );
    }

    private function ConvertRowToClass(array $row): RoleModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new RoleModel(
            name: $row['name'],
            permissions: $row['permissions'],
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ']),
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

    public function getRoleById(int $id): ?RoleModel
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateRole(RoleModel $RoleModel): bool
    {
        if ($RoleModel->getId() === 0) {
            throw new Exception('Cannot update system role');
        }
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update user, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];


        $Values[':name'] = $RoleModel->getName();
        $Values[':permissions'] = $RoleModel->getPermissions();
        $Values[':id'] = $RoleModel->getId();

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
    public function countAllRoles(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return RoleModel[]
     */
    public function fetchAllRolesOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return RoleModel[]
     */
    public function fetchAllRoles(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function fetchRolesPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllRoles();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchRolesPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllRolesOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertRole(RoleModel $RoleModel): RoleModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create user, because no transaction is active.');
        }

        if ($RoleModel->getId() !== null) {
            throw new Exception('User cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':name'] = $RoleModel->getName();
        $Values[':permissions'] = $RoleModel->getPermissions();

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

        return $this->getRoleById($this->getDatabaseConnector()->lastInsertId());
    }

    

    public function generateJsTreeJson(bool $withUsers = false): string
    {
        $roles = $this->fetchAllRoles();

        
        $jsTreeItems = new jsTreeItemModel(
            id: "role-id-root",
            text: $withUsers ? Translation::fetch('CMSControl.Views.Users.JsTree.Root_Tree') : Translation::fetch('CMSControl.Views.Roles.JsTree.Root_Tree'),
            icon: "fas fa-scroll",
            state: new jsTreeStateModel(
                opened: true,
                disabled: true,
            ),
            children: [],
            li_attr: []
        );

        foreach ($roles as $role) {
            $jsTreeItems->addChildren($role->toJsTreeItem($withUsers));
        }

        return json_encode($jsTreeItems->toArray());
    }

    public function deleteRole(RoleModel $RoleModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete user, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $RoleModel->getId());

        return $statement->execute();
    }
}
