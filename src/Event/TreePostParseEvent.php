<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Event;

use Sweikenb\Library\Filesystem\Api\DirectoryInterface;
use Symfony\Contracts\EventDispatcher\Event;

class TreePostParseEvent extends Event
{
    public function __construct(
        private readonly DirectoryInterface $tree,
        private readonly string $templateDir,
        private readonly string $linkPrefix
    ) {
    }

    public function getTree(): DirectoryInterface
    {
        return $this->tree;
    }

    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    public function getLinkPrefix(): string
    {
        return $this->linkPrefix;
    }
}
