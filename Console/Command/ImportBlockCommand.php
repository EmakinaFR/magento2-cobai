<?php

namespace Emakina\CmsImportExport\Console\Command;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportBlockCommand.
 */
class ImportBlockCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ImportBlockCommand constructor.
     *
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory             $blockFactory
     * @param CollectionFactory        $collectionFactory
     * @param State                    $state
     */
    public function __construct(BlockRepositoryInterface $blockRepository, BlockFactory $blockFactory, CollectionFactory $collectionFactory, State $state)
    {
        parent::__construct();

        $this->state = $state;
        $this->blockRepository = $blockRepository;
        $this->blockFactory = $blockFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cms:import:block')
            ->setDescription('Import block from csv file')
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV file path')
            ->addOption('force', ['f'], InputOption::VALUE_NONE, 'Replace block if it exists');

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
        $errors = $this->import($input->getArgument('filename'), $input->getOption('force'));

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
     * @param bool   $force
     *
     * @return array
     */
    public function import(string $filePath, bool $force): array
    {
        $errors = [];

        $blockCollection = $this->collectionFactory->create();

        try {
            $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(\League\Csv\Reader::BOM_UTF8);

            if (!array_diff(ExportBlockCommand::HEADER, $csv->getHeader())) {
                //Import of block line by line
                $records = $csv->getRecords();
                foreach ($records as $i => $record) {
                    $record['store_id'] = explode('|', $record['store_id']);
                    $collection = clone $blockCollection;
                    $block = $collection->addStoreFilter($record['store_id'])->getItemByColumnValue('identifier', $record['identifier']);

                    //If the block already exists and the option -f is not active, the line is ignored
                    if ($block && !$force) {
                        $errors[] = sprintf('Block %s already exists, use -f to replace it', $record['identifier']);
                        continue;
                    }

                    //Creation of block
                    if (!$block) {
                        $block = $this->blockFactory->create();
                    }

                    $block->setTitle($record['title'])
                        ->setIdentifier($record['identifier'])
                        ->setContent($record['content'])
                        ->setIsActive($record['is_active'])
                        ->setStoreId($record['store_id']);
                    $this->blockRepository->save($block);
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
