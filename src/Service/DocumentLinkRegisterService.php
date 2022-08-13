<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\DirectoryInterface;
use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Library\Markdown\Service\MarkdownService;
use Sweikenb\Mdocs\Exceptions\DocumentLinkRegisterException;

class DocumentLinkRegisterService
{
    /**
     * @var array<string,FileInterface>
     */
    private array $linkRegister = [];
    private string $linkPrefix = '';

    public function __construct(
        private readonly MarkdownService $markdownService
    ) {
    }

    /**
     * @throws DocumentLinkRegisterException
     */
    public function execute(DirectoryInterface $tree, string $linkPrefix): void
    {
        $this->linkRegister = [];
        $this->linkPrefix = $linkPrefix;
        $this->walkTree($tree, $this->linkRegister);
    }

    /**
     * @param string $relLink
     *
     * @return bool
     */
    public function contains(string $relLink): bool
    {
        return isset($this->linkRegister[$relLink]);
    }

    public function getFileForLink(string $relLink): ?FileInterface
    {
        return $this->linkRegister[$relLink] ?? null;
    }

    public function getLink(FileInterface $file, string $linkSuffix = ''): ?string
    {
        $relPath = array_search($file, $this->linkRegister);
        if ($relPath) {
            return sprintf(
                "%s.html%s",
                ($this->linkPrefix . $relPath),
                $linkSuffix
            );
        }
        return null;
    }

    /**
     * @param array<string, FileInterface> $register
     *
     * @throws DocumentLinkRegisterException
     */
    private function walkTree(DirectoryInterface $directory, array &$register): void
    {
        foreach ($directory->getFiles() as $file) {
            if ($this->markdownService->isMarkdownFile($file->getRelPath())) {
                $relLink = preg_replace(
                    sprintf('/\.(%s)$/i', implode('|', $this->markdownService->getFileExtensions())),
                    '',
                    $file->getRelPath()
                );
                if (isset($register[$relLink])) {
                    throw new DocumentLinkRegisterException(
                        sprintf('There is already a document under this link: %s', $relLink)
                    );
                }
                $register[$relLink] = $file;
            }
        }
        foreach ($directory->getChildDirs() as $childDir) {
            $this->walkTree($childDir, $register);
        }
    }
}
