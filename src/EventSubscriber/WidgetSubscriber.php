<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\EventSubscriber;

use Sweikenb\Mdocs\Event\FilePreParseEvent;
use Sweikenb\Mdocs\Service\WidgetPreProcessorService;
use Sweikenb\Mdocs\Service\WidgetService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WidgetSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WidgetService $widgetService,
        private readonly WidgetPreProcessorService $widgetPreProcessorService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FilePreParseEvent::class => [
                ['widgetPreProcessor', 10],
                ['widgetProcessor', 0],
            ],
        ];
    }

    public function widgetPreProcessor(FilePreParseEvent $event): void
    {
        $this->widgetPreProcessorService->execute($event->getFile());
    }

    public function widgetProcessor(FilePreParseEvent $event): void
    {
        $this->widgetService->parseFile($event->getFile());
    }
}
