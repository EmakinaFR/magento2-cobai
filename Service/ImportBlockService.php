<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;
use League\Csv\Reader;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class ImportBlockService
 */
class ImportBlockService
{
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
     * ImportBlockService constructor.
     * @param BlockRepositoryInterface $blockRepository
     * @param BlockFactory $blockFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(BlockRepositoryInterface $blockRepository, BlockFactory $blockFactory, CollectionFactory $collectionFactory)
    {
        $this->blockRepository = $blockRepository;
        $this->blockFactory = $blockFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Import blocks from csv
     *
     * @param string $file
     * @param bool $force
     *
     * @return array
     */
    public function import(string $file, bool $force): array
    {
        $errors = [];

        $blockCollection = $this->collectionFactory->create();

        try {
            $csv = Reader::createFromPath($file, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(Reader::BOM_UTF8);

            if (!array_diff(ExportConstants::BLOCK_HEADER, $csv->getHeader())) {
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
