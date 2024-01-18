<?php

declare(strict_types=1);

namespace BaksDev\Nginx\Unit;



use BaksDev\Nginx\Unit\DependencyInjection\BaksDevNginxUnitExtension;
use DirectoryIterator;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

final class BaksDevNginxUnitBundle extends AbstractBundle
{

    public function configure(DefinitionConfigurator $definition): void
    {
        //dump('configure');

        $rootNode = $definition->rootNode();

        $domainPath = $rootNode->children();
        $domainPath->scalarNode('path');
        $domainPath->end();

        Configuration\SettingsConfig::settings($rootNode);

        Configuration\ListenersConfig::listeners($rootNode);

        Configuration\DomainConfig::domains($rootNode);

        Configuration\DanyTypeConfig::dany($rootNode);

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

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder) : void
    {
        $path = __DIR__.'/Resources/config/';

        /** @var DirectoryIterator $config */
        foreach(new DirectoryIterator($path) as $config)
        {
            if($config->isDot() || $config->isDir())
            {
                continue;
            }

            if($config->isFile() && $config->getExtension() === 'php' && $config->getFilename() !== 'routes.php')
            {
                $container->import($config->getRealPath());
            }
        }
    }


    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if(isset($config['settings']))
        {
            /* конвертируем пользовательские мегабайты в байты */
            if(isset($config['settings']['max_body_size']))
            {
                $config['settings']['max_body_size'] *= 1048576;
            }
        }

        $container->parameters()->set('baks.nginx.unit', $config);
    }

}