<?php

namespace Emakina\Cobai\Console\Command;

use Emakina\Cobai\Constant\ExportConstants;
use Emakina\Cobai\Logger\Logger;
use Emakina\Cobai\Service\ImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportCommand.
 */
class ImportCommand extends Command
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ImportService
     */
    protected $importService;

    /**
     * ImportCommand constructor.
     * @param Logger $logger
     * @param ImportService $importService
     */
    public function __construct(Logger $logger, ImportService $importService)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->importService = $importService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cobai:cms:import')
            ->setDescription('Import from csv file')
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file path')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Export type', 'all')
            ->addOption('force', ['f'], InputOption::VALUE_NONE, 'Replace content if it exists');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $return = ExportConstants::COMMAND_OK;
        try {
            /** @var string $file */
            $file = $input->getArgument('file');
            /** @var string $type */
            $type = $input->getOption('type');
            /** @var bool $force */
            $force = $input->getOption('force');
            $errors = $this->importService->executeImport($file, $type, $force);
            if (count($errors) === 0) {
                $output->writeln(sprintf('<info>Successful file %s import</info>', $file));
            } else {
                foreach ($errors as $error) {
                    $this->logger->error($error);
                    $output->writeln(sprintf('<error>%s</error>', $error));
                }
                $return = ExportConstants::COMMAND_ERROR;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            $return = ExportConstants::COMMAND_ERROR;
        }

        return $return;
    }
}
