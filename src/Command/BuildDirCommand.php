<?php

namespace Sweikenb\Mdocs\Command;

use Sweikenb\Library\Filesystem\Exceptions\DirectoryTreeException;
use Sweikenb\Mdocs\Service\BuildDirectoryService;
use Sweikenb\Mdocs\Service\BuildThemeService;
use Sweikenb\Mdocs\Service\LinkPrefixNormalizerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'build:dir',
    description: 'Builds the provided directory',
)]
class BuildDirCommand extends Command
{
    private ?InputInterface $input = null;
    private ?SymfonyStyle $io = null;

    public function __construct(
        private readonly BuildDirectoryService $buildDirectoryService,
        private readonly BuildThemeService $buildThemeService,
        private readonly LinkPrefixNormalizerService $linkPrefixNormalizerService,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'sourceDir',
                InputArgument::OPTIONAL,
                'Source directory',
                './docs'
            )
            ->addArgument(
                'targetDir',
                InputArgument::OPTIONAL,
                'Target directory',
                './build'
            )
            ->addOption(
                'base-url',
                null,
                InputOption::VALUE_OPTIONAL,
                'Base-URL to render links for',
                ''
            )
            ->addOption(
                'base-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Base-path to prepend to the actual directory when creating hyperlinks',
                '/'
            )
            ->addOption(
                'template-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the directory to look for the templates to use for rendering the docs',
                ''
            )
            ->addOption(
                'theme-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the directory to look for the theme to use for displaying the docs templates',
                ''
            );
    }

    /**
     * @throws DirectoryTreeException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $sourceDir = @realpath(strval($input->getArgument('sourceDir')));
        if (!$sourceDir || !is_dir($sourceDir) || !is_readable($sourceDir)) {
            $this->io->error(
                sprintf(
                    'Invalid source directory (%s), does it exist and is readable?',
                    strval($input->getArgument('sourceDir'))
                )
            );
            return self::FAILURE;
        }

        $targetDir = @realpath(strval($input->getArgument('targetDir')));
        if (!$targetDir || !is_dir($targetDir) || !is_writable($targetDir)) {
            $this->io->error(
                sprintf(
                    'Invalid target directory (%s), does it exist and is writeable?',
                    /** @phpstan-ignore-next-line */
                    $input->getArgument('targetDir')
                )
            );
            return self::FAILURE;
        }

        $templateDir = $this->checkOptionalDirDefinition(
            'template-dir',
            'Invalid template directory (%s), does it exist and is readable?'
        );
        if ($templateDir === null) {
            return self::FAILURE;
        }

        $themeDir = $this->checkOptionalDirDefinition(
            'theme-dir',
            'Invalid theme directory (%s), does it exist and is readable?'
        );
        if ($themeDir === null) {
            return self::FAILURE;
        }

        $baseUrl = trim(strval($input->getOption('base-url')));
        $basePath = trim(strval($input->getOption('base-path')));
        if ($baseUrl !== '' && !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->io->error('Invalid base URL');
            return self::FAILURE;
        }

        // build template
        $linkPrefix = $this->linkPrefixNormalizerService->execute($baseUrl, $basePath);
        $this->buildDirectoryService->execute($sourceDir, $targetDir, $templateDir, $linkPrefix);

        // build theme
        if ($themeDir === '') {
            $themeDir = realpath(__DIR__ . '/../../theme');
            if ($themeDir === false) {
                $this->io->error('Can not find default theme directors');
                return self::FAILURE;
            }
        }
        $this->buildThemeService->execute($themeDir, $targetDir, $linkPrefix);

        return self::SUCCESS;
    }

    private function checkOptionalDirDefinition(string $optName, string $errMessage): ?string
    {
        $dir = strval($this->input?->getOption($optName));
        if ($dir !== '') {
            $dir = @realpath($dir);
            if (!$dir || !is_dir($dir) || !is_readable($dir)) {
                $this->io?->error(sprintf($errMessage, strval($this->input?->getOption($optName))));
                return null;
            }
        }
        return $dir;
    }
}
