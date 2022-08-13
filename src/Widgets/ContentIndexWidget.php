<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Widgets;

use Sweikenb\Library\Filesystem\Api\FileInterface;

class ContentIndexWidget extends AbstractWidget
{
    public function getIdentifier(): string
    {
        return 'CONTENT_INDEX';
    }

    /**
     * @return array<string, null|string|int|float|bool>
     */
    public function getUserSettings(): array
    {
        return [
            'ignore_first' => true,
        ];
    }

    public function parse(FileInterface $file, array $settings): ?string
    {
        $settings = array_merge($this->getUserSettings(), $settings);
        $lines = explode("\n", $file->getContent());

        $doIgnoreFirst = (bool)$settings['ignore_first'];

        $contentIndex = [];
        $isFirstHeadline = true;
        $isCodeBlock = false;
        $lastLevel = null;
        $lastRenderLevel = null;
        foreach ($lines as &$line) {
            $line = trim($line);
            if (mb_substr($line, 0, 3) === '```') {
                $isCodeBlock = !$isCodeBlock;
                continue;
            }
            if (!$isCodeBlock && preg_match('/^(#+)\s?(.+)/', $line, $match)) {
                if ($isFirstHeadline) {
                    $isFirstHeadline = false;
                    if ($doIgnoreFirst) {
                        continue;
                    }
                }

                $level = mb_strlen($match[1]);
                if ($lastLevel === null) {
                    $lastRenderLevel = 0;
                } else {
                    if ($lastLevel > $level) {
                        if ($lastRenderLevel < $level) {
                            $lastRenderLevel--;
                        } else {
                            $lastRenderLevel = $level;
                        }
                    } else if ($lastLevel < $level) {
                        $lastRenderLevel++;
                    }
                }

                $lastLevel = max(1, $level);
                $lastRenderLevel = max(0, $lastRenderLevel);

                $headline = trim($match[2]);
                $prefix = str_repeat(' ', $lastRenderLevel * 4);

                if (preg_match('/\{(#[a-z0-9-]+)}$/i', $line, $anchorMatch)) {
                    $anchor = $anchorMatch[1];
                    $headline = str_replace($anchorMatch[0], '', $headline);
                } else {
                    $anchor = sprintf("#%s", $this->normalizeAnchor(mb_strtolower($headline)));
                    $line .= sprintf(' {%s}', $anchor);
                }

                $contentIndex[] = sprintf("%s* [%s](%s)", $prefix, $headline, $anchor);
            }
        }

        $file->setContent(implode("\n", $lines));

        return implode("\n", $contentIndex);
    }

    private function normalizeAnchor(string $anchor): string
    {
        return (string)preg_replace('/[^a-z0-9-]/i', '-', $anchor);
    }
}
