<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Theme\Model\ResourceModel\Theme as ThemeResourceModel;
use Magento\Theme\Model\ThemeFactory;

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
     * @var ThemeResourceModel
     */
    private $themeResourceModel;

    /**
     * @var ThemeFactory
     */
    private $themeFactory;

    /**
     * ExportPageService constructor.
     * @param CollectionFactory $collectionFactory
     * @param ThemeFactory $themeFactory
     * @param ThemeResourceModel $themeResourceModel
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ThemeFactory $themeFactory,
        ThemeResourceModel $themeResourceModel
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->themeFactory = $themeFactory;
        $this->themeResourceModel = $themeResourceModel;
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
            mkdir($directory, 0777, true);
        }

        $filename = sprintf('%s.csv', $filename);

        $pageCollection = $this->collectionFactory->create();

        //Get all cms pages
        $pages = $pageCollection->getItems();

        //Create the content of csv file
        $rows = [ExportConstants::PAGE_HEADER];
        /** @var Page $page */
        foreach ($pages as $page) {
            $customThemeId = $page->getCustomTheme();

            $customTheme = null;
            if ($customThemeId) {
                $theme = $this->themeFactory->create();
                $this->themeResourceModel->load($theme, $customThemeId);
                $customTheme = $theme->getCode();
            }

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
                'custom_theme' => $customTheme,
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
