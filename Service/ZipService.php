<?php

namespace Emakina\Cobai\Service;

use Emakina\Cobai\Exception\ExportException;
use Emakina\Cobai\Exception\ImportException;
use ZipArchive;

/**
 * Class ZipService
 */
class ZipService
{
    /**
     * @param string $archiveName
     * @param string $directory
     * @param bool $delete
     * @return string
     * @throws ExportException
     */
    public function createZipFromDir(string $archiveName, string $directory, bool $delete = true): string
    {
        if (!is_dir($directory)) {
            throw new ExportException(sprintf("Can't create zip, directory %s not found", $directory));
        }

        $files = [];

        $zip = new ZipArchive();
        $path = sprintf('%s.zip', $archiveName);

        if ($zip->open($path, ZipArchive::CREATE)) {
            $files = $this->addDirectoryToZip($zip, $directory);
            $zip->close();
        }

        if ($delete) {
            //Delete files
            foreach ($files as $file) {
                unlink($file);
            }
        }

        return $path;
    }

    /**
     * Extract the zip into directory
     *
     * @param string $archiveFile
     * @return string
     * @throws ImportException
     */
    public function extractDirectoryFromZip(string $archiveFile): string
    {
        $zip = new \ZipArchive();
        $res = $zip->open($archiveFile);
        $directory = dirname($archiveFile) . '/extract/';

        if ($res) {
            //Extract zip
            $zip->extractTo($directory);
            $zip->close();
        } else {
            throw new ImportException(sprintf("Can't open zip archive %s", $archiveFile));
        }

        return $directory;
    }

    /**
     * Add a new directory into zip
     *
     * @param ZipArchive $zip
     * @param string $srcDirectory
     * @param string $destDirectory
     * @return array
     * @throws ExportException
     */
    private function addDirectoryToZip(ZipArchive &$zip, string $srcDirectory, string $destDirectory = ''): array
    {
        $files = [];
        if ($handle = opendir($srcDirectory)) {
            // Add all files inside the directory
            while ($entry = readdir($handle)) {
                $srcPath = $srcDirectory . $entry;
                $destPath = $destDirectory . $entry;
                if ($entry != "." && $entry != ".." && is_file($srcPath) && strpos($entry, '.') !== 0) {
                    $zip->addFile($srcPath, $destPath);
                    $files[] = $srcPath;
                } elseif ($entry != "." && $entry != ".." && is_dir($srcPath)) {
                    $files = array_merge($this->addDirectoryToZip($zip, $srcPath . '/', $destPath . '/'), $files);
                }
            }
            closedir($handle);
        } else {
            throw new ExportException(sprintf('Cannot open directory %s', $srcDirectory));
        }
        return $files;
    }
}
