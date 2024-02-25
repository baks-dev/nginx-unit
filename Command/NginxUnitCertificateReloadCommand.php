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

use BaksDev\Nginx\Unit\Api\DeleteCertificate;
use BaksDev\Nginx\Unit\Api\ReloadConfig;
use BaksDev\Nginx\Unit\Api\UpdateCertificate;
use BaksDev\Nginx\Unit\Process\CertbotWebroot;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'baks:nginx-unit:cert:reload',
    description: 'Перезаписывает существующие сертификаты доменов настройкам Nginx Unit',
)]
class NginxUnitCertificateReloadCommand extends Command
{
    private ParameterBagInterface $parameter;
    private ReloadConfig $reloadConfig;
    private DeleteCertificate $deleteCertificate;
    private UpdateCertificate $updateCertificate;

    public function __construct(
        ParameterBagInterface $parameter,
        ReloadConfig $reloadConfig,
        DeleteCertificate $deleteCertificate,
        UpdateCertificate $updateCertificate
    )
    {
        parent::__construct();

        $this->parameter = $parameter;
        $this->reloadConfig = $reloadConfig;
        $this->deleteCertificate = $deleteCertificate;
        $this->updateCertificate = $updateCertificate;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $data = $this->parameter->get('baks.nginx.unit');

        if(!isset($data['listeners']))
        {
            $io->warning('Файл конфигурации сервера Unit Listeners не найден');
            return Command::SUCCESS;
        }


        foreach($data['domains'] as $domain => $headers)
        {
            $path = $data['path'].'/'.$domain.'/';

            /* Путь к существующему файлу сертификата  */
            $cert = $path.$domain.'.pem';

            if(!file_exists($cert))
            {
                continue;
            }

            $io->text(sprintf('Переопределяем сертификат %s', $domain));

            /* Удаляем старый сертификат из настроек Unit */
            $this->deleteCertificate->delete($domain);

            /* Присваиваем новый сертификат настройкам Unit */
            $this->updateCertificate->update($cert, $domain)->outputConsole($io);
        }


        foreach($data['domains'] as $domain => $headers)
        {
            $path = $data['path'].'/'.$domain.'/';

            foreach($headers['subdomains'] as $subdomain)
            {

                $cert = $path.$subdomain.'.pem';

                if(!file_exists($cert))
                {
                    continue;
                }

                $io->text(sprintf('Переопределяем сертификат %s', $subdomain));

                /* Удаляем старый сертификат из настроек Unit */
                $this->deleteCertificate->delete($subdomain);

                /* Присваиваем новый сертификат настройкам Unit */
                $this->updateCertificate->update($cert, $subdomain)->outputConsole($io);
            }

        }

        /** Сбрасываем весь кеш */

        $io->warning('На сброс кеша файла конфигурации Unit может потребоваться некоторое время!');

        $command = ($this->getApplication())->get('baks:cache:clear');
        $command->run($input, new NullOutput());

        /** Обновляем конфигурацию сервера Unit */

        $this->reloadConfig->reload()->outputConsole($io);

        return Command::SUCCESS;
    }
}
