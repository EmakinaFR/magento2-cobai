<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Constant\ExportConstants;

/**
 * Class ImportArchiveService
 */
class ImportArchiveService
{
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
     * @var ZipService
     */
    protected $zipService;

    /**
     * ImportArchiveService constructor.
     * @param ImportBlockService $importBlockService
     * @param ImportHierarchyService $importHierarchyService
     * @param ImportPageService $importPageService
     * @param ImportImageService $importImageService
     * @param ZipService $zipService
     */
    public function __construct(
        ImportBlockService $importBlockService,
        ImportHierarchyService $importHierarchyService,
        ImportPageService $importPageService,
        ImportImageService $importImageService,
        ZipService $zipService
    ) {
        $this->importBlockService = $importBlockService;
        $this->importImageService = $importImageService;
        $this->importHierarchyService = $importHierarchyService;
        $this->importPageService = $importPageService;
        $this->zipService = $zipService;
    }

    /**
     * Import archive (blocks, hierarchies, images, pages) from zip
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

            //Scan extract directory
            if (is_dir($directory)) {
                $files = scandir($directory);
                if (is_array($files)) {
                    $files = array_diff($files, ['.', '..']);
                    asort($files);

                    //Import all files
                    foreach ($files as $file) {
                        $fileWithPath = $directory . $file;

                        switch ($file) {
                            case ExportConstants::BLOCK_FILE_NAME . '.csv':
                                $errors = array_merge($this->importBlockService->import($fileWithPath, $force), $errors);
                                break;
                            case ExportConstants::IMAGE_FILE_NAME . '.zip':
                                $errors = array_merge($this->importImageService->import($fileWithPath, $force), $errors);
                                break;
                            case ExportConstants::PAGE_FILE_NAME . '.csv':
                                $errors = array_merge($this->importPageService->import($fileWithPath, $force), $errors);
                                break;
                            case ExportConstants::HIERARCHY_FILE_NAME . '.csv':
                                $errors = array_merge($this->importHierarchyService->import($fileWithPath, $force), $errors);
                                break;
                            default:
                                $errors[] = sprintf('Unknown type %s', $file);
                        }
                        //Delete file
                        unlink($fileWithPath);
                    }
                    //Delete directory
                    rmdir($directory);
                } else {
                    $errors[] = sprintf('Can not scan dir %s', $directory);
                }
            } else {
                $errors[] = sprintf('%s is not a directory', $directory);
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        } finally {
            return $errors;
        }
    }
}
