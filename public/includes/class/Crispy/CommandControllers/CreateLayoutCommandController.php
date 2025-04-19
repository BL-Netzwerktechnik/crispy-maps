<?php

namespace Crispy\CommandControllers;

use Carbon\Carbon;
use crisp\api\Helper as ApiHelper;
use crisp\core\Logger;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\FileControllers\LayoutFileController;
use Crispy\Helper;
use Crispy\Models\LayoutModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateLayoutCommandController extends Command
{

    private LayoutDatabaseController $layoutDatabaseController;
    private LayoutFileController $layoutFileController;

    public function __construct()
    {
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->layoutFileController = new LayoutFileController();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('crispy:layout:create')
            ->addOption('name', 'l', InputOption::VALUE_REQUIRED, 'The name of the layout.')
            ->addOption('content', 'c', InputOption::VALUE_REQUIRED, 'The content of the layout.')
            ->addOption('author', 'u', InputOption::VALUE_OPTIONAL, 'The author of the file.')
            ->addOption('slug', 's', InputOption::VALUE_OPTIONAL, 'The slug of the file.')
            ->setDescription('Create a new layout.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        $name = $input->getOption('name');
        $content = $input->getOption('content');
        $author = $input->getOption('author');
        $slug = $input->getOption('slug');


        if (empty($name)) {
            $io->error('Name is required.');

            return Command::FAILURE;
        }

        if (empty($content)) {
            $io->error('Content is required.');

            return Command::FAILURE;
        }

        if(empty($author)){
            $author = 0;
        }

        if(empty($slug)){
            $slug = Helper::slugify($name, '_');
        }

        $layoutModel = new LayoutModel(
            name: $name,
            content: $content,
            author: $author,
            slug: $slug,
            updatedAt: Carbon::now($_ENV['TZ']),
            createdAt: Carbon::now($_ENV['TZ'])
        );

        if($this->layoutDatabaseController->getLayoutBySlug($slug) !== null) {
            $io->error('Layout with this slug already exists.');
            return Command::FAILURE;
        }

        $this->layoutDatabaseController->beginTransaction();

        if (!$this->layoutDatabaseController->insertLayout($layoutModel)) {
            $this->layoutDatabaseController->rollbackTransaction();
            return Command::FAILURE;
        }
        $this->layoutDatabaseController->commitTransaction();
        $io->success('Layout created.');

        return Command::SUCCESS;
    }
}
