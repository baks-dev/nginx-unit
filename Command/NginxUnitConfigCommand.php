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

namespace BaksDev\Nginx\Unit\Command;

use BaksDev\Nginx\Unit\BaksDevNginxUnitBundle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[AsCommand(
    name: 'baks:nginx-unit:config',
    description: 'Сохраняет файл конфигурации согласно настройкам',
    aliases: ['baks:unit:config']
)]
class NginxUnitConfigCommand extends Command
{
    private ParameterBagInterface $parameter;

    private string $project_dir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $project_dir,
        ParameterBagInterface $parameter
    )
    {
        parent::__construct();
        $this->parameter = $parameter;
        $this->project_dir = $project_dir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $data = $this->parameter->get('baks.nginx.unit');

        /** Основные настройки */
        $config["settings"]["http"] = $data['settings'];


        $routes = 0;
        $isHttps = false;

        /** Listeners */
        $config["listeners"] = [];
        foreach($data['listeners'] as $key => $listener)
        {
            $config["listeners"][$key] = ["pass" => "routes"];

            if(!empty($listener['tls']))
            {
                $config["listeners"][$key] = ["pass" => "routes"];

                $isHttps = true;

                $cache = current($listener['tls']);

                $hosts = [];

                foreach($data['domains'] as $domain => $headers)
                {
                    $hosts[] = $domain;

                    foreach($headers['subdomains'] as $subdomain)
                    {
                        $hosts[] = $subdomain;
                    }
                }

                $config["listeners"][$key]['tls'] =
                    [
                        "certificate" => $hosts,
                        "session" => [
                            "cache_size" => $cache['cache_size'],
                            "timeout" => $cache['ttl'],
                            "tickets" => true
                        ],
                    ];
            }
        }

        /** Запрет доступа пользовательских IP */
        if(!empty($data['dany']['ips']))
        {
            $config["routes"][$routes]['match']['source'] = $data['dany']['ips'];
            $config["routes"][$routes]['action']['return'] = 403;
            $routes++;
        }

        /** Запрет доступа при обращении к хосту (домену или ip домена) */
        if(!empty($data['dany']['hosts']))
        { //"scheme": "http"
            $config["routes"][$routes]['match']['host'] = $data['dany']['hosts'];
            $config["routes"][$routes]['action']['return'] = 403;
            $routes++;
        }

        /** Запрет доступа при обращении к определенным типам файлов */
        if(!empty($data['dany']['types']))
        {
            $config["routes"][$routes]['match']['uri'] = array_map(function($item) {
                return '*.'.$item.'*';
            }, $data['dany']['types']);
            $config["routes"][$routes]['action']['return'] = 403;
            $routes++;
        }

        if($isHttps)
        {

            /** Создаем роутинг для верификации Let's Encrypt */
            foreach($data['domains'] as $domain => $headers)
            {
                $hosts = [];
                $hosts[] = $domain;

                foreach($headers['subdomains'] as $subdomain)
                {
                    $hosts[] = $subdomain;
                }

                $config["routes"][$routes]['match']['host'] = $hosts;
                $config["routes"][$routes]['match']['scheme'] = 'http';
                $config["routes"][$routes]['match']['uri'] = '/.well-known/acme-challenge/*';
                $config["routes"][$routes]['action']['share'] = $data['path'].'/'.$domain.'/public/.well-known/acme-challenge/';
                $routes++;
            }


            /** Добавляем редирект на https */
            $config["routes"][$routes]['match']['scheme'] = 'http';
            $config["routes"][$routes]['action']['return'] = 301;
            $config["routes"][$routes]['action']['location'] = 'https://$host';

            $routes++;
        }


        /** Определяем статические ресурсы */
        foreach($data['static'] as $key => $static)
        {
            $config["routes"][$routes]['match']['uri'] = array_map(function($item) {
                return '*.'.$item;
            }, $static['types']);

            $config["routes"][$routes]['action']['share'] = $data['path'].'/$host/public$uri';

            if(!empty($static['headers']))
            {
                $config["routes"][$routes]['action']['response_headers'] = $static['headers'];
            }

            $routes++;
        }


        foreach($data['domains'] as $domain => $headers)
        {
            $hosts = [];
            $hosts[] = $domain;

            foreach($headers['subdomains'] as $subdomain)
            {
                $hosts[] = $subdomain;
            }

            $config["routes"][$routes]['match']['host'] = $hosts;
            $config["routes"][$routes]['action']['pass'] = 'applications/'.$domain;

            if(!empty($headers['headers']))
            {
                $config["routes"][$routes]['action']['response_headers'] = $headers['headers'];
            }

            $config['applications'][$domain] =
                [
                    'type' => 'php',
                    'processes' => 20,
                    'root' => $data['path'].'/'.$domain.'/public/',
                    'script' => "index.php"
                ];

            $routes++;
        }



        $handle = fopen($this->project_dir.'/unit.json', "w");
        fwrite($handle, json_encode($config));
        fclose($handle);

        $io->success('Файл конфигурации сервера Unit успешно обновлен');

        return Command::SUCCESS;
    }

}
