<?php

namespace Adq\CodeStandard\FileSystem;

class EditedFile
{
    protected $path;
    protected $extension;
    protected $editedLines = [];
    
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @param string $lineNumber
     * @param string $lineContent
     * @return void
     */
    public function registerEditedLine(string $lineNumber, string $lineContent): void
    {
        $this->editedLines[$lineNumber] = $lineContent;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->path;
    }
    
    /**
     * @return array
     */
    public function getEditedLines(): array
    {
        return $this->editedLines;
    }
}
