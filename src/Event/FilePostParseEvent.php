<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Event;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Symfony\Contracts\EventDispatcher\Event;

class FilePostParseEvent extends Event
{
    public function __construct(
        private readonly FileInterface $file,
        private readonly string $templateDir,
        private readonly string $linkPrefix
    ) {
    }

    public function getFile(): FileInterface
    {
        return $this->file;
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
