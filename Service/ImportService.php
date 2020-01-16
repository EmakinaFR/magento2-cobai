<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Logger\Logger;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

/**
 * Class ExportService
 */
class ImportService
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
     * @var ImportArchiveService
     */
    protected $importArchiveService;

    /**
     * @var ImportBlockService
     */
    protected $importBlockService;

    /**
     * @var ImportHierarchyService
     */
    protected $importHierarchyService;

    /**
     * @var ImportPageService
     */
    protected $importPageService;

    /**
     * @var ImportImageService
     */
    protected $importImageService;

    /**
     * ImportService constructor.
     * @param State $state
     * @param Logger $logger
     * @param ImportBlockService $importBlockService
     * @param ImportHierarchyService $importHierarchyService
     * @param ImportPageService $importPageService
     * @param ImportArchiveService $importArchiveService
     * @param ImportImageService $importImageService
     */
    public function __construct(
        State $state,
        Logger $logger,
        ImportBlockService $importBlockService,
        ImportHierarchyService $importHierarchyService,
        ImportPageService $importPageService,
        ImportArchiveService $importArchiveService,
        ImportImageService $importImageService
    ) {
        $this->state = $state;
        $this->logger = $logger;
        $this->importArchiveService = $importArchiveService;
        $this->importBlockService = $importBlockService;
        $this->importHierarchyService = $importHierarchyService;
        $this->importPageService= $importPageService;
        $this->importImageService = $importImageService;
    }

    /**
     * Execute import service determined by type
     *
     * @param string $file
     * @param string $type
     * @param bool $force
     * @return array
     */
    public function executeImport(string $file, string $type, bool $force)
    {
        $errors = [];
        $this->state->emulateAreaCode(
            Area::AREA_ADMINHTML,
            function () use ($file, $type, $force, &$errors) {
                switch ($type) {
                    case 'block':
                        $errors = $this->importBlockService->import($file, $force);
                        break;
                    case 'image':
                        $errors = $this->importImageService->import($file, $force);
                        break;
                    case 'hierarchy':
                        $errors = $this->importHierarchyService->import($file, $force);
                        break;
                    case 'page':
                        $errors = $this->importPageService->import($file, $force);
                        break;
                    default:
                        $errors = $this->importArchiveService->import($file, $force);
                        break;
                }
            }
        );

        return $errors;
    }
}
