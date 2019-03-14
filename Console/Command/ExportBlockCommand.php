<?php

namespace Courreges\ImportExportCMS\Console\Command;

use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportBlockCommand
 */
class ExportBlockCommand extends Command
{
    public const HEADER =
        [
            'title',
            'identifier',
            'content',
            'is_active',
            'store_id',
        ];

    private const PATH = 'var/Export/Block/';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * ExportBlockCommand constructor.
     *
     * @param State $state
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(State $state, CollectionFactory $collectionFactory)
    {
        parent::__construct();

        $this->state = $state;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('courreges:block:export')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of export file', sprintf('%s.csv', date('Ymd-H:i:s')))
            ->setDescription('Export block to csv file');
        parent::configure();
    }

    /**
     * Command to export CMS block to CSV file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        try {
            $filePath = $this->export($input->getOption('file'));
            $output->writeln(sprintf('<info>Successful file %s export</info>', $filePath));
        } catch (Exception | \TypeError $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }

    /**
     * Export block csv
     *
     * @param string $filename
     * @return string
     * @throws Exception
     * @throws \TypeError
     */
    public function export(string $filename): string
    {
        $blockCollection = $this->collectionFactory->create();

        //Get all cms blocks
        $blocks = $blockCollection->getItems();

        //Create the content of csv file
        $rows = [self::HEADER];
        foreach ($blocks as $block) {
            $rows[] = [
                'title' => $block->getTitle(),
                'identifier' => $block->getIdentifier(),
                'content' => $block->getContent(),
                'is_active' => $block->isActive(),
                'store_id' => implode('|', $block->getStoreId())
            ];
        }

        if (!is_dir(self::PATH)) {
            mkdir(self::PATH, 0774, true);
        }

        //Create the file and write in it
        $path = self::PATH . $filename;
        $file = fopen($path, 'w+');

        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(';')->setNewline("\r\n")->insertAll($rows);
        fwrite($file, $writer->getContent());

        return $path;
    }
}
