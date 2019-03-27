<?php

namespace Emakina\CmsImportExport\Console\Command;

use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportPageCommand.
 */
class ExportPageCommand extends Command
{
    public const HEADER =
        [
            'title',
            'page_layout',
            'meta_keywords',
            'meta_description',
            'identifier',
            'content_heading',
            'content',
            'is_active',
            'sort_order',
            'layout_update_xml',
            'custom_theme',
            'custom_root_template',
            'custom_layout_update_xml',
            'custom_theme_from',
            'meta_title',
            'website_root',
            'is_searchable',
            'store_id',
        ];

    private const PATH = 'var/Export/Page/';

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * ExportBlockCommand constructor.
     *
     * @param State             $state
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(State $state, CollectionFactory $collectionFactory)
    {
        parent::__construct();

        $this->state = $state;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cms:export:page')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of export file', sprintf('%s.csv', date('Ymd-H:i:s')))
            ->setDescription('Export page to csv file');
        parent::configure();
    }

    /**
     * Command to export CMS page to CSV file.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        try {
            $filePath = $this->export($input->getOption('file'));
            $output->writeln(sprintf('<info>Successful file %s export</info>', $filePath));
        } catch (Exception | \TypeError $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }

    /**
     * Export page csv.
     *
     * @param string $filename
     *
     * @return string
     *
     * @throws Exception
     * @throws \TypeError
     */
    public function export(string $filename): string
    {
        $pageCollection = $this->collectionFactory->create();

        //Get all cms pages
        $pages = $pageCollection->getItems();

        //Create the content of csv file
        $rows = [self::HEADER];
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

        if (!is_dir(self::PATH)) {
            mkdir(self::PATH, 0774, true);
        }

        //Create the file and write in it
        $path = self::PATH . $filename;
        $file = fopen($path, 'w+');

        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(';')->setNewline("\r\n")->insertAll($rows);
        fwrite($file, $writer->getContent());

        return $path;
    }
}
