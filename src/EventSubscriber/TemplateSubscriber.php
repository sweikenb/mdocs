<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\EventSubscriber;

use Sweikenb\Mdocs\Event\FilePostParseEvent;
use Sweikenb\Mdocs\Event\TreePreParseEvent;
use Sweikenb\Mdocs\Exceptions\DocumentLinkRegisterException;
use Sweikenb\Mdocs\Exceptions\NavigationAlreadyParsedException;
use Sweikenb\Mdocs\Service\DocumentLinkerService;
use Sweikenb\Mdocs\Service\DocumentLinkRegisterService;
use Sweikenb\Mdocs\Service\NavigationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

class TemplateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DocumentLinkerService $documentLinkerService,
        private readonly DocumentLinkRegisterService $documentLinkRegisterService,
        private readonly NavigationService $navigationService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TreePreParseEvent::class => [
                ['registerDocuments', 1024],
            ],
            FilePostParseEvent::class => [
                ['pageWrapper', -512],
                ['linkDocuments', -1024],
            ],
        ];
    }

    /**
     * @throws DocumentLinkRegisterException
     * @throws NavigationAlreadyParsedException
     */
    public function registerDocuments(TreePreParseEvent $event): void
    {
        $this->documentLinkRegisterService->execute($event->getTree(), $event->getLinkPrefix());
        $this->navigationService->execute($event->getTree(), $event->getLinkPrefix());
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function pageWrapper(FilePostParseEvent $event): void
    {
        // different template location?
        $prevLoader = null;
        if (!empty($event->getTemplateDir())) {
            $prevLoader = $this->twig->getLoader();
            $this->twig->setLoader(new FilesystemLoader($event->getTemplateDir()));
        }

        // inject globals
        $this->twig->addGlobal('link_prefix', $event->getLinkPrefix());

        // parse file content
        $file = $event->getFile();
        $navNode = $this->navigationService->findNodeForFile($file);
        $file->setContent(
            $this->twig->render(
                'page.html.twig',
                [
                    'file' => $file,
                    'navActive' => $navNode,
                ]
            )
        );

        // restore original loader?
        if ($prevLoader !== null) {
            $this->twig->setLoader($prevLoader);
        }
    }

    public function linkDocuments(FilePostParseEvent $event): void
    {
        $this->documentLinkerService->execute($event->getFile());
    }
}
