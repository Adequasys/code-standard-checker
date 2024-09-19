<?php

namespace Adq\CodeStandard\Checker;

use Adq\CodeStandard\FileSystem\EditedFile;

class ForbiddenWordsChecker extends CheckerAbstract
{
    protected $extensions = [
        'php' => true,
        'js' => true
    ];

    /**
     * @param EditedFile[] $files
     * @return array An array of error messages
     */
    protected function getErrors(array $files): array
    {
        $errors = [];
        foreach ($files as $file) {
            $addedCode = implode('', $file->getEditedLines());
            preg_match_all(
                $this->config['pattern'],
                $addedCode,
                $matches
            );
            if (!empty($matches[0])) {
                $errors[] = '"' . implode('", "', $matches[0]) .
                    '" found in ' . $file->getName();
            }
        }

        return $errors;
    }
}
