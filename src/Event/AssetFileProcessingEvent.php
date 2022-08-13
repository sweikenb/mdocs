<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Event;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AssetFileProcessingEvent extends Event
{
    public function __construct(
        private readonly FileInterface $assetFile,
        private readonly string $templateDir,
        private readonly string $linkPrefix
    ) {
    }

    public function getAssetFile(): FileInterface
    {
        return $this->assetFile;
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
