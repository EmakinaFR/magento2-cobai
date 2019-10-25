<?php

namespace Emakina\Cobai\Console\Command;

use Emakina\Cobai\Constant\ExportConstants;
use Emakina\Cobai\Logger\Logger;
use Emakina\Cobai\Service\ExportService;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportCommand
 */
class ExportCommand extends Command
{
    /**
     * @var State
     */
    protected $state;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ExportService
     */
    protected $exportService;

    /**
     * ExportCommand constructor.
     *
     * @param State $state
     * @param Logger $logger
     * @param ExportService $exportService
     */
    public function __construct(State $state, Logger $logger, ExportService $exportService)
    {
        parent::__construct();
        $this->state = $state;
        $this->logger = $logger;
        $this->exportService = $exportService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cobai:cms:export')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of export file', date('Ymd-H:i:s'))
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Directory of export file', ExportConstants::BASE_PATH)
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Export type', 'all')
            ->setDescription('Export block to csv file');
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
            $file = $input->getOption('file');
            /** @var string $directory */
            $directory = $input->getOption('directory');
            /** @var string $type */
            $type = $input->getOption('type');
            $path = $this->exportService->executeExport($file, $directory, $type);
            $output->writeln(sprintf('<info>Successful file %s export</info>', $path));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            $return = ExportConstants::COMMAND_ERROR;
        }

        return $return;
    }
}
