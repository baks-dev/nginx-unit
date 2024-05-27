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

use BaksDev\Nginx\Unit\Api\ReloadConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


#[AsCommand(
    name: 'baks:nginx-unit:reload-config',
    description: 'Обновляет конфигурацию сервера Unit'
)]
class NginxUnitReloadCommand extends Command
{
    private ReloadConfig $reloadConfig;

    public function __construct(
        ReloadConfig $reloadConfig
    )
    {
        parent::__construct();
        $this->reloadConfig = $reloadConfig;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** Сбрасываем весь кеш */
        $io->warning('На сброс кеша файла конфигурации Unit может потребоваться некоторое время!');

        $command = ($this->getApplication())->get('baks:cache:clear');
        $command->run($input, new NullOutput());

        /** Обновляем конфигурацию сервера Unit */

        $this->reloadConfig->reload()->outputConsole($io);

        return Command::SUCCESS;
    }
}
