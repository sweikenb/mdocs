<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Twig;

use Sweikenb\Library\Filesystem\Api\FileInterface;
use Sweikenb\Mdocs\Api\NavigationNodeInterface;
use Sweikenb\Mdocs\Service\NavigationService;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NavigationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NavigationService $navigationService,
        private readonly Environment $twig
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('main_navigation', [$this, 'main'], ['is_safe' => ['html']]),
            new TwigFunction('render_nav_link', [$this, 'navLink'], ['is_safe' => ['html']]),
            new TwigFunction('nav_canonical_tag', [$this, 'navCanonicalTag'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function main(FileInterface $currentFile): string
    {
        $tree = $this->navigationService->getRootNode();
        if ($tree === null) {
            return '';
        }

        return $this->twig->render(
            'navigation/main.html.twig',
            [
                'navTree' => $tree,
                'navActive' => $this->navigationService->findNodeForFile($currentFile),
            ]
        );
    }

    public function navLink(
        NavigationNodeInterface $node,
        ?NavigationNodeInterface $active = null,
        ?string $overwriteLabel = null
    ): string {
        if ($node->isFallbackLink()) {
            return $overwriteLabel ?? $node->getLabel();
        }

        if ($node === $active) {
            return sprintf(
                '<a href="%s" class="active">%s</a>',
                $node->getLink(),
                $overwriteLabel ?? $node->getLabel()
            );
        }

        return sprintf('<a href="%s">%s</a>', $node->getLink(), $overwriteLabel ?? $node->getLabel());
    }

    public function navCanonicalTag(NavigationNodeInterface $node): string
    {
        $canonicalLink = $node->getLink();
        if (preg_match('#^(http|https)://.+#i', $canonicalLink)) {
            return sprintf("<link rel=\"canonical\" href=\"%s\"/>", $canonicalLink);
        }
        return '';
    }
}
