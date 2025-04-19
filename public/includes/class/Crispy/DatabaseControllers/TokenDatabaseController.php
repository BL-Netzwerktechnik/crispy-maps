<?php

namespace Crispy\DatabaseControllers;

use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\Enums\AccessTokens;
use Crispy\Models\CategoryModel;
use Crispy\Models\jsTreeItem;
use Crispy\Models\TokenModel;
use Exception;
use PDO;

class TokenDatabaseController extends DatabaseController
{


    private UserDatabaseController $userDatabaseController;
    

    private const tableName = 'crispy_tokens';
    public const rowsPerPage = 15;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
        
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): TokenModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new TokenModel(
            token: $row['token'],
            tokenType: AccessTokens::from($row['type']),
            user: $this->userDatabaseController->getUserById($row['user']),
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ']),
            expiresAt: Carbon::parse($row['expires_at'], $_ENV['TZ']),
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

    public function getTokenByToken(string $token): ?TokenModel
    {

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE token = :token LIMIT 1;', self::tableName));

        $statement->execute([':token' => $token]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function getTokenById(int $id): ?TokenModel
    {

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function updateToken(TokenModel $tokenModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update token, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s WHERE id = :id';
        $Values = [];

        $Values[':id'] = $tokenModel->getId();
        $Values[':token'] = $tokenModel->getToken();
        $Values[':token_type'] = $tokenModel->getTokenType()->value;
        $Values[':user'] = $tokenModel->getUser()->getId();
        $Values[':expires_at'] = $tokenModel->getExpiresAt()->toDateTimeString();


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
    public function countAllTokens(): int
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
    public function fetchAllTokens(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function insertToken(TokenModel $tokenModel): TokenModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create token, because no transaction is active.');
        }

        if ($tokenModel->getId() !== null) {
            throw new Exception('Token cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':token'] = $tokenModel->getToken();
        $Values[':token_type'] = $tokenModel->getTokenType()->value;
        $Values[':user'] = $tokenModel->getUser()->getId();
        $Values[':expires_at'] = $tokenModel->getExpiresAt()->toDateTimeString();
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
            throw new Exception('Token could not be created.');
        }

        return $this->getTokenById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteToken(TokenModel $tokenModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete token, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $tokenModel->getId());

        return $statement->execute();
    }
}
