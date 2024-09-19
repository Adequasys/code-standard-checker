<?php

namespace Adq\CodeStandard\Git;

use Adq\CodeStandard\FileSystem\EditedFile;

class DiffParser
{
    /**
     * @param string $diff
     * @return EditedFile[]
     */
    public function parse(string $diff): array
    {
        $editedFiles = [];
        $filesDiff = explode('+++ b/', $diff);
        array_shift($filesDiff);
        foreach ($filesDiff as $fileDiff) {
            $fileChanges = explode("\n@@", $fileDiff);
            $fileName = trim(array_shift($fileChanges));
            $file = new EditedFile($fileName);
            foreach ($fileChanges as $fileChange) {
                preg_match('/\+([0-9]+)(,[0-9]+)? @@/', $fileChange, $matches);
                $changeStartLine = intval($matches[1]);
                $addedLines = array_filter(
                    explode("\n", $fileChange),
                    function ($line) {
                        return strpos($line, '+') === 0;
                    }
                );
                $index = 0;
                foreach ($addedLines as $addedLine) {
                    $file->registerEditedLine(
                        $changeStartLine + $index++,
                        substr($addedLine, 1)
                    );
                }
            }
            $editedFiles[] = $file;
        }
        return $editedFiles;
    }
}
