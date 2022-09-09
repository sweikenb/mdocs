<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Service;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Library\Markdown\Service\MarkdownService;

class DocumentLinkerService
{
    const FALLBACK_LINK = '#err';

    public function __construct(
        private readonly MarkdownService $markdownService,
        private readonly DocumentLinkRegisterService $linkRegisterService
    ) {
    }

    public function execute(FileInterface $file): void
    {
        // get file content
        $content = $file->getContent();

        // find links to markdown files in the document
        $pattern = sprintf(
            '/(<a href="(([^"]+)\.(%s)((\?|#)[^"]*)?)">)/i',
            implode('|', $this->markdownService->getFileExtensions())
        );
        if (preg_match_all($pattern, $content, $matches)) {
            // process matching links
            foreach ($matches[1] as $i => $search) {
                // prepare vars for easier usage
                $noExt = $matches[3][$i];
                $linkSuffix = $matches[5][$i];

                // get the parents path as array to be able to follow links "upwards" in the tree
                $parents = explode('/', strval($file->getParentDir()?->getRelPath()));
                while (mb_substr($noExt, 0, 1) === '.') {
                    // same leve, normalize and skipp
                    if (mb_substr($noExt, 0, 2) === './') {
                        $noExt = mb_substr($noExt, 2);
                        continue;
                    }

                    // jump one level up
                    if (mb_substr($noExt, 0, 3) === '../') {
                        $noExt = mb_substr($noExt, 3);

                        // parents left?
                        if (count($parents) > 0) {
                            array_pop($parents);
                        } else {
                            // stop here as we will find no result
                            break;
                        }
                    }
                }

                // create the new relative link to the document
                $relLink = trim(sprintf("%s/%s", implode('/', $parents), $noExt), '/');

                // is this a valid link?
                $linkTo = self::FALLBACK_LINK;
                $targetFile = $this->linkRegisterService->getFileForLink($relLink);
                if ($targetFile) {
                    $linkTo = $this->linkRegisterService->getLink($targetFile, $linkSuffix);
                }

                // replace the link to the markdown-file with the link to the actual html-file (or #err fallback)
                $content = str_replace($search, sprintf('<a href="%s">', $linkTo), $content);
            }
            $file->setContent($content);
        }
    }
}
