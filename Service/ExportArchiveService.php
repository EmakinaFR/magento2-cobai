<?php

namespace Emakina\CmsImportExport\Service;

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
    ) {
        $this->exportBlockService = $exportBlockService;
        $this->exportHierarchyService = $exportHierarchyService;
        $this->exportPageService= $exportPageService;
        $this->exportImageService = $exportImageService;
        $this->zipService = $zipService;
    }

    /**
     * Create an archive with block, hierarchy, page and image and export it
     *
     * @param string $filename
     * @param string $directory
     * @return string
     * @throws CsvException
     */
    public function export(string $filename, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        $csvDirectory = $directory . $filename . '/';
        $this->exportBlockService->export('block', $csvDirectory);
        $this->exportHierarchyService->export('hierarchy', $csvDirectory);
        $this->exportPageService->export('page', $csvDirectory);
        $this->exportImageService->export('image', $csvDirectory);

        //Create archive
        $path = $this->zipService->createZipFromDir($directory . $filename, $csvDirectory, true);

        //Delete directory
        rmdir($csvDirectory);

        return $path;
    }
}
