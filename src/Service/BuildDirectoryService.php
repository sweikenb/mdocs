<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\DirectoryInterface;
use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Library\Filesystem\Exceptions\DirectoryTreeException;
use Sweikenb\Library\Filesystem\Exceptions\FileRenameException;
use Sweikenb\Library\Filesystem\Service\DirectoryTreeService;
use Sweikenb\Library\Markdown\Service\MarkdownService;
use Sweikenb\Mdocs\Event\AssetFileProcessingEvent;
use Sweikenb\Mdocs\Event\FilePostParseEvent;
use Sweikenb\Mdocs\Event\FilePreParseEvent;
use Sweikenb\Mdocs\Event\TreePostParseEvent;
use Sweikenb\Mdocs\Event\TreePreParseEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class BuildDirectoryService
{
    public function __construct(
        private readonly DirectoryTreeService $treeService,
        private readonly Filesystem $filesystem,
        private readonly MarkdownService $markdownService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws DirectoryTreeException
     */
    public function execute(string $source, string $target, string $templateDir, string $linkPrefix): void
    {
        // copy source to target
        $this->filesystem->mirror($source, $target, null, ['override' => true, 'delete' => true]);

        // read target into memory for manipulations
        $tree = $this->treeService->fetchTree($target);

        // dispatch tree-pre-parse event
        $this->eventDispatcher->dispatch(new TreePreParseEvent($tree, $templateDir, $linkPrefix));

        // convert now
        $this->walkTree($tree, function (FileInterface $file) use ($templateDir, $linkPrefix) {
            if ($this->markdownService->isMarkdownFile($file->getAbsPath())) {
                $this->eventDispatcher->dispatch(new FilePreParseEvent($file, $templateDir, $linkPrefix));
                $this->parseMarkdownToHtml($file);
                $this->renameMarkdownToHtmlFile($file);
                $this->eventDispatcher->dispatch(new FilePostParseEvent($file, $templateDir, $linkPrefix));
            } else {
                $this->eventDispatcher->dispatch(new AssetFileProcessingEvent($file, $templateDir, $linkPrefix));
            }
        });

        // dispatch tree-post-parse event
        $this->eventDispatcher->dispatch(new TreePostParseEvent($tree, $templateDir, $linkPrefix));

        // persist changes now
        $tree->persist();
    }

    private function walkTree(DirectoryInterface $directory, callable $callback): void
    {
        foreach ($directory->getFiles() as $file) {
            call_user_func($callback, $file);
        }
        foreach ($directory->getChildDirs() as $childDir) {
            $this->walkTree($childDir, $callback);
        }
    }

    private function parseMarkdownToHtml(FileInterface $file): void
    {
        $file->setContent(
            $this->markdownService->toHtml(
                $file->getContent()
            )
        );
    }

    /**
     * @throws FileRenameException
     */
    private function renameMarkdownToHtmlFile(FileInterface $file): void
    {
        $filename = basename($file->getRelPath());
        $pos = mb_strrpos($filename, '.');
        if ($pos !== false) {
            $filename = mb_substr($filename, 0, $pos);
        }

        $file->renameOnPersist(sprintf("%s.html", $filename));
    }
}
