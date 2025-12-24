<?php

namespace blfilme\lostplaces\CommandControllers;

use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Models\CategoryModel;
use blfilme\lostplaces\Models\CoordinateModel;
use blfilme\lostplaces\Models\LocationModel;
use Carbon\Carbon;
use crisp\core\Logger;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Models\UserModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommandController extends Command
{

    private CategoryDatabaseController $categoryDatabaseController;
    private LocationDatabaseController $locationDatabaseController;
    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
        $this->userDatabaseController = new UserDatabaseController();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('lostplaces:data:import')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path to the json file.')
            ->setDescription('Import Data');
    }

    public function getCategoryMapping(int $id): CategoryModel
    {
        return match ($id) {
            1 => $this->categoryDatabaseController->getCategoryById(4),
            2 => $this->categoryDatabaseController->getCategoryById(5),
            3 => $this->categoryDatabaseController->getCategoryById(6),
            4 => $this->categoryDatabaseController->getCategoryById(1),
            5 => $this->categoryDatabaseController->getCategoryById(7),
            6 => $this->categoryDatabaseController->getCategoryById(8),
            7 => $this->categoryDatabaseController->getCategoryById(9),
            default => $this->categoryDatabaseController->getCategoryById(-1),
        };
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        $path = $input->getOption('path');

        if (empty($path)) {
            $io->error('Path is required.');

            return Command::FAILURE;
        }

        if (!file_exists($path)) {
            $io->error('File does not exist.');

            return Command::FAILURE;
        }

        if (!is_readable($path)) {
            $io->error('File is not readable.');

            return Command::FAILURE;
        }

        $json = file_get_contents($path);

        if ($json === false) {
            $io->error('Failed to read file.');

            return Command::FAILURE;
        }

        $data = json_decode($json, true);

        if ($data === null) {
            $io->error('Failed to decode json.');

            return Command::FAILURE;
        }

        $progressBar = new ProgressBar($output, count($data));

        $this->locationDatabaseController->beginTransaction();
        $progressBar->start();
        foreach ($data as $location) {
            $category = $this->getCategoryMapping($location['category']);
            $location = new LocationModel(
                id: null,
                name: $location['name'],
                description: $location['place'],
                properties: [],
                youtube: null,
                status: LocationStatus::UNKNOWN,
                coordinates: new CoordinateModel(
                    latitude: $location['lat'],
                    longitude: $location['lng'],
                ),
                category: $category,
                createdAt: Carbon::now(),
                updatedAt: Carbon::now(),
                author: UserModel::fetchSystemUser(),
            );

            if (!$this->locationDatabaseController->insertLocation($location)) {
                $this->locationDatabaseController->rollbackTransaction();
                $io->error('Failed to insert location: ' . $location->getName());

                return Command::FAILURE;
            }

            $progressBar->advance();
        }

        $this->locationDatabaseController->commitTransaction();
        $progressBar->finish();

        return Command::SUCCESS;
    }
}
