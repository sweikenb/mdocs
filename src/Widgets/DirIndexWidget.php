<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Widgets;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Mdocs\Api\NavigationNodeInterface;
use Sweikenb\Mdocs\Service\DocumentLinkerService;
use Sweikenb\Mdocs\Service\NavigationService;

class DirIndexWidget extends AbstractWidget
{
    public function __construct(
        private readonly NavigationService $navigationService
    ) {
    }

    public function getIdentifier(): string
    {
        return 'DIR_INDEX';
    }

    /**
     * @return array<string, null|string|int|float|bool>
     */
    public function getUserSettings(): array
    {
        return [
            'skipp_same_level' => true,
            'skipp_self' => true,
            'depth' => -1,
            'dir' => './',
            'headline' => false, // or the actual headline to use
        ];
    }

    public function parse(FileInterface $file, array $settings): ?string
    {
        $sourceNode = $this->navigationService->findNodeForFile($file);
        if (!$sourceNode) {
            return null;
        }

        $settings = array_merge($this->getUserSettings(), $settings);
        $widgetHeadline = $settings['headline'];
        $startDir = $settings['dir'] ?? './';
        if (!in_array($startDir, ['', '.', './'])) {
            // TODO implement navigation feature
        }

        $skippSameLevel = (bool)$settings['skipp_same_level'];
        $skippSelf = (bool)$settings['skipp_self'];
        $depth = intval($settings['depth'] ?? -1);
        if ($depth < 1) {
            $depth = -1;
        }

        $list = [];
        if ($skippSameLevel || $sourceNode->getParent() === null) {
            $startNode = $sourceNode;
        } else {
            $startNode = $sourceNode->getParent();
        }

        foreach ($startNode->getChildren() as $node) {
            $skippInitialNode = ($skippSelf && $node === $sourceNode);
            if ($skippInitialNode) {
                $currentDepth = -1;
            } else {
                $currentDepth = 0;
            }
            $this->collectNestedList($node, $depth, $list, $currentDepth, $skippInitialNode);
        }

        // Add headline?
        $pageList = implode("\n", $list);
        if ($widgetHeadline !== false) {
            $pageList = sprintf("\n\n---\n\n**%s**\n\n%s\n\n---\n\n", trim((string)$widgetHeadline), $pageList);
        }

        return $pageList;
    }

    /**
     * @param array<int, string> $list
     */
    private function collectNestedList(
        NavigationNodeInterface $sourceNode,
        int $maxDepth,
        array &$list,
        int $currentDepth,
        bool $skippInitialNode
    ): void {
        // render list item?
        if (!$skippInitialNode) {
            $list[] = $this->getListItem($sourceNode, $currentDepth);
        }

        // render list items
        foreach ($sourceNode->getChildren() as $node) {
            $list[] = $this->getListItem($node, $currentDepth + 1);

            // render further children?
            if ($maxDepth === -1 || $maxDepth > ($currentDepth + 1)) {
                foreach ($node->getChildren() as $child) {
                    $this->collectNestedList($child, $maxDepth, $list, ($currentDepth + 2), false);
                }
            }
        }
    }

    private function getListItem(NavigationNodeInterface $node, int $currentDepth): string
    {
        $prefix = str_repeat(' ', $currentDepth * 4);
        if ($node->getLink() === NavigationService::FALLBACK_LINK) {
            return sprintf('%s* %s', $prefix, $node->getLabel());
        } else {
            if ($node->getLink() === DocumentLinkerService::FALLBACK_LINK) {
                return sprintf('%s* *%s*', $prefix, $node->getLabel());
            }
        }
        return sprintf('%s* [%s](%s)', $prefix, $node->getLabel(), $node->getLink());
    }
}
