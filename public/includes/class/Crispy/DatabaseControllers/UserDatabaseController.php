<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use Crispy\Models\UserModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class UserDatabaseController extends DatabaseController
{
    private RoleDatabaseController $roleDatabaseController;

    private const tableName = 'cmscontrol_users';
    public const rowsPerPage = 15;

    public function __construct()
    {
        $this->roleDatabaseController = new RoleDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): UserModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new UserModel(
            username: $row['username'],
            password: $row['password'],
            name: $row['name'],
            email: $row['email'],
            emailVerified: $row['email_verified'],
            role: $row['role'] !== null ? $this->roleDatabaseController->getRoleById($row['role']) : null,
            lastLogin: $row['last_login'] !== null ? Carbon::parse($row['last_login'], $_ENV['TZ']): null,
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

    public function getUserById(int $id): ?UserModel
    {
        if($id === 0){
            return UserModel::fetchSystemUser();
        }

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    
    /**
     * Undocumented function
     *
     * @param integer $id
     * @return UserModel[]
     */
    public function fetchAllUsersByRoleId(int $id): array
    {
        if($id === 0){
            return UserModel::fetchSystemUser();
        }

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE role = %s;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }


    public function getUserByUsername(string $username): ?UserModel
    {

        if($username === "system"){
            return UserModel::fetchSystemUser();
        }
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE username = :username LIMIT 1;', self::tableName));

        $statement->execute([':username' => $username]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getUserByEmail(string $email): ?UserModel
    {

        if($email === "system@crispcms.invalid"){
            return UserModel::fetchSystemUser();
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE email = :email LIMIT 1;', self::tableName));

        $statement->execute([':email' => $email]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateUser(UserModel $userModel): bool
    {
        if($userModel->getId() === 0){
            throw new Exception('Cannot update system user');
        }
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update user, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':username'] = $userModel->getUsername();
        $Values[':password'] = $userModel->getPassword();
        $Values[':name'] = $userModel->getName();
        $Values[':email'] = $userModel->getEmail();
        $Values[':email_verified'] = (int)$userModel->getEmailVerified();
        $Values[':last_login'] = $userModel->getLastLogin()?->toDateTimeString();
        $Values[':updated_at'] = Carbon::now($_ENV['TZ'])->toDateTimeString();
        $Values[':role'] = $userModel->getRole()->getId();
        $Values[':id'] = $userModel->getId();

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
    public function countAllUsers(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * @return UserModel[]
     */
    public function fetchAllUsersOffset(int $Limit, int $Offset, string $Order = 'ASC', string $OrderCol = 'id'): array
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
     * @return UserModel[]
     */
    public function fetchAllUsers(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function fetchUsersPages(int $rowsPerPage = self::rowsPerPage)
    {
        $rows = $this->countAllUsers();

        return ceil($rows / $rowsPerPage);
    }

    public function fetchAllPerPage(int $Page = 1, int $rowsPerPage = self::rowsPerPage, string $Order = 'ASC', string $OrderCol = 'id')
    {
        if ($Page < 1 || $Page > $this->fetchUsersPages($rowsPerPage)) {
            return [];
        }

        $offset = ($Page === 1 ? 0 : ($Page - 1) * $rowsPerPage);

        return $this->fetchAllUsersOffset($rowsPerPage, $offset, $Order, $OrderCol);
    }

    public function insertUser(UserModel $userModel): UserModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create user, because no transaction is active.');
        }

        if ($userModel->getId() !== null) {
            throw new Exception('User cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':username'] = $userModel->getUsername();
        $Values[':password'] = $userModel->getPassword();
        $Values[':name'] = $userModel->getName();
        $Values[':email'] = $userModel->getEmail();
        $Values[':role'] = $userModel->getRole()->getId();
        $Values[':email_verified'] = (int)$userModel->getEmailVerified();
        $Values[':last_login'] = $userModel->getLastLogin()?->toDateTimeString();

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

        return $this->getUserById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteUser(UserModel $userModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete user, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $userModel->getId());

        return $statement->execute();
    }
}
