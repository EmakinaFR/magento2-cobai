<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

/**
 * Class ExportBlockService
 */
class ExportBlockService
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ExportBlockService constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Export blocks into a csv
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

        $blockCollection = $this->collectionFactory->create();

        //Get all cms blocks
        $blocks = $blockCollection->getItems();

        //Create the content of csv file
        $rows = [ExportConstants::BLOCK_HEADER];
        /** @var Block $block */
        foreach ($blocks as $block) {
            $rows[] = [
                'title' => $block->getTitle(),
                'identifier' => $block->getIdentifier(),
                'content' => $block->getContent(),
                'is_active' => $block->isActive(),
                'store_id' => implode('|', $block->getStoreId()),
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
