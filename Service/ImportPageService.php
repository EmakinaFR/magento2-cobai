<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use Emakina\Cobai\Exception\ImportException;
use League\Csv\Reader;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Theme\Model\ResourceModel\Theme as ThemeResourceModel;
use Magento\Theme\Model\ThemeFactory;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;

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
     * @var UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;

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
        ThemeResourceModel $themeResourceModel,
        UrlRewriteCollectionFactory $urlRewriteCollectionFactory
    )
    {
        $this->pageRepository = $pageRepository;
        $this->pageFactory = $pageFactory;
        $this->collectionFactory = $collectionFactory;
        $this->themeFactory = $themeFactory;
        $this->themeResourceModel = $themeResourceModel;
        $this->urlRewriteCollectionFactory = $urlRewriteCollectionFactory;
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

                    if ($this->urlExist($record['identifier'])) {
                        if (!$force) {
                            $errors[] = sprintf('URL %s already exists, use -f to rewrite it', $record['identifier']);
                            continue;
                        }
                        $record['identifier'] = $this->rewriteUrl($record['identifier']);
                    }

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

    /**
     * Check if the url given in parameter already exists in url_rewrite table
     *
     * @param string $url
     * @return bool
     */
    private function urlExist(string $url): bool
    {
        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addFieldToSelect('request_path')
            ->addFieldToFilter('request_path', $url)
            ->addFieldToFilter('entity_type', ['neq' => 'cms-page']);
        return $urlRewriteCollection->count() > 0;
    }

    /**
     * Rewrite URL by adding a number at the end (e.g test-page-#)
     * If the new URL is already taken, throw an exception
     *
     * @param string $url
     * @return string
     * @throws ImportException
     */
    private function rewriteUrl(string $url): string
    {
        $identifierPart = explode('.', $url);

        $urlRewriteCollection = $this->urlRewriteCollectionFactory->create();
        $urlRewriteCollection->addFieldToFilter(
            'request_path',
            ['like' => $identifierPart[0] . '%' . (isset($identifierPart[1]) ? '.' . $identifierPart[1] : '')])
            ->addFieldToFilter('entity_type', ['neq' => 'cms-page']);
        $urls = $urlRewriteCollection->getColumnValues('request_path');

        $filteredUrls = array_filter($urls, function ($value) use ($identifierPart) {
            return preg_match(
                '/' . $identifierPart[0] . '(-\d+)?$' . (isset($identifierPart[1]) ? '.' . $identifierPart[1] : '') . '/',
                $value
            );
        }
        );

        $newIdentifier = $identifierPart[0] . '-' . count($filteredUrls) . (isset($identifierPart[1]) ? '.' . $identifierPart[1] : '');

        if (in_array($newIdentifier, $filteredUrls)) {
            throw new ImportException(
                "Could not import the page {$url}: URL Key already exist and can't be substitute. Please, change it manually."
            );
        }

        return $newIdentifier;
    }
}
