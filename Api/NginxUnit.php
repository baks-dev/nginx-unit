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

use JsonException;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class NginxUnit
{
    protected mixed $result;

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        return json_decode($this->result, true, 512, JSON_THROW_ON_ERROR);
    }

    public function getContent(): string
    {
        return $this->result;
    }

    public function outputConsole(?SymfonyStyle $io = null): void
    {
        $data = $this->toArray();

        foreach($data as $type => $message)
        {
            if($type === 'success')
            {
                $io ? $io->success($message) : dump(sprintf('%s: %s', $type, $message));
                break;
            }

            if($type === 'error')
            {
                $io ? $io->error($message) : dump(sprintf('%s: %s', $type, $message));
                continue;
            }

            $io ? $io->text($message) : dump(sprintf('%s: %s', $type, $message));
        }

    }

}