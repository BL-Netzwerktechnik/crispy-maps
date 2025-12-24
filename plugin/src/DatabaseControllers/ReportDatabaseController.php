<?php

namespace blfilme\lostplaces\DatabaseControllers;

use blfilme\lostplaces\Enums\ReportReasons;
use blfilme\lostplaces\Models\LocationModel;
use blfilme\lostplaces\Models\ReportModel;
use blfilme\lostplaces\Models\VoteModel;
use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\DatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Models\UserModel;

class ReportDatabaseController extends DatabaseController
{

    private UserDatabaseController $userDatabaseController;
    private LocationDatabaseController $locationDatabaseController;
    private const tableName = 'lostplaces_reports';
    public const rowsPerPage = 15;

    public function __construct()
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $this->userDatabaseController = new UserDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): ReportModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);

        return new ReportModel(
            id: $row['id'],
            user: $row['author'] ? $this->userDatabaseController->getUserById($row['author']) : null,
            location: $this->locationDatabaseController->getLocationById($row['location']),
            ipAddress: $row['ip_address'],
            description: $row['description'],
            reasons: ReportReasons::fromIntToArray($row['reason']),
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ'] ?? 'UTC'),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ'] ?? 'UTC'),
        );
    }

    public function reportExistsByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if report exists by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND author = :user LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function deleteReportByLocationAndUser(LocationModel $locationModel, UserModel $user): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete template, because no transaction is active.');
        }
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Deleting report by location and user', ['location' => $locationModel->getId(), 'userId' => $user->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE location = :location AND author = :user;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':user' => $user->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function locationHasReports(LocationModel $locationModel): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if location has reports', ['location' => $locationModel->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        return $statement->rowCount() > 0;
    }

    public function fetchReportsByLocation(LocationModel $locationModel): array
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Fetching reports by location', ['location' => $locationModel->getId()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location ORDER BY created_at DESC;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
        ]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function reportExistByLocationAndIpAddress(LocationModel $locationModel, string $ipAddress): bool
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Checking if report exists by location and IP address', ['location' => $locationModel->getId(), 'ipAddress' => $ipAddress]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT * FROM %s WHERE location = :location AND ip_address = :ip_address LIMIT 1;', self::tableName));

        $statement->execute([
            ':location' => $locationModel->getId(),
            ':ip_address' => $ipAddress,
        ]);

        return $statement->rowCount() > 0;
    }

    public function getReportById(int $id): ?ReportModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Getting report by ID', ['id' => $id]);

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT * FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }

    public function countAllReportsForLocation(LocationModel $locationModel): int
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
    public function countAllReports(): int
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
    public function fetchAllReports(string $Order = 'ASC', string $OrderCol = 'id'): array
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

    public function insertReport(ReportModel $reportModel): ReportModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot create report, because no transaction is active.');
        }

        if ($reportModel->getId() !== null) {
            throw new \Exception('Report cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s) VALUES (%s)';
        $Values = [];

        $Values[':location'] = $reportModel->getLocation()->getId();
        $Values[':author'] = $reportModel->getUser() !== null ? $reportModel->getUser()->getId() : null;
        $Values[':description'] = $reportModel->getDescription();
        $Values[':reason'] = $reportModel->getReasonsInt();
        $Values[':ip_address'] = $reportModel->getIpAddress();
        $Values[':updated_at'] = $reportModel->getUpdatedAt()->toDateTimeString();

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        Logger::getLogger(__METHOD__)->info('SQL Query', [sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues))]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues)));

        if (!$statement->execute($Values)) {
            throw new \Exception('Report could not be created.');
        }

        return $this->getReportById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteByLocation(LocationModel $locationModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete report, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE location = :location;', self::tableName));

        $statement->bindValue(':location', $locationModel->getId());

        return $statement->execute();
    }

    public function deleteReport(ReportModel $reportModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new \Exception('Cannot delete report, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $reportModel->getId());

        return $statement->execute();
    }
}
