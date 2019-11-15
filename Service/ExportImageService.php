<?php

namespace Emakina\CmsImportExport\Service;

use Emakina\CmsImportExport\Constant\ExportConstants;

/**
 * Class ExportImageService
 */
class ExportImageService
{
    /**
     * @var ZipService
     */
    protected $zipService;

    /**
     * ExportImageService constructor.
     * @param ZipService $zipService
     */
    public function __construct(ZipService $zipService)
    {
        $this->zipService = $zipService;
    }

    /**
     * Export wysiwyg images into a zip
     * @param string $filename
     * @param string $directory
     * @return string
     * @throws \Exception
     */
    public function export(string $filename, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $archivePath = $directory . $filename;

        //Create archive
        $path = $this->zipService->createZipFromDir($archivePath, ExportConstants::IMAGE_DIRECTORY, false);

        return $path;
    }
}
