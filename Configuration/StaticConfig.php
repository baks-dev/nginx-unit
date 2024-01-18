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

final class StaticConfig
{

    public static function static(ArrayNodeDefinition|NodeDefinition $definition): void
    {

        $rootNode = $definition;

        //$rootNode->fixXmlConfig('static');
        $rootArray = $rootNode->children();

//        $staticNode = $rootArray
//            ->arrayNode('statics')
//            //->useAttributeAsKey('name')
//        ;

        $staticNode = $rootArray->arrayNode('static');
        //$settingsArray = $staticNode->children();





        $typePrototype = $staticNode->arrayPrototype();

        $typePrototype
            ->fixXmlConfig('type')
            ->children()
            ->arrayNode('types')
                ->scalarPrototype()->end()
            ->end()
            ->end()

        ;


        $typePrototype
            ->children()
            ->scalarNode('domain')
            ->end()
            ->end()
        ;


        $typePrototype
            ->fixXmlConfig('subdomain')
            ->children()
            ->arrayNode('subdomains')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;


        $typePrototype
            ->fixXmlConfig('header')
            ->children()
            ->arrayNode('headers')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;


        $typePrototype->end();


//        $staticPrototype = $typePrototype->arrayPrototype();
//
//        $staticPrototype
//            ->fixXmlConfig('header')
//            ->children()
//            ->arrayNode('headers')
//            ->scalarPrototype()->end()
//            ->end()
//            ->end()
//        ;
//
//
//        $staticPrototype->end();












        $rootArray->end();

    }


}