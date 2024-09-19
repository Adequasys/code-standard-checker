<?php

namespace Adq\CodeStandard\Checker;

use Adq\CodeStandard\FileSystem\EditedFile;
use Symfony\Component\Process\Process;

class PhpCsChecker extends CheckerAbstract
{
    protected $extensions = ['php' => true];

    /**
     * @param EditedFile[] $files
     * @return array An array of error messages
     */
    protected function getErrors(array $files): array
    {
        $this->checkConfigFile('standard');
        $phpCsResult = $this->runPhpCs($files);
        $errorMessages = [];
        foreach ($phpCsResult as $fileName => $phpCsFile) {
            $editedFile = $this->fileManager->findFileByName(
                $files,
                (string) $fileName
            );
            $editedFileName = $editedFile->getName();
            $editedLines = $editedFile->getEditedLines();
            foreach ($phpCsFile->warning as $warning) {
                if (isset($editedLines[(int) $warning['line']])) {
                    $errorMessages[] = (string) $warning . ' in ' .
                        $editedFileName . ' on line ' . $warning['line'];
                }
            }
            foreach ($phpCsFile->error as $error) {
                if (isset($editedLines[(int) $error['line']])) {
                    $errorMessages[] = (string) $error . ' in ' .
                        $editedFileName . ' on line ' . $error['line'];
                }
            }
        }
        return $errorMessages;
    }

    /**
     * @param EditedFile[] $files
     * @return \SimpleXMLElement[]
     */
    protected function runPhpCs(array $files): array
    {
        $results = [];
        $command = $this->vendorDirectories['composer'] . 'phpcs' .
            ' --report=xml  --standard=' . $this->config['standard'] . ' -';
        foreach ($files as $file) {
            $process = new Process(
                'git show :' . $file->getName() . ' | ' . $command
            );
            $process->run();
            $fileViolations = new \SimpleXMLElement($process->getOutput());
            if (!empty($fileViolations->file)) {
                $results[$file->getName()] = $fileViolations->file;
            }
        }
        return $results;
    }
}
