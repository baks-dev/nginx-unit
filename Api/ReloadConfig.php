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

namespace BaksDev\Nginx\Unit\Api;

use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

final class ReloadConfig extends NginxUnit
{

    private string $project_dir;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $project_dir
    )
    {
        $this->project_dir = $project_dir;
    }

    public function reload(): self
    {
        $config = $this->project_dir.'/unit.json';

        if(!file_exists($config))
        {
            throw new InvalidArgumentException(sprintf('File not found: %s', $config));
        }

        /** применяем конфиг */
        $process = Process::fromShellCommandline('curl -X PUT --data-binary @'.$config.' --unix-socket /var/run/control.unit.sock http://localhost/config/');
        $process->setTimeout(5);
        $process->run();

        $this->result = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT)->current();


        /** логи запросов */
        $process = Process::fromShellCommandline('curl -X PUT "/var/log/unit.log" --unix-socket /var/run/control.unit.sock http://localhost/config/access_log');
        $process->setTimeout(5);
        $process->run();


        return $this;
    }

}