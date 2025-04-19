<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\Enums\NavbarLinkTypes;
use Crispy\Models\CategoryModel;
use Crispy\Models\CrispyNavbarItemModel;
use Crispy\Models\jsTreeItem;
use Crispy\Models\jsTreeItemModel;
use Crispy\Models\jsTreeStateModel;
use Exception;
use PDO;

class NavbarDatabaseController extends DatabaseController
{

    private const tableName = 'crispy_navbar';
    public const rowsPerPage = 15;

    public function __construct()
    {
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): CrispyNavbarItemModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new CrispyNavbarItemModel(
            text: $row['text'],
            properties: $row['properties'],
            parent: $row['parent'] === null ? null : $this->getNavbarById($row['parent']),
            type: NavbarLinkTypes::from($row['type']),
            icon: $row['icon'],
            target: $row['target'],
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

    public function getNavbarById(int $id): ?CrispyNavbarItemModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getChildrenByParentNavbar(CrispyNavbarItemModel $parent): array
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE parent = :parent ORDER BY sort_order ASC LIMIT 1;', self::tableName));

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

    public function updateNavbar(CrispyNavbarItemModel $crispyNavbarItemModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update navbar, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':text'] = $crispyNavbarItemModel->getText();
        $Values[':properties'] = $crispyNavbarItemModel->getPropertiesAsInt();
        $Values[':parent'] = $crispyNavbarItemModel->getParent() === null ? null : $crispyNavbarItemModel->getParent()->getId();
        $Values[':type'] = $crispyNavbarItemModel->getType()->value;
        $Values[':icon'] = $crispyNavbarItemModel->getIcon();
        $Values[':target'] = $crispyNavbarItemModel->isTargetUrl() ? $crispyNavbarItemModel->getTarget() : $crispyNavbarItemModel->getTarget()->getId();
        $Values[':id'] = $crispyNavbarItemModel->getId();


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
    public function countAllNavbars(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    public function generateJsTreeJson(): string
    {
        $navbars = $this->fetchAllNavbarsWithoutParent(OrderCol: 'sort_order', Order: 'ASC');

        $jsTreeItems = new jsTreeItemModel(
            id: "navbar-id-root",
            text: Translation::fetch('CMSControl.Views.Navbar.JsTree.Root_Tree'),
            icon: "fas fa-folder-open",
            state: new jsTreeStateModel(
                disabled: true,
            ),
            children: [],
            li_attr: []
        );

        foreach ($navbars as $navbar) {
            $jsTreeItems->addChildren($navbar->toJsTreeItem());
        }

        return json_encode($jsTreeItems->toArray());
    }

    /**
     * Undocumented function
     *
     * @param string $Order
     * @param string $OrderCol
     * @return CrispyNavbarItemModel[]
     */
    public function fetchAllNavbars(string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return CrispyNavbarItemModel[]
     */
    public function fetchAllNavbarsWithoutParent(string $Order = 'ASC', string $OrderCol = 'id'): array
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


    public function insertNavbar(CrispyNavbarItemModel $crispyNavbarItemModel): CrispyNavbarItemModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create category, because no transaction is active.');
        }

        if ($crispyNavbarItemModel->getId() !== null) {
            throw new Exception('Navbar cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];


        $Values[':text'] = $crispyNavbarItemModel->getText();
        $Values[':properties'] = $crispyNavbarItemModel->getPropertiesAsInt();
        $Values[':parent'] = $crispyNavbarItemModel->getParent() === null ? null : $crispyNavbarItemModel->getParent()->getId();
        $Values[':type'] = $crispyNavbarItemModel->getType()->value;
        $Values[':icon'] = $crispyNavbarItemModel->getIcon();
        $Values[':target'] = $crispyNavbarItemModel->isTargetUrl() ? $crispyNavbarItemModel->getTarget() : $crispyNavbarItemModel->getTarget()->getId();

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

        return $this->getNavbarById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteNavbar(CrispyNavbarItemModel $crispyNavbarItemModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $crispyNavbarItemModel->getId());

        return $statement->execute();
    }
}
