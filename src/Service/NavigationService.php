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

    /**
     * @throws NavigationAlreadyParsedException
     */
    public function execute(DirectoryInterface $tree, string $linkPrefix): void
    {
        if ($this->rootNode !== null) {
            throw new NavigationAlreadyParsedException();
        }
        $this->linkPrefix = $linkPrefix;
        $this->rootNode = $this->createFallbackIndexNode(null);
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
        foreach ($dir->getFiles() as $file) {
            if (!$this->markdownService->isMarkdownFile($file->getRelPath())) {
                continue;
            }
            $node = $this->createNodeForFile($parentNode, $file);
            $parentNode->addChild($node);
            $this->fileToNodeRegistry[spl_object_id($file)] = $node;
            if ($numFiles === 1 || $this->isIndexFileExactMatch($file->getRelPath())) {
                $dirIndex = $node;
            }
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
            if ($dirIndex === null) {
                $dirIndex = $this->createFallbackIndexNode($parentNode);
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
        return $this->nodeFactory->create($label, $link, $parent, []);
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

    private function createFallbackIndexNode(?NavigationNodeInterface $parent): NavigationNodeInterface
    {
        $node = $this->nodeFactory->create('More', self::FALLBACK_LINK, $parent, []);
        $parent?->addChild($node);
        return $node;
    }
}
