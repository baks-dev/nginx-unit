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
use Symfony\Component\Process\Process;

final class UpdateCertificate extends NginxUnit
{

    /**
     * @param string $cert - полный путь к файлу сертификата
     * @param string $name
     * @return $this
     */
    public function update(string $cert, string $name): self
    {
        if(!file_exists($cert))
        {
            throw new InvalidArgumentException(sprintf('Не найден файл сертификата: %s', $cert));
        }

        $process = Process::fromShellCommandline(sprintf("curl -X PUT --data-binary @%s --unix-socket /var/run/control.unit.sock http://localhost/certificates/%s", $cert, $name));
        $process->setTimeout(10);
        $process->run();

        $this->result = $process->getIterator($process::ITER_SKIP_ERR | $process::ITER_KEEP_OUTPUT)->current();

        return $this;
    }

}