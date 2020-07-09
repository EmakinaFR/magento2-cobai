<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;

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
        $archivePath = $directory . $filename;

        //Create archive
        $exportInfo['path'] = $this->zipService->createZipFromDir($archivePath, ExportConstants::IMAGE_DIRECTORY, false);

        return $exportInfo;
    }
}
