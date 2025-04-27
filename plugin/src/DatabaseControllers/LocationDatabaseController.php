<?php

namespace blfilme\lostplaces\DatabaseControllers;

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Enums\MarkerColors;
use blfilme\lostplaces\Models\CategoryModel;
use blfilme\lostplaces\Models\CoordinateModel;
use blfilme\lostplaces\Models\HeatMapModel;
use blfilme\lostplaces\Models\LocationDistanceModel;
use blfilme\lostplaces\Models\LocationModel;
use Carbon\Carbon;
use crisp\api\Translation;
use crisp\core\Logger;
use Crispy\DatabaseControllers\DatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Exception;
use PDO;

class LocationDatabaseController extends DatabaseController
{

    private CategoryDatabaseController $categoryDatabaseController;
    private UserDatabaseController $userDatabaseController;

    private const tableName = 'lostplaces_locations';
    public const rowsPerPage = 15;

    public function __construct()
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        $this->userDatabaseController = new UserDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        parent::__construct();
    }

    private function ConvertRowToClass(array $row): LocationModel
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        return new LocationModel(
            id: $row['id'],
            name: $row['name'],
            description: $row['description'],
            properties: LocationProperties::fromIntToArray($row['properties']) ?? [],
            youtube: $row['youtube'] ?? null,
            category: $this->categoryDatabaseController->getCategoryById($row['category']),
            status: LocationStatus::tryFrom($row['status']) ?? LocationStatus::UNKNOWN,
            coordinates: new CoordinateModel($row['latitude'], $row['longitude']),
            author: $this->userDatabaseController->getUserById($row['author']),
            createdAt: Carbon::parse($row['created_at'], $_ENV['TZ'] ?? 'UTC'),
            updatedAt: Carbon::parse($row['updated_at'], $_ENV['TZ'] ?? 'UTC'),
        );
    }

    /**
     * Undocumented function
     *
     * @param LocationModel $locationModel
     * @param integer $limit
     * @param integer $maxDistanceInKilometers
     * @return LocationDistanceModel[]
     */
    public function fetchNearestLocations(LocationModel $locationModel, int $limit = 10, int $maxDistanceInKilometers = 100): array
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Fetching nearest locations', ['location' => $locationModel->getId(), 'limit' => $limit, 'maxDistance' => $maxDistanceInKilometers]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT 
  l.id,
  ST_DistanceSphere(l.marker_location, ref.marker_location) / 1000 AS distance_km
FROM 
  %s l,
  %s ref
WHERE 
  ref.id = :locationId
  AND l.id != ref.id
  AND ST_DistanceSphere(l.marker_location, ref.marker_location) <= :maxDistance * 1000
ORDER BY 
  distance_km
