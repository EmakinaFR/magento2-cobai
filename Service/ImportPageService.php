<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use League\Csv\Reader;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Theme\Model\ResourceModel\Theme as ThemeResourceModel;
use Magento\Theme\Model\ThemeFactory;

/**
 * Class ImportPageService
 */
class ImportPageService
{
    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
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
     * ImportPageService constructor.
     * @param PageRepositoryInterface $pageRepository
     * @param PageFactory $pageFactory
     * @param CollectionFactory $collectionFactory
     * @param ThemeFactory $themeFactory
     * @param ThemeResourceModel $themeResourceModel
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        PageFactory $pageFactory,
        CollectionFactory $collectionFactory,
        ThemeFactory $themeFactory,
        ThemeResourceModel $themeResourceModel
    ) {
        $this->pageRepository = $pageRepository;
        $this->pageFactory = $pageFactory;
        $this->collectionFactory = $collectionFactory;
        $this->themeFactory = $themeFactory;
        $this->themeResourceModel = $themeResourceModel;
    }

    /**
     * Import pages from zip
     *
     * @param string $file
     * @param bool $force
     * @return array
     */
    public function import(string $file, bool $force): array
    {
        $errors = [];

        $pageCollection = $this->collectionFactory->create();

        try {
            $csv = Reader::createFromPath($file, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(Reader::BOM_UTF8);

            if (!array_diff(ExportConstants::PAGE_HEADER, $csv->getHeader())) {
                //Import of page line by line
                $records = $csv->getRecords();
                foreach ($records as $i => $record) {
                    $collection = clone $pageCollection;
                    $record['store_id'] = explode('|', $record['store_id']);
                    $page = $collection->addStoreFilter($record['store_id'])->getItemByColumnValue('identifier', $record['identifier']);

                    //If the page already exists and the option -f is not active, the line is ignored
                    if ($page && !$force) {
                        $errors[] = sprintf('Page %s already exists, use -f to replace it', $record['identifier']);
                        continue;
                    }

                    //Creation of page
                    if (!$page) {
                        $page = $this->pageFactory->create();
                    }

                    if ($record['custom_theme']) {
                        $theme = $this->themeFactory->create();
                        $this->themeResourceModel->load($theme, $record['custom_theme'], 'code');
                        $record['custom_theme'] = $theme->getId();
                    }

                    $page->setTitle($record['title'])
                        ->setPageLayout($record['page_layout'])
                        ->setMetaKeywords($record['meta_keywords'])
                        ->setMetaDescription($record['meta_description'])
                        ->setIdentifier($record['identifier'])
                        ->setContentHeading($record['content_heading'])
                        ->setContent($record['content'])
                        ->setIsActive($record['is_active'])
                        ->setSortOrder($record['sort_order'])
                        ->setLayoutUpdateXml($record['layout_update_xml'])
                        ->setCustomTheme($record['custom_theme'])
                        ->setCustomRootTemplate($record['custom_root_template'])
                        ->setCustomLayoutUpdateXml($record['custom_layout_update_xml'])
                        ->setCustomThemeFrom($record['custom_theme_from'])
                        ->setMetaTitle($record['meta_title'])
                        ->setWebsiteRoot($record['website_root'])
                        ->setStoreId($record['store_id'])
                        ->setData('is_searchable', $record['is_searchable']);

                    $this->pageRepository->save($page);
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
