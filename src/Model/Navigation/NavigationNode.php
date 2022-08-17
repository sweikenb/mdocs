<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Model\Navigation;

use Sweikenb\Mdocs\Api\NavigationNodeInterface;
use Sweikenb\Mdocs\Service\DocumentLinkerService;
use Sweikenb\Mdocs\Service\NavigationService;

class NavigationNode implements NavigationNodeInterface
{
    private ?NavigationNodeInterface $indexNode = null;

    /**
     * @var array<string, string>
     */
    private array $metaData = [];

    /**
     * @param array<int, NavigationNodeInterface> $children
     */
    public function __construct(
        private readonly string $label,
        private readonly string $link,
        private readonly string $filesystemReferenceName,
        private ?NavigationNodeInterface $parent,
        private array $children
    ) {
    }

    public function setIndexNode(NavigationNodeInterface $indexNode): void
    {
        $this->indexNode = $indexNode;
    }

    public function getIndexNode(): ?NavigationNodeInterface
    {
        return $this->indexNode;
    }

    public function getLabel(): string
    {
        return trim($this->metaData['title'] ?? $this->metaData['label'] ?? $this->label);
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getParent(): ?NavigationNodeInterface
    {
        return $this->parent;
    }

    public function setParent(?NavigationNodeInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return array<int, NavigationNodeInterface>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array<int, NavigationNodeInterface> $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function addChild(NavigationNodeInterface $child): void
    {
        $this->children[] = $child;
    }

    public function isFallbackLink(): bool
    {
        return in_array($this->link, [NavigationService::FALLBACK_LINK, DocumentLinkerService::FALLBACK_LINK]);
    }

    public function getFilesystemReferenceName(): string
    {
        return $this->filesystemReferenceName;
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function getMetaDataKey(string $key, string $default = ''): string
    {
        return $this->metaData[$key] ?? $default;
    }
}
