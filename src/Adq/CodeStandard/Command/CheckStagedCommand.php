<?php

namespace Adq\CodeStandard\Command;

use Adq\CodeStandard\Checker\CheckerAbstract;
use Adq\CodeStandard\Git\DiffParser;
use Adq\CodeStandard\FileSystem\EditedFile;
use Adq\CodeStandard\FileSystem\FileManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class CheckStagedCommand extends Command
{
    private $scriptPath;

    /**
     * @var array
     */
    private $standardsConfig;

    /**
     * @var array
     */
    private $vendorDirectories;

    /**
     * @var DiffParser
     */
    private $diffParser;

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var CheckerAbstract[][]
     */
    private $checkers = [];


    /**
     * @param string $scriptPath
     */
    public function __construct(string $scriptPath)
    {
        $this->diffParser = new DiffParser();
        $this->fileManager = new  FileManager();
        $this->scriptPath = $scriptPath;
        $this->initVendorDirectories($scriptPath);
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('code-standard:check:staged')
            ->setDescription('Check Git staged changes violations')
            ->addOption(
                'standards-config',
                's',
                InputOption::VALUE_REQUIRED,
                'Standards description file path',
                $this->scriptPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'standards.yml'
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $standardsFile = $input->getOption('standards-config');
        if (!file_exists($standardsFile) &&
            file_exists(getcwd() . DIRECTORY_SEPARATOR . $standardsFile)
        ) {
            $standardsFile = getcwd() . DIRECTORY_SEPARATOR . $standardsFile;
        }
        if (!file_exists($standardsFile)) {
            throw new \InvalidArgumentException(
                'Project standards description file not found (' . $standardsFile . ')'
            );
        }
        $this->standardsConfig = Yaml::parse(
            file_get_contents($standardsFile)
        );

        $this->instantiateCheckers(dirname($standardsFile));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hasSeveralStandards = count($this->standardsConfig) > 1;
        $hasError = false;
        $this->printStartMessage($output);
        $editedFiles = $this->getEditedFiles();
        foreach ($editedFiles as $standardName => $files) {
            if (empty($this->checkers[$standardName])) {
                continue;
            }
            if ($hasSeveralStandards) {
                $this->printStandardNameMessage($output, $standardName);
            }
            foreach ($this->checkers[$standardName] as $checker) {
                $errorMessages = $checker->checkFiles($files);
                if (!empty($errorMessages)) {
                    $hasError = true;
                    $output->writeln(
                        '<bg=yellow;fg=black>' .
                        $checker->getName() . ' found the following errors</>'
                    );
                    foreach ($errorMessages as $errorMessage) {
                        $output->writeln($errorMessage);
                    }
                }
            }
        }

        $this->printEndMessage($output, $hasError);
        return $hasError ? 1 : 0;
    }

    /**
     * @param OutputInterface $output
     * @return void
     */
    private function printStartMessage(OutputInterface $output): void
    {
        $output->writeln('<question>                             </question>');
        $output->writeln('<question>       PRE-COMMIT HOOK       </question>');
        $output->writeln('<question>                             </question>');
        $output->writeln('');
    }

    /**
     * @param OutputInterface $output
     * @param string          $standardName
     * @return void
     */
    private function printStandardNameMessage(
        OutputInterface $output,
        string $standardName
    ): void {
        $output->writeln(
            '<info>' . $standardName . ' standard check...</info>'
        );
    }

    /**
     * @param OutputInterface $output
     * @param bool            $hasError
     * @return void
     */
    private function printEndMessage(OutputInterface $output, bool $hasError): void
    {
        $output->writeln('');
        if (!$hasError) {
            $output->writeln('<fg=black;bg=green>                             </>');
            $output->writeln('<fg=black;bg=green>       COMMIT ACCEPTED       </>');
            $output->writeln('<fg=black;bg=green>                             </>');
        } else {
            $output->writeln('<error>                             </error>');
            $output->writeln('<error>       COMMIT REJECTED       </error>');
            $output->writeln('<error>   (git commit --no-verify)  </error>');
            $output->writeln('<error>                             </error>');
        }
    }

    /**
     * @param string $configPath
     *
     * @return void
     */
    private function instantiateCheckers(string $configPath): void
    {
        foreach ($this->standardsConfig as $standardName => $standard) {
            if (empty($standard['checkers'])) {
                continue;
            }
            foreach ($standard['checkers'] as $checkerName => $checkerConfig) {
                if (!class_exists($checkerConfig['class'])) {
                    throw new \InvalidArgumentException(
                        'Class not found: ' . $checkerConfig['class']
                    );
                }
                $checker = new $checkerConfig['class']($this->fileManager);
                if (!($checker instanceof CheckerAbstract)) {
                    throw new \InvalidArgumentException(
                        'A checker must extend CheckerAbstract'
                    );
                }
                $checker->setName(ucfirst($checkerName))
                    ->setVendorDirectories($this->vendorDirectories)
                    ->setScriptPath($this->scriptPath)
                    ->setConfigPath($configPath);
                if (!empty($checkerConfig['options'])) {
                    $checker->setConfig($checkerConfig['options']);
                }
                $this->checkers[$standardName][] = $checker;
            }
        }
    }

    /**
     * @return EditedFile[][] Edited files, grouped by standard
     */
    private function getEditedFiles(): array
    {
        $process = new Process(
            'git diff -U0 --diff-filter=ACMR --cached'
        );
        $process->run();

        $editedFiles = $this->diffParser->parse(
            $process->getOutput()
        );

        return $this->fileManager->groupFilesByStandard(
            $editedFiles,
            $this->standardsConfig
        );
    }

    /**
     * @param string $scriptPath
     * @return void
     */
    private function initVendorDirectories(string $scriptPath): void
    {
        $this->vendorDirectories = [
            'composer' => $scriptPath . DIRECTORY_SEPARATOR .
                implode(DIRECTORY_SEPARATOR, ['vendor', 'bin']) . DIRECTORY_SEPARATOR,
            'node' => $scriptPath . DIRECTORY_SEPARATOR .
                implode(DIRECTORY_SEPARATOR, ['node_modules', '.bin']) . DIRECTORY_SEPARATOR
        ];
    }
}
