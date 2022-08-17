<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Api;

interface NavigationNodeInterface
{
    public function setIndexNode(NavigationNodeInterface $indexNode): void;

    public function getIndexNode(): ?NavigationNodeInterface;

    public function getLabel(): string;

    public function getLink(): string;

    public function getParent(): ?NavigationNodeInterface;

    public function setParent(?NavigationNodeInterface $parent): void;

    /**
     * @return array<int, NavigationNodeInterface>
     */
    public function getChildren(): array;

    /**
     * @param array<int, NavigationNodeInterface> $children
     */
    public function setChildren(array $children): void;

    public function addChild(NavigationNodeInterface $child): void;

    public function isFallbackLink(): bool;

    public function getFilesystemReferenceName(): string;

    /**
     * @param array<string, string> $metaData
     */
    public function setMetaData(array $metaData): void;

    /**
     * @return array<string, string>
     */
    public function getMetaData(): array;

    public function getMetaDataKey(string $key, string $default = ''): string;
}
