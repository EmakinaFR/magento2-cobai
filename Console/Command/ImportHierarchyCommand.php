<?php

namespace Courreges\ImportExportCMS\Console\Command;

use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\State;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportHierarchyCommand
 */
class ImportHierarchyCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var HierarchyNodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * ImportHierarchyCommand constructor.
     *
     * @param HierarchyNodeRepositoryInterface $nodeRepository
     * @param NodeFactory $nodeFactory
     * @param CollectionFactory $collectionFactory
     * @param PageCollectionFactory $pageCollectionFactory
     * @param State $state
     */
    public function __construct(HierarchyNodeRepositoryInterface $nodeRepository, NodeFactory $nodeFactory, CollectionFactory $collectionFactory, PageCollectionFactory $pageCollectionFactory, State $state)
    {
        parent::__construct();

        $this->state = $state;
        $this->nodeRepository = $nodeRepository;
        $this->nodeFactory = $nodeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('courreges:hierarchy:import')
            ->setDescription('Import hierarchy page from csv file. Be careful, current hierarchy will be rewritten')
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV file path');
        parent::configure();
    }

    /**
     * Command to import hierarchy page from CSV file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
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
     * Import hierarchy page
     *
     * @param string $filePath
     * @return array
     */
    public function import(string $filePath): array
    {
        $errors = [];

        $collection = $this->collectionFactory->create();

        $pageCollection = $this->pageCollectionFactory->create();

        //Delete all hierarchy
        foreach ($collection->getItems() as $node) {
            $node->delete();
        }

        try {
            $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(\League\Csv\Reader::BOM_UTF8);

            if (!array_diff(ExportHierarchyCommand::HEADER, $csv->getHeader())) {

                //Import of node line by line
                $records = $csv->getRecords();

                //Node correspondence table
                $nodesId = [];

                foreach ($records as $i => $record) {
                    $collection = clone $pageCollection;

                    $pageId = null;
                    if ($record['page_identifier']) {
                        $page = $collection->addStoreFilter(explode('|', $record['page_store']))->getItemByColumnValue('identifier', $record['page_identifier']);

                        if ($page) {
                            $pageId = $page->getId();
                        }
                    }

                    if (($record['page_identifier'] && $pageId) || (!$record['page_identifier'] && !$pageId)) {
                        $parentNodeId = key_exists($record['parent_node_id'], $nodesId) ? $nodesId[$record['parent_node_id']] : null;
                        $node = $this->nodeFactory->create();
                        $node->setScope($record['scope'])
                            ->setScopeId($record['scope_id'])
                            ->setParentNodeId($parentNodeId)
                            ->setPageId($pageId)
                            ->setIdentifier($record['identifier'])
                            ->setLabel($record['label'])
                            ->setLevel($record['level'])
                            ->setSortOrder($record['sort_order'])
                            ->setRequestUrl($record['request_url'])
                            ->setXpath($record['xpath'])
                            ->setMetaFirstLast($record['meta_first_last'])
                            ->setMetaNextPrevious($record['meta_next_previous'])
                            ->setMetaChapiter($record['meta_chapter'])
                            ->setMetaSection($record['meta_section'])
                            ->setMetaCsEnabled($record['meta_cs_enabled'])
                            ->setPagerVisibility($record['pager_visibility'])
                            ->setPagerFrame($record['pager_frame'])
                            ->setPagerJump($record['pager_jump'])
                            ->setMenuVisibility($record['menu_visibility'])
                            ->setMenuExcluded($record['menu_excluded'])
                            ->setMenuLayout($record['menu_layout'])
                            ->setMenuBrief($record['menu_brief'])
                            ->setMenuLevelsDown($record['menu_levels_down'])
                            ->setMenuOrdered($record['menu_ordered'])
                            ->setMenuListType($record['menu_list_type'])
                            ->setTopMenuVisibility($record['top_menu_visibility'])
                            ->setTopMenuExcluded($record['top_menu_excluded']);

                        $this->nodeRepository->save($node);
                        $nodesId[$record['node_id']] = $node->getId();
                    } else {
                        $errors[] = sprintf('Page %s doen\'t exist', $record['page_identifier']);
                    }
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
