<?php

declare(strict_types=1);

namespace BaksDev\Nginx\Unit;

use DirectoryIterator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class BaksDevNginxUnitBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder) : void
    {
        $path = __DIR__.'/Resources/config/';
        foreach(new DirectoryIterator($path) as $config)
        {
            if($config->isDot() || $config->isDir())
            {
                continue;
            }
            if($config->isFile() && $config->getExtension() === 'php' && $config->getFilename() !== 'routes.php')
            {
                $container->import($config->getPathname());
            }
        }
    }



    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // load an XML, PHP or Yaml file
        //$containerConfigurator->import('../config/services.xml');

        // you can also add or replace parameters and services
//        $containerConfigurator->parameters()
//            ->set('acme_hello.phrase', $config['phrase'])
//        ;

//        if ($config['scream']) {
//            $containerConfigurator->services()
//                ->get('acme_hello.printer')
//                ->class(ScreamingPrinter::class)
//            ;
//        }
    }
}