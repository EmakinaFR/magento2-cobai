<?php

namespace Emakina\CmsImportExport\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportImageCommand.
 */
class ImportImageCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * ImportBlockCommand constructor.
     *
     * @param State $state
     */
    public function __construct(State $state)
    {
        parent::__construct();

        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cms:import:image')
            ->setDescription('Import image from csv file')
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV file path');

        parent::configure();
    }

    /**
     * Command to import CMS block from CSV file.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $errors = $this->import($input->getArgument('filename'));

        if (\count($errors) > 0) {
            $output->writeln('<error>');
            $output->writeln($errors);
            $output->writeln('</error>');
        } else {
            $output->writeln('<info>Successful file import</info>');
        }
    }

    /**
     * Import CMS block.
     *
     * @param string $filePath
     *
     * @return array
     */
    public function import(string $filePath): array
    {
        $errors = [];

        try {
            $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(\League\Csv\Reader::BOM_UTF8);

            if (!array_diff(ExportImageCommand::HEADER, $csv->getHeader())) {
                //Import of image line by line
                $records = $csv->getRecords();
                foreach ($records as $i => $record) {
                    if (!is_dir($record['directory'])) {
                        mkdir($record['directory'], 0774, true);
                    }
                    copy($record['url'], $record['directory'] . $record['filename']);
                }
            } else {
                $errors[] = 'Invalid header';
            }
        } catch (\Exception | \Throwable $e) {
            $errors[] = $e->getMessage();
        } finally {
            return $errors;
        }
    }
}
