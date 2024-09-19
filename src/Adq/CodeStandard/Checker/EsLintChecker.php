<?php

namespace Adq\CodeStandard\Checker;

use Adq\CodeStandard\FileSystem\EditedFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EsLintChecker extends CheckerAbstract
{
    protected $extensions = ['js' => true, 'ts' => true, 'tsx' => true];

    /**
     * @param EditedFile[] $files
     * @return array An array of error messages
     */
    protected function getErrors(array $files): array
    {
        $errorMessages = [];
        $this->checkConfigFile('standard', 'EsLint standard file not found');
        $esLintResults = $this->runEsLint($files);
        foreach ($esLintResults as $esLintFile) {
            $editedFile = $this->fileManager->findFileByName(
                $files,
                $esLintFile->filePath
            );
            $editedLines = $editedFile->getEditedLines();
            foreach ($esLintFile->messages as $violation) {
                if (isset($editedLines[$violation->line])) {
                    $errorMessages[] = $violation->message . ' in ' .
                        $editedFile->getName() . ' on line ' . $violation->line;
                }
            }
        }
        return $errorMessages;
    }

    /**
     * @param EditedFile[] $files
     * @return array
     */
    protected function runEsLint(array $files): array
    {
        $results = [];
        $command = $this->vendorDirectories['node'] . 'eslint' .
            ' --no-eslintrc --format=json  --config ' . $this->getConfigFileCopy() .
            ' --stdin --stdin-filename=';
        foreach ($files as $file) {
            $fileName = $file->getName();
            $process = new Process(
                'git show :' . $fileName . ' | ' . $command . $fileName
            );
            $process->run();

            if ($process->getExitCode() > 1) {
                throw new ProcessFailedException($process);
            }

            $fileViolations = json_decode($process->getOutput());
            if (!empty($fileViolations[0])) {
                $results[] = $fileViolations[0];
            }
        }
        return $results;
    }

    private function getConfigFileCopy(): string
    {
        $configCopiesDir = $this->scriptPath . DIRECTORY_SEPARATOR
            . 'eslint-config-copies';

        if (!is_dir($configCopiesDir)) {
            mkdir($configCopiesDir);
        }

        return $this->copyConfigFile(
            $this->config['standard'],
            $configCopiesDir
        );
    }

    private function copyConfigFile(
        string $file,
        string $configCopiesDir
    ): string {
        $configFileCopy
            = $configCopiesDir . DIRECTORY_SEPARATOR . basename($file);

        copy($file, $configFileCopy);

        $extendedConfig
            = json_decode(file_get_contents($configFileCopy), true)['extends']
            ?? null;

        if ($extendedConfig) {
            $extendedFile
                = dirname($file) . DIRECTORY_SEPARATOR . $extendedConfig;

            if (is_file($extendedFile)) {
                $this->copyConfigFile($extendedFile, $configCopiesDir);
            }
        }

        return $configFileCopy;
    }
}
