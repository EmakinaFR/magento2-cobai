<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @param array $identifiers
     * @return array
     * @throws \Exception
     */
    public function export(string $filename, string $directory, array $identifiers = []): array


    {
        $exportInfo = [];

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filename = sprintf('%s.csv', $filename);

        $blockCollection = $this->collectionFactory->create();

        if (empty($identifiers)) {
            //Get all cms pages
            $blocks = $blockCollection->getItems();
        } else {
            $blocks = $blockCollection->addFieldToFilter('identifier', $identifiers);

            $identifiers = $blocks->getColumnValues('identifier');

            if (!$blocks->count()) {
                throw new \InvalidArgumentException('No matching identifier(s)');
            }

            foreach ($identifiers as $identifier) {
                if (!in_array($identifier, $identifiers)) {
                    $exportInfo['errors'][] = sprintf('<comment>%s is missing</comment>', $identifier);
                }
            }

        }


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
        $exportInfo['path'] = $path;
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        if ($writer instanceof Writer && $file) {
            $writer->setDelimiter(';')->setNewline("\r\n")->insertAll($rows);
            fwrite($file, $writer->getContent());
        } else {
            throw new \Exception(sprintf('Can not open file %s', $path));
        }

        return $exportInfo;
    }
}
