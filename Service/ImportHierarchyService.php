<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;
use League\Csv\Reader;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;

/**
 * Class ImportHierarchyService
 */
class ImportHierarchyService
{
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
     * ImportHierarchyService constructor.
     * @param HierarchyNodeRepositoryInterface $nodeRepository
     * @param NodeFactory $nodeFactory
     * @param CollectionFactory $collectionFactory
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(HierarchyNodeRepositoryInterface $nodeRepository, NodeFactory $nodeFactory, CollectionFactory $collectionFactory, PageCollectionFactory $pageCollectionFactory)
    {
        $this->nodeRepository = $nodeRepository;
        $this->nodeFactory = $nodeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Import hierarchies from csv
     *
     * @param string $file
     * @param bool $force
     * @return array
     */
    public function import(string $file, bool $force): array
    {
        $errors = [];

        $collection = $this->collectionFactory->create();
        $pageCollection = $this->pageCollectionFactory->create();

        try {
            //Delete all hierarchy
            /** @var Node $node */
            foreach ($collection->getItems() as $node) {
                $this->nodeRepository->delete($node);
            }

            $csv = Reader::createFromPath($file, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(Reader::BOM_UTF8);

            if (!array_diff(ExportConstants::HIERARCHY_HEADER, $csv->getHeader())) {
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
                        $parentNodeId = $nodesId[$record['parent_node_id']] ?? null;

                        //Manage xpath
                        $xpath = '';
                        foreach (explode('/', $record['xpath']) as $path) {
                            if (array_key_exists($path, $nodesId)) {
                                $xpath .= $nodesId[$path] . '/'; //the current node is added at the end of xpath by magento
                            }
                        }

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
                            ->setXpath($xpath)
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
