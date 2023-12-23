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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'baks:nginx-unit:certificate',
    description: 'Обновляет и присваивает сертификаты доменов',
    aliases: ['baks:unit:cert']
)]
class NginxUnitCertificateCommand extends Command
{
    private ParameterBagInterface $parameter;
    private ReloadConfig $reloadConfig;
    private DeleteCertificate $deleteCertificate;
    private UpdateCertificate $updateCertificate;
    private CertbotWebroot $certbotWebroot;

    public function __construct(
        ParameterBagInterface $parameter,
        CertbotWebroot $certbotWebroot,

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
        $this->certbotWebroot = $certbotWebroot;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $data = $this->parameter->get('baks.nginx.unit');

        $isTls = false;

        foreach($data['listeners'] as $listener)
        {
            if(!empty($listener['tls']))
            {
                $isTls = true;
                break;
            }
        }

        if(!$isTls)
        {
            $io->warning('Не найдено настроек сертификатов');
            return Command::SUCCESS;
        }

        $currentDate = new DateTimeImmutable();
        $filesystem = new Filesystem();

        foreach($data['domains'] as $domain => $headers)
        {

            $path = $data['path'].'/'.$domain.'/';

            /* Создаем директорию letsencrypt */
            $filesystem->mkdir($path.'public/.well-known/acme-challenge');

            /* Путь к сохранению сертификата */
            $cert = $path.$domain.'.pem';

            if(file_exists($cert))
            {
                $modify = $currentDate->setTimestamp(filemtime($cert));
                $lastModify = $modify->add(new DateInterval('P2M20D'));

                if($currentDate < $lastModify)
                {
                    $this->actualMessage($path, $domain, $modify, $io);
                    continue;
                }
            }

            $io->text(sprintf('Обновляем сертификат %s', $domain));

            /* Обновляем сертификат Let's Encrypt */
            $webroot = $this->certbotWebroot
                ->setPath($path)
                ->domain($domain)
                ->hande($io);

            if($webroot->isSuccessful())
            {
                /* Сохраняем файл сертификата */
                $this->certbotWebroot->saveCertificate();

                /* Удаляем старый сертификат из настроек Unit */
                $this->deleteCertificate->delete($domain);

                /* Присваиваем новый сертификат настройкам Unit */
                $this->updateCertificate->update($cert, $domain)->outputConsole($io);

            }
        }


        foreach($data['domains'] as $domain => $headers)
        {
            $path = $data['path'].'/'.$domain.'/';

            foreach($headers['subdomains'] as $subdomain)
            {
                $cert = $path.$subdomain.'.pem';

                if(file_exists($cert))
                {
                    $modify = $currentDate->setTimestamp(filemtime($cert));
                    $lastModify = $modify->add(new DateInterval('P2M20D'));

                    if($currentDate < $lastModify)
                    {
                        $this->actualMessage($path, $subdomain, $modify, $io);
                        continue;
                    }
                }

                $io->text(sprintf('Обновляем сертификат %s', $subdomain));

                //                dump($subdomain);
                //                dd($path.'public');

                /* Обновляем сертификат Let's Encrypt */
                $webroot = $this->certbotWebroot
                    ->setPath($path)
                    ->domain($subdomain)
                    ->hande($io);

                if($webroot->isSuccessful())
                {
                    /* Сохраняем файл сертификата */
                    $this->certbotWebroot->saveCertificate();

                    /* Удаляем старый сертификат из настроек Unit */
                    $this->deleteCertificate->delete($subdomain);

                    /* Присваиваем новый сертификат настройкам Unit */
                    $this->updateCertificate->update($cert, $subdomain)->outputConsole($io);
                }
            }

        }

        $this->reloadConfig->reload()->outputConsole($io);

        return Command::SUCCESS;
    }


    private function actualMessage(string $path, string $domain, DateTimeImmutable $modify, SymfonyStyle $io): void
    {
        $cert = $path.$domain.'.pem';

        $fullChain = sprintf('/etc/letsencrypt/live/%s/fullchain.pem', $domain);
        $privateKey = sprintf('/etc/letsencrypt/live/%s/privkey.pem', $domain);

        $commandCertbot = sprintf('certbot certonly --webroot -w %s -d %s', $path, $domain);
        $commandCat = sprintf('cat %s  %s > %s', $fullChain, $privateKey, $cert);

        $io->warning(
            sprintf('Сертификат %s в актуальном состоянии: %s', $domain, $modify->format('d.m.Y H:i:s'))
        );

        $io->text('Для ручного обновления запустите комманду:');

        $io->text('1. '.$commandCertbot);
        $io->text('2. '.$commandCat);

    }

}