LIMIT %s;', self::tableName, self::tableName, $limit));

        $statement->execute([
            ':locationId' => $locationModel->getId(),
            ':maxDistance' => $maxDistanceInKilometers,
        ]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];


        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = new LocationDistanceModel(
                location: $this->getLocationById($Row['id']),
                distance: $Row['distance_km'],
            );
        }

        return $_rows;
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

        $SQLTemplate = 'UPDATE %s SET %s, marker_location = %s WHERE id = :id';
        $Values = [];

        $Values[':id'] = $locationModel->getId();
        $Values[':name'] = $locationModel->getName();
        $Values[':description'] = $locationModel->getDescription();
        $Values[':category'] = $locationModel->getCategory()->getId();
        $Values[':youtube'] = $locationModel->getYoutube();
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


    public function moveAllLocationsToNewCategory(CategoryModel $oldCategory, CategoryModel $newCategory): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot move locations, because no transaction is active.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('UPDATE %s SET category = :category WHERE category = :oldCategory;', self::tableName));

        $statement->bindValue(':category', $newCategory->getId());
        $statement->bindValue(':oldCategory', $oldCategory->getId());

        return $statement->execute();
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
        $statement = $this->getDatabaseConnector()->query(sprintf("SELECT *, ST_Y(marker_location) AS latitude, ST_X(marker_location) AS longitude FROM %s ORDER BY $OrderCol $Order LIMIT 500;", self::tableName));

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
     * @return CoordinateModel[]
     */
    public function fetchAllLocationsCoordinates(): array
    {
        $statement = $this->getDatabaseConnector()->query(sprintf('SELECT ST_Y(marker_location) AS latitude, ST_X(marker_location) AS longitude FROM %s;', self::tableName));

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = new CoordinateModel($Row['latitude'], $Row['longitude']);
        }

        return $_rows;
    }




    /**
     * Undocumented function
     *
     * @param float $minLat
     * @param float $maxLat
     * @param float $minLon
     * @param float $maxLon
     * @return HeatMapModel[]
     */
    public function fetchHeatmapByBoundary(float $minLat, float $maxLat, float $minLon, float $maxLon): array
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Fetching all locations by boundary', ['minLat' => $minLat, 'maxLat' => $maxLat, 'minLon' => $minLon, 'maxLon' => $maxLon]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT AVG(ST_Y(marker_location)) AS latitude, AVG(ST_X(marker_location)) AS longitude, COUNT(*) AS weight FROM %s WHERE ST_Intersects(marker_location,ST_MakeEnvelope(:minLon, :minLat, :maxLon, :maxLat, 4326)) GROUP BY ST_SnapToGrid(marker_location, 0.01, 0.01);', self::tableName));

        $statement->execute([
            ':minLat' => $minLat,
            ':maxLat' => $maxLat,
            ':minLon' => $minLon,
            ':maxLon' => $maxLon,
        ]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = new HeatMapModel(
                coordinate: new CoordinateModel($Row['latitude'], $Row['longitude']),
                weight: $Row['weight'],
            );
        }

        return $_rows;
    }

    public function fetchAllLocationsByBoundary(float $minLat, float $maxLat, float $minLon, float $maxLon): array
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Fetching all locations by boundary', ['minLat' => $minLat, 'maxLat' => $maxLat, 'minLon' => $minLon, 'maxLon' => $maxLon]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT *, ST_Y(marker_location) AS latitude, ST_X(marker_location) AS longitude FROM %s WHERE ST_Intersects(marker_location, ST_MakeEnvelope(:minLon, :minLat, :maxLon, :maxLat, 4326)) LIMIT 1000;', self::tableName));

        $statement->execute([
            ':minLat' => $minLat,
            ':maxLat' => $maxLat,
            ':minLon' => $minLon,
            ':maxLon' => $maxLon,
        ]);

        if ($statement->rowCount() === 0) {
            return [];
        }

        $_rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $Row) {
            $_rows[] = $this->ConvertRowToClass($Row);
        }

        return $_rows;
    }

    public function countAllLocationsByBoundary(float $minLat, float $maxLat, float $minLon, float $maxLon): int
    {
        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);
        Logger::getLogger(__METHOD__)->debug('Counting all locations by boundary', ['minLat' => $minLat, 'maxLat' => $maxLat, 'minLon' => $minLon, 'maxLon' => $maxLon]);

        $statement = $this->getDatabaseConnector()->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE ST_Intersects(marker_location, ST_MakeEnvelope(:minLon, :minLat, :maxLon, :maxLat, 4326));', self::tableName));

        $statement->execute([
            ':minLat' => $minLat,
            ':maxLat' => $maxLat,
            ':minLon' => $minLon,
            ':maxLon' => $maxLon,
        ]);

        return (int)$statement->fetchColumn();
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
        $Values[':youtube'] = $locationModel->getYoutube();
        $Values[':status'] = $locationModel->getStatus()->value;
        $Values[':properties'] = LocationProperties::fromArrayToInt($locationModel->getProperties());
        $Values[':author'] = 0; // TODO: Implement author

        $Columns = [];
        $ParsedValues = [];

        foreach ($Values as $Column => $Value) {
            $Columns[] = substr($Column, 1);
            $ParsedValues[] = $Column;
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf($SQLTemplate, self::tableName, implode(', ', $Columns), implode(', ', $ParsedValues), $locationModel->getCoordinates()->toPostGIS()));

        if (!$statement->execute($Values)) {
            throw new Exception('Layout could not be created.');
        }

        return $this->getLocationById($this->getDatabaseConnector()->lastInsertId());
    }

    public function canDeleteLocation(LocationModel $locationModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        if((new ReportDatabaseController())->locationHasReports($locationModel)) {
            return false;
        }

        if((new VoteDatabaseController())->locationHasVotes($locationModel)) {
            return false;
        }

        return true;
    }

    public function deleteLocation(LocationModel $locationModel): bool
    {
        if ($this->getDatabaseConnector() && $this->getDatabaseConnector()->inTransaction() === false) {
            throw new Exception('Cannot delete template, because no transaction is active.');
        }

        if (!$this->canDeleteLocation($locationModel)) {
            throw new Exception('Cannot delete location, because it has reports or votes.');
        }

        $statement = $this->getDatabaseConnector()->prepare(sprintf('DELETE FROM %s WHERE id = :id;', self::tableName));

        $statement->bindValue(':id', $locationModel->getId());

        return $statement->execute();
    }
}
