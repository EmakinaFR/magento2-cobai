<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;
use Emakina\CmsImportExport\Logger\Logger;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

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
    ) {
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
     * @return string
     */
    public function executeExport(string $file, string $directory, string $type)
    {
        $path = '';
        $this->state->emulateAreaCode(
            Area::AREA_ADMINHTML,
            function () use ($file, $directory, $type, &$path) {
                switch ($type) {
                    case 'block':
                        $path = $this->exportBlockService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::BLOCK_PATH : $directory);
                        break;
                    case 'image':
                        $path = $this->exportImageService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::IMAGE_PATH : $directory);
                        break;
                    case 'hierarchy':
                        $path = $this->exportHierarchyService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::HIERARCHY_PATH : $directory);
                        break;
                    case 'page':
                        $path = $this->exportPageService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::PAGE_PATH : $directory);
                        break;
                    default:
                        $path = $this->exportArchiveService->export($file, $directory === ExportConstants::BASE_PATH ? ExportConstants::ARCHIVE_PATH : $directory);
                }
            }
        );

        return $path;
    }
}