<?php

namespace blfilme\lostplaces\DatabaseControllers;

use blfilme\lostplaces\Models\LocationModel;
use blfilme\lostplaces\Models\VoteModel;
use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\DatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Models\UserModel;

class VoteDatabaseController extends DatabaseController
{

    private UserDatabaseController $userDatabaseController;
    private LocationDatabaseController $locationDatabaseController;
    private const tableName = 'lostplaces_votes';
    public const rowsPerPage = 15;

    public function __construct()
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $this->userDatabaseController = new UserDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): VoteModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new VoteModel(
            id: $row['id'],
            user: $row['author'] ? $this->userDatabaseController->getUserById($row['author']) : null,
            location: $this->locationDatabaseController->getLocationById($row['location']),
            ipAddress: $row['ip_address'],
            vote: $row['vote'] === '1',
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ'] ?? 'UTC'),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ'] ?? 'UTC'),
        );
    }

    public function deleteVoteByLocationAndIpAddress(LocationModel $locationModel, string $ipAddress): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete template, because no transaction is active.');
        }
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Deleting vote by location and IP address', ['location' => $locationModel->getId(), 'ipAddress' => $ipAddress]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE location = :location AND ip_address = :ip_address;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':ip_address' => $ipAddress,
        ]);

        return $statement->rowCount() > 0;
    }

    public function upVoteExistsByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if upvote exists by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND author = :user AND vote = 1 LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function downVoteExistsByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if downvote exists by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND author = :user AND vote = 0 LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function upVoteExistsByLocationAndIpAddress(LocationModel $locationModel, string $ipAddress): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if upvote exists by location and IP address', ['location' => $locationModel->getId(), 'ipAddress' => $ipAddress]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND ip_address = :ip_address AND vote = 1 LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':ip_address' => $ipAddress,
        ]);

        return $statement->rowCount() > 0;
    }

    public function downVoteExistsByLocationAndIpAddress(LocationModel $locationModel, string $ipAddress): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if downvote exists by location and IP address', ['location' => $locationModel->getId(), 'ipAddress' => $ipAddress]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND ip_address = :ip_address AND vote = 0 LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':ip_address' => $ipAddress,
        ]);

        return $statement->rowCount() > 0;
    }

    public function locationHasVotes(LocationModel $locationModel): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if location has votes', ['location' => $locationModel->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function voteExistsByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if vote exists by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND author = :user LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function deleteVoteByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete template, because no transaction is active.');
        }
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Deleting vote by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE location = :location AND author = :user;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function voteExistByLocationAndIpAddress(LocationModel $locationModel, string $ipAddress): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if vote exists by location and IP address', ['location' => $locationModel->getId(), 'ipAddress' => $ipAddress]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND ip_address = :ip_address LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':ip_address' => $ipAddress,
        ]);

        return $statement->rowCount() > 0;
    }

    public function getVoteyById(int $id): ?VoteModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Getting category by ID', ['id' => $id]);

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function countAllUpVotesForLocation(LocationModel $locationModel): int
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND vote = 1', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        return $statement->rowCount();
    }

    public function countAllDownVotesForLocation(LocationModel $locationModel): int
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND vote = 0', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        return $statement->rowCount();
    }

    public function countAllVotesForLocation(LocationModel $locationModel): int
    {
        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        return $statement->rowCount();
    }

    /**
     * @return int
     */
    public function countAllVotes(): int
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s', self::tableName));

        return $statement->rowCount();
    }

    /**
     * Undocumented function.
     *
     * @param  string      $Order
     * @param  string      $OrderCol
     * @return VoteModel[]
     */
    public function fetchAllVotes(string $Order = 'ASC', string $OrderCol = 'id'): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT * FROM %s ORDER BY $OrderCol $Order;", self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function insertVote(VoteModel $voteModel): VoteModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot create vote, because no transaction is active.');
        }

        if ($voteModel->getId() !== null) {
            throw new \Exception('Navbar cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':location'] = $voteModel->getLocation()->getId();
        $Values[':author'] = $voteModel->getUser() !== null ? $voteModel->getUser()->getId() : null;
        $Values[':ip_address'] = $voteModel->getIpAddress();
        $Values[':vote'] = $voteModel->getVote() ? 1 : 0;
        $Values[':updated_at'] = $voteModel->getUpdatedAt()->toDateTimeString();

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        Logger::getLogger(__METHOD__)->info('SQL Query', [sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues))]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues)));

        if (!$statement->execute($Values)) {
            throw new \Exception('Layout could not be created.');
        }

        return $this->getVoteyById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteByLocation(LocationModel $locationModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE location = :location;', self::tableName));

        $statement->bindValue(':location', $locationModel->getId());

        return $statement->execute();
    }

    public function deleteVote(VoteModel $voteModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $voteModel->getId());

        return $statement->execute();
    }
}
