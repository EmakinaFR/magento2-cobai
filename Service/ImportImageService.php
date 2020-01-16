<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;

/**
 * Class ImportImageService
 */
class ImportImageService
{
    /**
     * @var ZipService
     */
    protected $zipService;

    /**
     * ImportImageService constructor.
     * @param ZipService $zipService
     */
    public function __construct(ZipService $zipService)
    {
        $this->zipService = $zipService;
    }

    /**
     * Import wysiwyg images from zip
     *
     * @param string $archiveFile
     * @param bool $force
     * @return array
     */
    public function import(string $archiveFile, bool $force): array
    {
        $errors = [];
        try {
            //Extract zip
            $directory = $this->zipService->extractDirectoryFromZip($archiveFile);
            $this->recurseCopy($directory, ExportConstants::IMAGE_DIRECTORY);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        } finally {
            return $errors;
        }
    }

    /**
     * Copy images recursively
     *
     * @param string $src
     * @param string $dst
     * @throws \Exception
     */
    private function recurseCopy(string $src, string $dst): void
    {
        $dir = opendir($src);

        if ($dir) {
            if (!is_dir($dst)) {
                mkdir($dst);
            }

            while ($file = readdir($dir)) {
                if (($file != '.') && ($file != '..')) {
                    $srcTmp = $src . $file;
                    $dstTmp = $dst . $file;

                    if (is_dir($srcTmp)) {
                        $this->recurseCopy($srcTmp . '/', $dstTmp . '/');
                    } else {
                        copy($srcTmp, $dstTmp);
                        unlink($srcTmp);
                    }
                }
            }
            closedir($dir);
            rmdir($src);
        } else {
            throw new \Exception(sprintf('Can not open dir %s', $src));
        }
    }
}
