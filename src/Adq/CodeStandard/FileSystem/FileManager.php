<?php

namespace Adq\CodeStandard\FileSystem;

class FileManager
{

    /**
     * @param EditedFile[] $files
     * @return string[]
     */
    public function getFileNames(array $files): array
    {
        return array_map(function ($file) {
            return $file->getName();
        }, $files);
    }

    /**
     * @param EditedFile[] $files
     * @param array        $extensions
     * @return EditedFile[]
     */
    public function filterFilesByExtensions(array $files, array $extensions): array
    {
        return array_filter($files, function ($file) use ($extensions) {
            return isset($extensions[$file->getExtension()]);
        });
    }

    /**
     * @param EditedFile[] $files
     * @param array        $standards
     * @return EditedFile[][]
     */
    public function groupFilesByStandard(array $files, array $standards): array
    {
        $secondaryStandards = $standards;
        unset($secondaryStandards['main']);

        $groupedByStandardFiles = [];
        foreach ($files as $file) {
            $standardFound = false;
            foreach ($secondaryStandards as $standardName => $standard) {
                if (strpos($file->getName(), $standard['folder']) === 0) {
                    $groupedByStandardFiles[$standardName] =
                        $groupedByStandardFiles[$standardName] ?? [];
                    $groupedByStandardFiles[$standardName][] = $file;
                    $standardFound = true;
                    break;
                }
            }
            if (!$standardFound) {
                $groupedByStandardFiles['main'] =
                    $groupedByStandardFiles['main'] ?? [];
                $groupedByStandardFiles['main'][] = $file;
            }
        }
        return $groupedByStandardFiles;
    }

    /**
     * @param EditedFile[] $files
     * @param string       $fileName File path (can be an absolute path)
     * @return bool|EditedFile
     * @todo Avoid two return types
     */
    public function findFileByName(array $files, string $fileName)
    {
        $fileName = str_replace('\\', '/', $fileName);
        foreach ($files as $file) {
            if (substr($fileName, -strlen($file->getName())) === $file->getName()) {
                return $file;
            }
        }
        return false;
    }
}
