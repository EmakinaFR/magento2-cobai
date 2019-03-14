<?php

namespace Courreges\ImportExportCMS\Console\Command;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportPageCommand
 */
class ImportPageCommand extends Command
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * ImportBlockCommand constructor.
     *
     * @param PageRepositoryInterface $pageRepository
     * @param PageFactory $pageFactory
     * @param CollectionFactory $collectionFactory
     * @param State $state
     */
    public function __construct(PageRepositoryInterface $pageRepository, PageFactory $pageFactory, CollectionFactory $collectionFactory, State $state)
    {
        parent::__construct();

        $this->state = $state;
        $this->pageRepository = $pageRepository;
        $this->pageFactory = $pageFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('courreges:page:import')
            ->setDescription('Import page from csv file')
            ->addArgument('filename', InputArgument::REQUIRED, 'CSV file path')
            ->addOption('force', ['f'], InputOption::VALUE_NONE, 'Replace page if it exists');

        parent::configure();
    }

    /**
     * Command to import CMS page from CSV file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $errors = $this->import($input->getArgument('filename'), $input->getOption('force'));

        if (\count($errors) > 0) {
            $output->writeln('<error>');
            $output->writeln($errors);
            $output->writeln('</error>');
        } else {
            $output->writeln('<info>Successful file import</info>');
        }
    }

    /**
     * Import CMS page
     *
     * @param string $filePath
     * @param bool $force
     * @return array
     */
    public function import(string $filePath, bool $force): array
    {
        $errors = [];

        $pageCollection = $this->collectionFactory->create();

        try {
            $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(';');
            $csv->setOutputBOM(\League\Csv\Reader::BOM_UTF8);

            if (!array_diff(ExportBlockCommand::HEADER, $csv->getHeader())) {
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
