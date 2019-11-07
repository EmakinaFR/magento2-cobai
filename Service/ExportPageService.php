<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;
use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;

/**
 * Class ExportPageService
 */
class ExportPageService
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ExportPageService constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Export page into a csv
     *
     * @param string $filename
     * @param string $directory
     * @return string
     * @throws Exception
     */
    public function export(string $filename, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $filename = sprintf('%s.csv', $filename);

        $pageCollection = $this->collectionFactory->create();

        //Get all cms pages
        $pages = $pageCollection->getItems();

        //Create the content of csv file
        $rows = [ExportConstants::PAGE_HEADER];
        /** @var Page $page */
        foreach ($pages as $page) {
            $rows[] = [
                'title' => $page->getTitle(),
                'page_layout' => $page->getPageLayout(),
                'meta_keywords' => $page->getMetaKeywords(),
                'meta_description' => $page->getMetaDescription(),
                'identifier' => $page->getIdentifier(),
                'content_heading' => $page->getContentHeading(),
                'content' => $page->getContent(),
                'is_active' => $page->isActive(),
                'sort_order' => $page->getSortOrder(),
                'layout_update_xml' => $page->getLayoutUpdateXml(),
                'custom_theme' => $page->getCustomTheme(),
                'custom_root_template' => $page->getCustomRootTemplate(),
                'custom_layout_update_xml' => $page->getCustomLayoutUpdateXml(),
                'custom_theme_from' => $page->getCustomThemeFrom(),
                'meta_title' => $page->getMetaTitle(),
                'website_root' => $page->getWebsiteRoot(),
                'is_searchable' => $page->getData('is_searchable'),
                'store_id' => implode('|', $page->getStoreId()),
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
