<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Nginx\Unit\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

final class DomainConfig
{

    public static function domains(ArrayNodeDefinition|NodeDefinition $definition): void
    {
        $rootNode = $definition;

        $rootNode->fixXmlConfig('domain');
        $rootArray = $rootNode->children();

        $domainNode = $rootArray
            ->arrayNode('domains')
            ->useAttributeAsKey('name')
        ;

        $domainPrototype = $domainNode->arrayPrototype();


        $domainPrototype
            ->children()
            ->scalarNode('email')
            ->defaultValue('example@local.ru')
            ->end()
            ->end()
        ;

        //dd(654564);

//        $domainPrototype
//            ->fixXmlConfig('header')
//
//            ->scalarNode('email')
//            ->info('Email для регистрации сертификатов')
//            ->defaultValue('example')
//            ->end();



        $domainPrototype
            ->fixXmlConfig('header')
            ->children()
                ->arrayNode('headers')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        $domainPrototype
            ->fixXmlConfig('subdomain')
            ->children()
            ->arrayNode('subdomains')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;


//        $domainArray
//            ->arrayNode('headers')
//            ->useAttributeAsKey('name')
//            ->arrayPrototype()
//            ->children()
//            ->scalarNode('value')->end()
//            ->end()
//            ->end()
//            ->end();


        //$domainArray->end();
        $domainPrototype->end();
        $rootArray->end();

    }


}