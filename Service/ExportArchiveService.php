<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;
use League\Csv\Exception as CsvException;

/**
 * Class ExportArchiveService
 */
class ExportArchiveService
{
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
     * @var ZipService
     */
    protected $zipService;

    /**
     * ExportArchiveService constructor.
     * @param ExportBlockService $exportBlockService
     * @param ExportHierarchyService $exportHierarchyService
     * @param ExportPageService $exportPageService
     * @param ExportImageService $exportImageService
     * @param ZipService $zipService
     */
    public function __construct(
        ExportBlockService $exportBlockService,
        ExportHierarchyService $exportHierarchyService,
        ExportPageService $exportPageService,
        ExportImageService $exportImageService,
        ZipService $zipService
    )
    {
        $this->exportBlockService = $exportBlockService;
        $this->exportHierarchyService = $exportHierarchyService;
        $this->exportPageService = $exportPageService;
        $this->exportImageService = $exportImageService;
        $this->zipService = $zipService;
    }

    /**
     * Create an archive with block, hierarchy, page and image and export it
     *
     * @param string $filename
     * @param string $directory
     * @return array
     * @throws \Exception
     */
    public function export(string $filename, string $directory): array
    {
        $exportInfo = [];
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $csvDirectory = $directory . $filename . '/';
        $this->exportBlockService->export(ExportConstants::BLOCK_FILE_NAME, $csvDirectory);
        $this->exportHierarchyService->export(ExportConstants::HIERARCHY_FILE_NAME, $csvDirectory);
        $this->exportPageService->export(ExportConstants::PAGE_FILE_NAME, $csvDirectory);
        $this->exportImageService->export(ExportConstants::IMAGE_FILE_NAME, $csvDirectory);

        //Create archive
        $exportInfo['path'] = $this->zipService->createZipFromDir($directory . $filename, $csvDirectory, true);

        //Delete directory
        rmdir($csvDirectory);

        return $exportInfo;
    }
}
