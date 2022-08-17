<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\DirectoryInterface;
use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Library\Markdown\Service\MarkdownInterceptorService;
use Sweikenb\Library\Markdown\Service\MarkdownService;
use Sweikenb\Mdocs\Api\NavigationNodeInterface;
use Sweikenb\Mdocs\Exceptions\NavigationAlreadyParsedException;
use Sweikenb\Mdocs\Factory\NavigationNodeFactory;

class NavigationService
{
    const FALLBACK_LINK = '#nav';

    /**
     * @var array<int, NavigationNodeInterface>
     */
    private array $fileToNodeRegistry = [];

    /**
     * @var array<int, FileInterface>
     */
    private array $nodeToFileRegistry = [];

    private ?string $linkPrefix = null;
    private ?NavigationNodeInterface $rootNode = null;

    public function __construct(
        private readonly MarkdownService $markdownService,
        private readonly MarkdownInterceptorService $markdownInterceptorService,
        private readonly DocumentLinkRegisterService $documentLinkRegisterService,
        private readonly NavigationNodeFactory $nodeFactory
    ) {
    }

    public function getLinkPrefix(): ?string
    {
        return $this->linkPrefix;
    }

    public function getRootNode(): ?NavigationNodeInterface
    {
        return $this->rootNode;
    }

    public function findNodeForFile(FileInterface $file): ?NavigationNodeInterface
    {
        return $this->fileToNodeRegistry[spl_object_id($file)] ?? null;
    }

    public function findFileForNode(NavigationNodeInterface $node): ?FileInterface
    {
        return $this->nodeToFileRegistry[spl_object_id($node)] ?? null;
    }

    /**
     * @throws NavigationAlreadyParsedException
     */
    public function execute(DirectoryInterface $tree, string $linkPrefix): void
    {
        if ($this->rootNode !== null) {
            throw new NavigationAlreadyParsedException();
        }
        $this->linkPrefix = $linkPrefix;
        $this->rootNode = $this->createFallbackIndexNode(null, $tree);
        $this->walkTree($tree, $this->rootNode);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function walkTree(DirectoryInterface $dir, NavigationNodeInterface $parentNode): void
    {
        $dirIndex = null;
        $isRootLevel = $parentNode === $this->rootNode;

        $numFiles = count($dir->getFiles());
        $numMarkdownFiles = 0;
        $lastMarkdownFileNode = null;
        foreach ($dir->getFiles() as $file) {
            if (!$this->markdownService->isMarkdownFile($file->getRelPath())) {
                continue;
            }
            $node = $this->createNodeForFile($parentNode, $file);
            $parentNode->addChild($node);
            $this->fileToNodeRegistry[spl_object_id($file)] = $node;
            $this->nodeToFileRegistry[spl_object_id($node)] = $file;
            if ($numFiles === 1 || $this->isIndexFileExactMatch($file->getRelPath())) {
                $dirIndex = $node;
            }
            $numMarkdownFiles++;
            $lastMarkdownFileNode = $node;
        }

        if ($dirIndex === null) {
            if ($numMarkdownFiles === 1) {
                $dirIndex = $lastMarkdownFileNode;
            }
            if ($dirIndex === null) {
                foreach ($dir->getFiles() as $file) {
                    if (!$this->markdownService->isMarkdownFile($file->getRelPath())) {
                        continue;
                    }
                    if ($this->isIndexFileCandidate($file->getRelPath())) {
                        $dirIndex = $this->fileToNodeRegistry[spl_object_id($file)];
                        break;
                    }
                }
            }
            if ($dirIndex === null) {
                $dirIndex = $this->createFallbackIndexNode($parentNode, $dir);
                foreach ($dir->getFiles() as $file) {
                    $node = $this->findNodeForFile($file);
                    if ($node === null) {
                        continue;
                    }
                    if ($node->getParent() && $node->getParent() !== $dirIndex) {
                        $node->getParent()->setChildren([$dirIndex]);
                    }
                    $node->setParent($dirIndex);
                    $dirIndex->addChild($node);
                }
            }
        }

        if ($isRootLevel) {
            $this->rootNode?->setIndexNode($dirIndex);
        }

        foreach ($dir->getChildDirs() as $childDir) {
            $this->walkTree($childDir, $dirIndex);
        }
    }

    private function createNodeForFile(?NavigationNodeInterface $parent, FileInterface $file): NavigationNodeInterface
    {
        $label = $this->markdownInterceptorService->getFirstTitle($file->getContent()) ?? basename($file->getRelPath());
        $link = $this->documentLinkRegisterService->getLink($file) ?? '#err';
        return $this->nodeFactory->create($label, $link, basename($file->getRelPath()), $parent, []);
    }

    private function isIndexFileExactMatch(string $filename): bool
    {
        $result = preg_match(
            sprintf(
                '/^index\.(%s)$/i',
                implode('|', $this->markdownService->getFileExtensions())
            ),
            $filename
        );
        return $result === 1;
    }

    private function isIndexFileCandidate(string $filename): bool
    {
        $result = preg_match(
            sprintf(
                '/(index|readme|documentation|docs)\.(%s)$/i',
                implode('|', $this->markdownService->getFileExtensions())
            ),
            $filename
        );
        return $result === 1;
    }

    private function createFallbackIndexNode(
        ?NavigationNodeInterface $parent,
        DirectoryInterface $parentDir
    ): NavigationNodeInterface {
        $refName = basename($parentDir->getRelPath());
        $label = $this->directoryNameToChapterName($refName);
        $node = $this->nodeFactory->create($label, self::FALLBACK_LINK, $refName, $parent, []);
        $parent?->addChild($node);
        return $node;
    }

    private function directoryNameToChapterName(string $name): string
    {
        if (preg_match('/^(\d+)(.*)/', $name, $matches)) {
            $name = $matches[2];
        }

        if (preg_match_all('/([A-Z]+[a-z0-9]+)/', $name, $matches)) {
            $name = implode('-', array_map('ucfirst', $matches[1]));
        }

        return preg_replace('/[\s_-]+/i', ' ', $name);
    }
}
