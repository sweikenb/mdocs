<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Factory;

use Sweikenb\Mdocs\Api\NavigationNodeInterface;
use Sweikenb\Mdocs\Model\Navigation\NavigationNode;

class NavigationNodeFactory
{
    /**
     * @param array<int, NavigationNodeInterface> $children
     */
    public function create(
        string $label,
        string $link,
        ?NavigationNodeInterface $parent,
        array $children
    ): NavigationNodeInterface {
        return new NavigationNode($label, $link, $parent, $children);
    }
}
