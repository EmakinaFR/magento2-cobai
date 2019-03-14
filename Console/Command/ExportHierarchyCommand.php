<?php

namespace Courreges\ImportExportCMS\Console\Command;

use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\State;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportHierarchyCommand
 */
class ExportHierarchyCommand extends Command
{
    public const HEADER =
        [
            'scope',
            'scope_id',
            'node_id',
            'parent_node_id',
            'page_identifier',
            'page_store',
            'identifier',
            'label',
            'level',
            'sort_order',
            'request_url',
            'xpath',
            'meta_first_last',
            'meta_next_previous',
            'meta_chapter',
            'meta_section',
            'meta_cs_enabled',
            'pager_visibility',
            'pager_frame',
            'pager_jump',
            'menu_visibility',
            'menu_excluded',
            'menu_layout',
            'menu_brief',
            'menu_levels_down',
            'menu_ordered',
            'menu_list_type',
            'top_menu_visibility',
            'top_menu_excluded'
        ];

    private const PATH = 'var/Export/Hierarchy/';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * ExportHierarchyCommand constructor.
     *
     * @param State $state
     * @param CollectionFactory $collectionFactory
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(State $state, CollectionFactory $collectionFactory, PageCollectionFactory $pageCollectionFactory)
    {
        parent::__construct();

        $this->state = $state;
        $this->collectionFactory = $collectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('courreges:hierarchy:export')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of export file', sprintf('%s.csv', date('Ymd-H:i:s')))
            ->setDescription('Export hierarchy page to csv file');
        parent::configure();
    }

    /**
     * Command to export Hierarchy page to CSV file
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
     * Export page hierarchy csv
     *
     * @param string $filename
     * @return string
     * @throws Exception
     * @throws \TypeError
     */
    public function export(string $filename): string
    {
        $nodeCollection = $this->collectionFactory->create();
        $pageCollection = $this->pageCollectionFactory->create();

        //Get all nodes
        $nodes = $nodeCollection->getItems();

        //Create the content of csv file
        $rows = [self::HEADER];
        foreach ($nodes as $node) {
            $metaData = $node->getTreeMetaData();

            $page = $pageCollection->getItemByColumnValue('page_id', $node->getPageId());
            $rows[] = [
                'scope' => $node->getScope(),
                'scope_id' => $node->getScopeId(),
                'node_id' => $node->getNodeId(),
                'parent_node_id' => $node->getParentNodeId(),
                'page_identifier' => null === $page ? null : $page->getIdentifier(),
                'page_store' => null === $page ? null : implode('|', $page->getStoreId()),
                'identifier' => $node->getIdentifier(),
                'label' => $node->getLabel(),
                'level' => $node->getLevel(),
                'sort_order' => $node->getSortOrder(),
                'request_url' => $node->getRequestUrl(),
                'xpath' => $node->getXpath(),
                'meta_first_last' => $metaData['meta_first_last'],
                'meta_next_previous' => $metaData['meta_next_previous'],
                'meta_chapter' => $metaData['meta_chapter'],
                'meta_section' => $metaData['meta_section'],
                'meta_cs_enabled' => $metaData['meta_cs_enabled'],
                'pager_visibility' => $metaData['pager_visibility'],
                'pager_frame' => $metaData['pager_frame'],
                'pager_jump' => $metaData['pager_jump'],
                'menu_visibility' => $metaData['menu_visibility'],
                'menu_excluded' => $metaData['menu_excluded'],
                'menu_layout' => $metaData['menu_layout'],
                'menu_brief' => $metaData['menu_brief'],
                'menu_levels_down' => $metaData['menu_levels_down'],
                'menu_ordered' => $metaData['menu_ordered'],
                'menu_list_type' => $metaData['menu_list_type'],
                'top_menu_visibility' => $metaData['top_menu_visibility'],
                'top_menu_excluded' => $metaData['top_menu_excluded'],
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
