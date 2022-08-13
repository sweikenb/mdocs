<?php declare(strict_types=1);

namespace Sweikenb\Mdocs\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WidgetRegisterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('mdocs.services.widget')) {
            return;
        }

        $widgetService = $container->getDefinition('mdocs.services.widget');
        foreach (array_keys($container->findTaggedServiceIds('mdocs.widget')) as $id) {
            $widgetService->addMethodCall('register', [new Reference($id)]);
        }
    }
}
