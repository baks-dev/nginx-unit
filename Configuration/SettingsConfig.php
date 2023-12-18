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

final class SettingsConfig
{

    public static function settings(ArrayNodeDefinition|NodeDefinition $definition): void
    {
        $rootNode = $definition;
        $rootArray = $rootNode->children();

        $settingsNode = $rootArray->arrayNode('settings');
        $settingsArray = $settingsNode->children();


        //        $settings = $this->rootNode
        //            ->children()
        //            ->arrayNode('settings')
        //            ->children();

        /**
         * body_read_timeout
         *
         * Максимальное количество секунд для чтения данных из тела запроса клиента.
         * Это интервал между последовательными операциями чтения, а не время чтения всего тела.
         * Если Unit не получает никаких данных от клиента в течение этого интервала, он возвращает ответ 408 «Request Timeout».
         *
         * body_read_timeout default 30.
         */

        $settingsArray
            ->integerNode('body_read_timeout')
            ->info('Максимальное количество секунд для чтения данных из тела запроса клиента.')
            ->defaultValue(30)
            ->min(1)
            ->end();

        /**
         * header_read_timeout
         *
         * Максимальное количество секунд для чтения заголовка запроса клиента.
         * Если Unit не получает весь заголовок от клиента в течение этого интервала, он возвращает ответ 408 «Request Timeout».
         *
         * header_read_timeout default 30.
         */
        $settingsArray
            ->integerNode('header_read_timeout')
            ->info('Максимальное количество секунд для чтения заголовка запроса клиента.')
            ->defaultValue(30)
            ->min(1)
            ->end();


        /**
         * idle_timeout
         *
         * Максимальное количество секунд между запросами в поддерживающем соединении.
         * Если в течение этого интервала не поступает новых запросов, Unit возвращает ответ 408 «Request Timeout» и закрывает соединение.
         *
         * idle_timeout default is 180.
         */
        $settingsArray
            ->integerNode('idle_timeout')
            ->info('Максимальное количество секунд между запросами в поддерживающем соединении.')
            ->defaultValue(180)
            ->min(1)
            ->end();

        /**
         * log_route
         *
         * включает или отключает ведение журнала маршрутизатора.
         *
         * log_route default is false (disabled).
         */
        $settingsArray
            ->booleanNode('log_route')
            ->defaultFalse()
            ->end();


        /**
         * max_body_size
         *
         * Максимальное количество байт в теле запроса клиента.
         * Если размер тела превышает это значение, Unit возвращает ответ 413 «Полезная нагрузка слишком велика» и закрывает соединение.
         *
         * max_body_size default is 8388608 (8 MB).
         */

        /** TODO: Умножаем на $mb */
        $mb = 1048576; // 1MB

        $settingsArray
            ->integerNode('max_body_size')
            ->info('Максимальное количество мегабайт в теле запроса клиента.')
            ->defaultValue(8)
            ->min(1)
            ->end();

        /**
         * send_timeout
         *
         * Максимальное количество секунд для передачи данных в качестве ответа клиенту.
         * Это интервал между последовательными передачами, а не время всего ответа.
         * Если в течение этого интервала клиенту не отправляются данные, Unit закрывает соединение.
         *
         * send_timeout default is 30.
         */

        $settingsArray
            ->integerNode('send_timeout')
            ->defaultValue(30)
            ->min(1)
            ->end();

        /**
         * server_version
         *
         * если установлено значение false, Unit опускает информацию о версии в полях заголовка ответа сервера.
         *
         * true: Server: Unit/1.31.0
         * false: Server: Unit
         *
         */
        $settingsArray
            ->booleanNode('server_version')
            ->defaultFalse()
            ->end();


        $settingsArray->end();
        $rootArray->end();

    }


}