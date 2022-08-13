<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Api;

use Sweikenb\Library\Filesystem\Api\FileInterface;

interface WidgetInterface
{
    public function getIdentifier(): string;

    /**
     * @return array<string, null|string|int|float|bool>
     */
    public function getUserSettings(): array;

    /**
     * @param FileInterface $file
     * @param array<string, null|string|int|float|bool> $settings
     *
     * @return string|null
     */
    public function parse(FileInterface $file, array $settings): ?string;
}
