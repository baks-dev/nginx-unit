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

namespace BaksDev\Nginx\Unit\Process;

use InvalidArgumentException;
use phpDocumentor\Reflection\Types\This;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class CertbotWebroot
{
    private ?string $domain = null;

    private ?string $path = null;

    private bool $successful = true;


    public function setPath(string $path): self
    {
        $this->domain = null;
        $this->path = $path;
        return $this;
    }

    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }


    /** Метод получает сертификаты  */

    public function hande(?SymfonyStyle $io = null): self
    {
        if(!$this->path || !is_dir($this->path))
        {
            throw new InvalidArgumentException('Необходимо указать корневую директорию домена Webroot');
        }

        if(!$this->domain)
        {
            throw new InvalidArgumentException('Необходимо добавить домены для сертификации');
        }

        $process = new Process(['certbot', 'certonly', '--force-renewal', '--webroot', '-w', $this->path.'public/', '-d', $this->domain]);
        $process->start();

        if(!$io)
        {
            $process->wait();
            return $this;
        }

        $process->wait(function($type, $buffer) use ($io): void {
            if(Process::ERR === $type)
            {
                $io->error($buffer);
            }
            else
            {
                $io->text($buffer);
            }
        });


        $this->successful = $process->isSuccessful();

        return $this;
    }





    /**
     * Сохраняет сертификат. Если не передан путь для сохранения - сохраняет в директорию домена
     */
    public function saveCertificate(): self
    {
        if(empty($this->domain))
        {
            throw new InvalidArgumentException('Необходимо указать домены для сертификации');
        }


        dump(sprintf('Сохраняем сертификат в директорию домена %s', $this->domain));

        $fullChain = sprintf('/etc/letsencrypt/live/%s/fullchain.pem', $this->domain);
        $privateKey = sprintf('/etc/letsencrypt/live/%s/privkey.pem', $this->domain);


        if(!file_exists($fullChain))
        {
            throw new InvalidArgumentException(sprintf('Не найден файл сертификата letsencrypt FULLCHAIN: %s', $fullChain));
        }

        if(!file_exists($privateKey))
        {
            throw new InvalidArgumentException(sprintf('Не найден файл сертификата letsencrypt PRIVKEY: %s', $privateKey));
        }

        $process = new Process(['cat', $fullChain, $privateKey]);
        $process->start();

        $dump = null;

        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $dump .= $data;
            }
        }

        $process->wait();

        if($dump)
        {
            $this->dumpFile($dump);
            dump(sprintf('Сохранили сертификат %s', $this->path.$this->domain));
        }
        else
        {
            dump(sprintf('НЕвозможно сохранить сертификат %s', $this->path.$this->domain));
        }


        return $this;
    }


    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    private function dumpFile(string $dump): void
    {
        $filesystem = new Filesystem();
        $name = $this->path.$this->domain.'.pem';
        $filesystem->dumpFile($name, $dump);
    }

}