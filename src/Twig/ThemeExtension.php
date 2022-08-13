<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\Twig;

use Sweikenb\Mdocs\Service\NavigationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ThemeExtension extends AbstractExtension
{
    public function __construct(
        private readonly NavigationService $navigationService
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_css_path', [$this, 'getThemeCss']),
            new TwigFunction('theme_js_path', [$this, 'getThemeJs']),
        ];
    }

    public function getThemeCss(): string
    {
        return $this->navigationService->getLinkPrefix() . 'theme.css';
    }

    public function getThemeJs(): string
    {
        return $this->navigationService->getLinkPrefix() . 'theme.js';
    }
}
