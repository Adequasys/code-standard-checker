<?php

namespace Adq\CodeStandard\Checker;

use Adq\CodeStandard\FileSystem\EditedFile;
use Adq\CodeStandard\FileSystem\FileManager;

abstract class CheckerAbstract
{
    protected $vendorDirectories;
    protected $scriptPath;
    protected $configPath;
    protected $extensions = [];
    protected $config = [];

    /**
     * @var FileManager
     */
    protected $fileManager;

    private $name;

    /**
     * @param FileManager $fileManager
     * @throws \Exception
     */
    public function __construct(FileManager $fileManager)
    {
        if (empty($this->extensions)) {
            throw new \Exception(
                'A checker must care about file extensions'
            );
        }
        $this->fileManager = $fileManager;
    }

    /**
     * @param EditedFile[]  $files
     * @return array An array of error messages
     */
    abstract protected function getErrors(array $files): array;

    /**
     * @param EditedFile[] $files
     * @return array
     * @throws \Exception
     */
    public function checkFiles(array $files): array
    {
        $filesToCheck = $this->fileManager->filterFilesByExtensions(
            $files,
            $this->extensions
        );
        if (empty($filesToCheck)) {
            return [];
        }
        return $this->getErrors($filesToCheck);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): CheckerAbstract
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param string $configKey
     * @param string $errorMessage
     */
    protected function checkConfigFile(string $configKey, string $errorMessage = ''): void
    {
        if (!file_exists($this->config[$configKey]) &&
            file_exists($this->configPath . DIRECTORY_SEPARATOR . $this->config[$configKey])
        ) {
            $this->config[$configKey] = $this->configPath . DIRECTORY_SEPARATOR . $this->config[$configKey];
        }
        if ($errorMessage && !file_exists($this->config[$configKey])) {
            throw new \InvalidArgumentException(
                $errorMessage . ' (' . $this->config[$configKey] . ')'
            );
        }
    }

    /**
     * @param array $vendorDirectories
     * @return $this
     */
    public function setVendorDirectories(array $vendorDirectories): CheckerAbstract
    {
        $this->vendorDirectories = $vendorDirectories;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): CheckerAbstract
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $scriptPath
     * @return $this
     */
    public function setScriptPath(string $scriptPath): CheckerAbstract
    {
        $this->scriptPath = $scriptPath;
        return $this;
    }

    /**
     * @param string $configPath
     * @return $this
     */
    public function setConfigPath(string $configPath): CheckerAbstract
    {
        $this->configPath = $configPath;
        return $this;
    }
}
