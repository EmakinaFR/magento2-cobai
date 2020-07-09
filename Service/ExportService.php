<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use Emakina\Cobai\Logger\Logger;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportService
 */
class ExportService
{

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ExportArchiveService
     */
    protected $exportArchiveService;

    /**
     * @var ExportBlockService
     */
    protected $exportBlockService;

    /**
     * @var ExportHierarchyService
     */
    protected $exportHierarchyService;

    /**
     * @var ExportPageService
     */
    protected $exportPageService;

    /**
     * @var ExportImageService
     */
    protected $exportImageService;

    /**
     * ExportService constructor.
     *
     * @param State $state
     * @param Logger $logger
     * @param ExportBlockService $exportBlockService
     * @param ExportHierarchyService $exportHierarchyService
     * @param ExportPageService $exportPageService
     * @param ExportArchiveService $exportArchiveService
     * @param ExportImageService $exportImageService
     */
    public function __construct(
        State $state,
        Logger $logger,
        ExportBlockService $exportBlockService,
        ExportHierarchyService $exportHierarchyService,
        ExportPageService $exportPageService,
        ExportArchiveService $exportArchiveService,
        ExportImageService $exportImageService
    )
    {
        $this->state = $state;
        $this->logger = $logger;
        $this->exportArchiveService = $exportArchiveService;
        $this->exportBlockService = $exportBlockService;
        $this->exportHierarchyService = $exportHierarchyService;
        $this->exportPageService = $exportPageService;
        $this->exportImageService = $exportImageService;
    }

    /**
     * Execute the export service determined by type
     *
     * @param string $file
     * @param string $directory
     * @param string $type
     * @param array $identifiers
     * @return array
     */
    public function executeExport(string $file, string $directory, string $type, array $identifiers): array
    {
        $exportInfo = [];
        $this->state->emulateAreaCode(
            Area::AREA_ADMINHTML,
            function () use ($file, $directory, $type, $identifiers, &$exportInfo) {
                switch ($type) {
                    case 'block':
                        $exportInfo = $this->exportBlockService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::BLOCK_PATH : $directory, $identifiers);
                        break;
                    case 'image':
                        $exportInfo = $this->exportImageService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::IMAGE_PATH : $directory);
                        break;
                    case 'hierarchy':
                        $exportInfo = $this->exportHierarchyService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::HIERARCHY_PATH : $directory);
                        break;
                    case 'page':
                        $exportInfo = $this->exportPageService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::PAGE_PATH : $directory, $identifiers);
                        break;
                    default:
                        $exportInfo = $this->exportArchiveService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::ARCHIVE_PATH : $directory);
                }
            }
        );

        return $exportInfo;
    }
}
