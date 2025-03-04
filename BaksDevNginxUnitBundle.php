<?php

declare(strict_types=1);

namespace BaksDev\Nginx\Unit;

use BaksDev\Nginx\Unit\DependencyInjection\BaksDevNginxUnitExtension;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class BaksDevNginxUnitBundle extends AbstractBundle
{
    public const string NAMESPACE = __NAMESPACE__.'\\';

    public const string PATH = __DIR__.DIRECTORY_SEPARATOR;

    public function configure(DefinitionConfigurator $definition): void
    {

        $rootNode = $definition->rootNode();

        $domainPath = $rootNode->children();
        $domainPath->scalarNode('path');
        $domainPath->end();

        Configuration\SettingsConfig::settings($rootNode);

        Configuration\ListenersConfig::listeners($rootNode);

        Configuration\DomainConfig::domains($rootNode);

        Configuration\DanyConfig::dany($rootNode);

        Configuration\StaticConfig::static($rootNode);


        //        $definition->rootNode()
        //            ->children()
        //            ->arrayNode('twitter')
        //            ->children()
        //            ->integerNode('client_id')->end()
        //            ->scalarNode('client_secret')->end()
        //            ->end()
        //            ->end() // twitter
        //            ->end()
        //        ;
    }

    //    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder) : void
    //    {
    //        $path = __DIR__.'/Resources/config/';
    //
    //        /** @var DirectoryIterator $config */
    //        foreach(new DirectoryIterator($path) as $config)
    //        {
    //            if($config->isDot() || $config->isDir())
    //            {
    //                continue;
    //            }
    //
    //            if($config->isFile() && $config->getExtension() === 'php' && $config->getFilename() !== 'routes.php')
    //            {
    //                $container->import($config->getRealPath());
    //            }
    //        }
    //    }


    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
//        $services = $container->services()
        //            ->defaults()
        //            ->autowire()
        //            ->autoconfigure();
        //
        //        $services->load(self::NAMESPACE, self::PATH)
        //            ->exclude([
        //                self::PATH.'{Entity,Resources,Type}',
        //                self::PATH.'**/*Message.php',
        //                self::PATH.'**/*DTO.php',
        //            ]);

        if(isset($config['settings']))
        {
            /* конвертируем пользовательские мегабайты в байты */
            if(isset($config['settings']['max_body_size']))
            {
                $config['settings']['max_body_size'] *= 1048576;
            }

            //$config['js_module'] = __DIR__.'/Resources/module/csp.js';
        }

        $container->parameters()->set('baks.nginx.unit', $config);
    }

}
