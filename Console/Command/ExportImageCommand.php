<?php

namespace Courreges\ImportExportCMS\Console\Command;

use League\Csv\Exception;
use League\Csv\Writer;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportImageCommand
 */
class ExportImageCommand extends Command
{
    public const HEADER =
        [
            'url',
            'filename',
            'directory'
        ];

    private const PATH = 'var/Export/Image/';

    public const IMAGE_PATH = 'pub/media/wysiwyg/';

    /**
     * @var State
     */
    private $state;

    /**
     * @var UrlInterface
     */
    private $urlInterface;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $rows;

    /**
     * ExportImageCommand constructor.
     * @param State $state
     * @param UrlInterface $urlInterface
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(State $state, UrlInterface $urlInterface, StoreManagerInterface $storeManager)
    {
        parent::__construct();

        $this->state = $state;
        $this->urlInterface = $urlInterface;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('courreges:image:export')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Name of export file', sprintf('%s.csv', date('Ymd-H:i:s')))
            ->setDescription('Export image to csv file');
        parent::configure();
    }

    /**
     * Command to export Wysiwyg image to CSV file
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        try {
            $filePath = $this->export($input->getOption('file'));
            $output->writeln(sprintf('<info>Successful file %s export</info>', $filePath));
        } catch (Exception | \TypeError | NoSuchEntityException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }

    /**
     * Export wysiwyg image
     *
     * @param string $filename
     * @return string
     * @throws Exception
     * @throws NoSuchEntityException
     * @throws \TypeError
     */
    public function export(string $filename): string
    {
        $this->baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);

        //Create the content of csv file
        $this->rows = [self::HEADER];
        $this->generateRows('');

        if (!is_dir(self::PATH)) {
            mkdir(self::PATH, 0774, true);
        }

        //Create the file and write in it
        $path = self::PATH . $filename;
        $file = fopen($path, 'w+');

        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $writer->setDelimiter(';')->setNewline("\r\n")->insertAll($this->rows);
        fwrite($file, $writer->getContent());

        return $path;
    }

    /**
     * Generate rows
     *
     * @param $path
     */
    public function generateRows($path)
    {
        $directory = self::IMAGE_PATH . $path;
        $files = scandir($directory);
        $files = array_diff($files, ['.', '..']);

        foreach ($files as $file) {
            if (is_dir($directory . $file)) {
                $this->generateRows($path . $file . '/');
                continue;
            }
            $url = $this->baseUrl . 'media/wysiwyg/' . $path . $file;
            $this->rows[] = ['url' => $url, 'filename' => $file, 'directory' => $directory];
        }
    }
}
