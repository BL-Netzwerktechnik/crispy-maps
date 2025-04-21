<?php

namespace blfilme\lostplaces\DatabaseControllers;

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Enums\MarkerColors;
use blfilme\lostplaces\Models\CategoryModel;
use blfilme\lostplaces\Models\CoordinateModel;
use blfilme\lostplaces\Models\LocationModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\DatabaseControllers\DatabaseController;
use Exception;
use PDO;

class LocationDatabaseController extends DatabaseController
{

    private CategoryDatabaseController $categoryDatabaseController;

    private const tableName = 'lostplaces_locations';
    public const rowsPerPage = 15;

    public function __construct()
    {
        $this->categoryDatabaseController = new CategoryDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): LocationModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Converting row to class', $row);
        return new LocationModel(
            id: $row['id'],
            name: $row['name'],
            description: $row['description'],
            properties: LocationProperties::fromIntToArray($row['properties']) ?? [],
            category: $this->categoryDatabaseController->getCategoryById($row['category']),
            status: LocationStatus::tryFrom($row['status']) ?? LocationStatus::UNKNOWN,
            coordinates: new CoordinateModel($row['latitude'], $row['longitude']),
            author: 0,
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ'] ?? 'UTC'),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ'] ?? 'UTC'),
        );
    }
    public function getLocationById(int $id): ?LocationModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Getting location by ID', ['id' => $id]);

        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT *, ST_Y(marker_location) AS latitude, ST_X(marker_location) AS longitude FROM %s WHERE id = %s LIMIT 1;', self::tableName, $id));

        if ($statement->rowCount() === 0) {
            return null;
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->ConvertRowToClass($row);
    }


    public function updateLocation(LocationModel $locationModel): bool
    {
        if ($this->isStrictTransaction() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot update category, because no transaction is active.');
        }

        $SQLTemplate = 'UPDATE %s SET %s marker_location = %s, 4326) WHERE id = :id';
        $Values = [];

        $Values[':id'] = $locationModel->getId();
        $Values[':name'] = $locationModel->getName();
        $Values[':description'] = $locationModel->getDescription();
        $Values[':category'] = $locationModel->getCategory()->getId();
        $Values[':status'] = $locationModel->getStatus()->value;
        $Values[':properties'] = LocationProperties::fromArrayToInt($locationModel->getProperties());
        $Values[':author'] = 0; // TODO: Implement author
        $Values[':updated_at'] = $locationModel->getUpdatedAt()->toDateTimeString();

        $Columns = [];
        foreach ($Values as $Column => $Value) {
            if ($Column !== ':id') {
                $Columns[] = substr($Column, 1) . ' = ' . $Column;
            }
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), $locationModel->getCoordinates()->toPostGIS()));

        return $statement->execute($Values);
    }

    /**
     * @return int
     */
    public function countAllLocations(): int
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
    public function fetchAllLocations(string $Order = 'ASC', string $OrderCol = 'id'): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT *, ST_Y(marker_location) AS latitude, ST_X(marker_location) AS longitude FROM %s ORDER BY $OrderCol $Order;", self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function insertLocation(LocationModel $locationModel): LocationModel
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot create category, because no transaction is active.');
        }

        if ($locationModel->getId() !== null) {
            throw new Exception('LocationModel cannot be created, because it contains an ID.');
        }

        $SQLTemplate = 'INSERT INTO %s (%s, marker_location) VALUES (%s, %s)';
        $Values = [];


        $Values[':name'] = $locationModel->getName();
        $Values[':description'] = $locationModel->getDescription();
        $Values[':category'] = $locationModel->getCategory()->getId();
        $Values[':status'] = $locationModel->getStatus()->value;
        $Values[':properties'] = LocationProperties::fromArrayToInt($locationModel->getProperties());
        $Values[':author'] = 0; // TODO: Implement author

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        Logger::getLogger(__METHOD__)->info('SQL Query', [sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues)), $locationModel->getCoordinates()->toPostGIS()]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues), $locationModel->getCoordinates()->toPostGIS()));

        if (!$statement->execute($Values)) {
            throw new Exception('Layout could not be created.');
        }

        return $this->getLocationById($this->getDatabaseConnector()->lastInsertId());
    }

    public function deleteLocation(LocationModel $locationModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $locationModel->getId());

        return $statement->execute();
    }
}
