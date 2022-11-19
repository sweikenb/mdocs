<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\FileInterface;

class WidgetPreProcessorService
{
    public function __construct(
        private readonly NavigationService $navigationService
    ) {
    }

    public function execute(FileInterface $file): void
    {
        $this->metadataExtractor($file);
    }

    private function metadataExtractor(FileInterface $file): void
    {
        $pattern = sprintf('/^(%s){3,}$/i', implode('|', ['-', '=', '~', '_']));

        $offset = null;
        $delimiter = '';
        $metaLines = [];

        $lines = explode("\n", trim($file->getContent()));
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if ($i === 0) {
                if (!preg_match($pattern, $line, $matches)) {
                    return;
                }
                $delimiter = $matches[0];
                continue;
            }
            if ($line === $delimiter) {
                $offset = $i + 1;
                break;
            }
            if (preg_match('/^[a-z0-9]/i', $line)) {
                $metaLines[] = $line;
            } else {
                return;
            }
        }

        // if the offset is still null, no matching end was found, so we have no metadata
        if ($offset === null) {
            return;
        }

        // update the content to remove the meta information
        $file->setContent(trim((string)implode("\n", array_slice($lines, $offset))));

        // get the navigation node
        $node = $this->navigationService->findNodeForFile($file);
        if ($node === null) {
            return;
        }

        // set meta data
        $node->setMetaData($this->parseMetaLines($metaLines));
    }

    /**
     * @param array<int, string> $lines
     *
     * @return array<string, string>
     */
    private function parseMetaLines(array $lines): array
    {
        $metaData = [];
        foreach ($lines as $line) {
            if (preg_match('/^(.*)(=|:)("|\')?(.*)("|\')?$/i', $line, $matches)) {
                $metaData[mb_strtolower(trim($matches[1]))] = trim($matches[4]);
            }
        }
        return $metaData;
    }
}
