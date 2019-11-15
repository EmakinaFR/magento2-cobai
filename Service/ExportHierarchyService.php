<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;
use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;

/**
 * Class ExportHierarchyService
 */
class ExportHierarchyService
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var PageCollectionFactory
     */
    private $pageCollectionFactory;

    /**
     * ExportHierarchyService constructor.
     * @param CollectionFactory $collectionFactory
     * @param PageCollectionFactory $pageCollectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory, PageCollectionFactory $pageCollectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
    }

    /**
     * Export hierarchies into a csv
     *
     * @param string $filename
     * @param string $directory
     * @return string
     * @throws Exception
     */
    public function export(string $filename, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = sprintf('%s.csv', $filename);

        $nodeCollection = $this->collectionFactory->create();
        $pageCollection = $this->pageCollectionFactory->create();

        //Get all nodes
        $nodes = $nodeCollection->getItems();

        //Create the content of csv file
        $rows = [ExportConstants::HIERARCHY_HEADER];
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

        if (!is_dir($directory)) {
            mkdir($directory, 0774, true);
        }

        //Create the file and write in it
        $path = $directory . $filename;
        $file = fopen($path, 'w+');

        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        if ($writer instanceof Writer && $file) {
            $writer->setDelimiter(';')->setNewline("\r\n")->insertAll($rows);
            fwrite($file, $writer->getContent());
        } else {
            throw new \Exception(sprintf('Can not open file %s', $path));
        }

        return $path;
    }
}
