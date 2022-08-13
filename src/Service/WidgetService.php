<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Mdocs\Api\WidgetInterface;

class WidgetService
{
    /**
     * @var array<string, WidgetInterface>
     */
    private array $widgets = [];

    public function register(WidgetInterface $widget): void
    {
        $this->widgets[$widget->getIdentifier()] = $widget;
    }

    public function parseFile(FileInterface $file): void
    {
        if (preg_match_all('/\[\[\[\s*([a-z\d_]+)\s*:?(.*)?]]]/i', $file->getContent(), $matches)) {
            foreach ($matches[0] as $i => $search) {
                $replace = '';
                $widgetName = trim($matches[1][$i]);
                if (isset($this->widgets[$widgetName])) {
                    $widgetSettings = $this->parseSettings(trim($matches[2][$i]));
                    $replace = $this->widgets[$widgetName]->parse($file, $widgetSettings) ?? $replace;
                }
                $file->setContent(str_replace($search, $replace, $file->getContent()));
            }
        }
    }

    /**
     * @param string $settings
     *
     * @return array<string, null|string|int|float|bool>
     */
    public function parseSettings(string $settings): array
    {
        $parsed = @json_decode($settings, true);
        if (!$parsed || !is_array($parsed)) {
            return [];
        }
        return $parsed;
    }
}
