<?php

namespace Crispy\CommandControllers;

use Carbon\Carbon;
use crisp\api\Helper as ApiHelper;
use crisp\core\Logger;
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Crispy\Helper;
use Crispy\Models\LayoutModel;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateFrontendCommandController extends Command
{

    private TemplateGeneratorController $templateGeneratorController;


    public function __construct()
    {
        $this->templateGeneratorController = new TemplateGeneratorController();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('crispy:frontend:generate')
            ->setDescription('Generate the frontend code.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $io = new SymfonyStyle($input, $output);

        if($this->templateGeneratorController->generate()){
            $io->success('Frontend code generated successfully.');
            return Command::SUCCESS;
        } else {
            $io->error('Failed to generate frontend code.');
            return Command::FAILURE;
        }

        

    }
}
