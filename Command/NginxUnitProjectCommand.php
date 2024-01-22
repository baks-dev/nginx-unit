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
use Random\Randomizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'baks:nginx-unit:project',
    description: 'Добавляет новую директорию проекта с символической ссылкой vendor',
    aliases: ['baks:unit:cert']
)]
class NginxUnitProjectCommand extends Command
{

    private ?string $domain;

    private InputInterface $input;

    private OutputInterface $output;

    private ParameterBagInterface $parameter;

    private string $project_dir;

    private string $new_project_dir;

    private Filesystem $filesystem;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $project_dir,
        ParameterBagInterface $parameter,
    )
    {
        parent::__construct();
        $this->parameter = $parameter;
        $this->project_dir = $project_dir;

        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'domain',
            InputArgument::OPTIONAL,
            'Основное доменное имя проекта'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $io = new SymfonyStyle($input, $output);

        $data = $this->parameter->get('baks.nginx.unit');

        /** Присваиваем доменное имя */
        $this->domain = $input->getArgument('domain') ?: $this->domainQuestion();

        if(!$this->domain)
        {
            $io->warning('Необходимо указать доменное имя');
            return Command::INVALID;
        }

        /** Требуем подтверждение */
        $isInstall = $this->confirmationQuestion();

        if(!$isInstall)
        {
            return Command::SUCCESS;
        }


        $this->new_project_dir = sprintf('%s/%s', $data['path'], $this->domain);


        if($this->filesystem->exists($this->new_project_dir))
        {
            $io->error(sprintf('Директория проекта %s уже существует', $this->domain));
            return Command::SUCCESS;
        }

        $this->filesystem->mkdir($this->new_project_dir, 0755);


        $this->filesystem->mkdir($this->new_project_dir.'/bin', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/src', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/public', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/templates', 0755);
        $this->filesystem->mkdir($this->new_project_dir.'/translations', 0755);
        $this->filesystem->mkdir($this->new_project_dir.'/migrations', 0755);

        $this->filesystem->mkdir($this->new_project_dir.'/var/cache', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/var/log', 755);

        $this->filesystem->mkdir($this->new_project_dir.'/config/packages/dev', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/config/packages/prod', 755);

        $this->filesystem->mkdir($this->new_project_dir.'/config/routes/dev', 755);
        $this->filesystem->mkdir($this->new_project_dir.'/config/routes/prod', 755);

        // Создаем cимволическую ссылку на vendor
        //$filesystem->symlink($this->project_dir.'/vendor', $this->new_project_dir.'/vendor', true);
        $this->symlink('/composer.json');
        $this->symlink('/composer.lock');


        // копируем файлы

        $this->copy('/src/Kernel.php');
        $this->copy('/public/index.php');
        $this->copy('/public/robots.txt');

        $this->copy('/config/preload.php');
        $this->copy('/config/services.php');

        $this->copy('/config/packages/doctrine.php');
        $this->copy('/config/packages/new.php');
        $this->copy('/config/packages/doctrine_migrations.php');
        $this->copy('/config/packages/framework.php');
        $this->copy('/config/packages/http_client.php');
        $this->copy('/config/packages/monolog.php');
        $this->copy('/config/packages/routing.php');
        $this->copy('/config/packages/session.php');
        $this->copy('/config/packages/translation.php');
        $this->copy('/config/packages/twig.php');
        $this->copy('/config/packages/validator.php');

        // копируем директории
        $this->mirror('/bin');
        $this->mirror('/config/routes/dev');
        $this->mirror('/config/packages/dev');


        // создаем новые файлы проекта
        $this->filesystem->dumpFile($this->new_project_dir.'/.env', $this->fileEnvironment());
        $this->filesystem->dumpFile($this->new_project_dir.'/config/bundles.php', $this->fileBundles());
        $this->filesystem->dumpFile($this->new_project_dir.'/.gitignore', $this->fileGitIgnore());

        $io->success(sprintf('Директория проекта %s успешно создана', $this->domain));

        return Command::SUCCESS;
    }

    public function domainQuestion(): ?string
    {
        $helper = $this->getHelper('question');
        $question = new Question('Основное доменное имя проекта: ', null);

        return $helper->ask($this->input, $this->output, $question);
    }


    private function confirmationQuestion(): bool
    {
        $question = new ChoiceQuestion(
            sprintf('Вы уверены что хотите создать проект %s?', $this->domain),
            ['No', 'Yes'],
            0
        );

        $helper = $this->getHelper('question');

        return $helper->ask($this->input, $this->output, $question) === 'Yes';
    }


    private function fileEnvironment(): string
    {
        $r = new Randomizer();
        $secret = bin2hex($r->getBytes(8));

        $content = '###> symfony/framework-bundle ###'.PHP_EOL;
        $content .= 'APP_ENV=dev'.PHP_EOL;
        $content .= sprintf('APP_SECRET=%s', md5($secret)).PHP_EOL;
        $content .= '###< symfony/framework-bundle ###'.PHP_EOL;

        return $content;
    }

    private function fileGitIgnore(): string
    {
        $content = '###> symfony/framework-bundle ###'.PHP_EOL;
        $content .= '/.env.local'.PHP_EOL;
        $content .= '/.env.local.php'.PHP_EOL;
        $content .= '/.env.*.local'.PHP_EOL;
        $content .= '/config/secrets/prod/prod.decrypt.private.php'.PHP_EOL;
        $content .= '/public/bundles/'.PHP_EOL;
        $content .= '/var/'.PHP_EOL;
        $content .= '/vendor/'.PHP_EOL;
        $content .= '###< symfony/framework-bundle ###'.PHP_EOL;

        return $content;
    }

    /**
     * Копирует файл.
     * Если целевой файл старше исходного файла, он всегда перезаписывается.
     * Если целевой файл более новый, он перезаписывается только в том случае, если для параметра $overwriteNewerFiles установлено значение true.
     */
    private function copy(string $file): void
    {
        if(file_exists($this->project_dir.$file))
        {
            $this->filesystem->copy($this->project_dir.$file, $this->new_project_dir.$file);
        }
    }

    /**
     * Создает символическую ссылку или копирует каталог.
     */
    private function symlink(string $dir): void
    {
        if(is_dir($this->project_dir.$dir))
        {
            $this->filesystem->symlink($this->project_dir.$dir, $this->new_project_dir.$dir, true);
        }
    }

    /**
     * Зеркально отображает каталог в другой.
     * Копирует файлы и каталоги из исходного каталога в целевой каталог. По умолчанию:
     * существующие файлы в целевом каталоге будут перезаписаны, за исключением случаев, когда они более новые (см. опцию override )
     * файлы в целевом каталоге, которых нет в исходном каталоге, не будут удалены (см. опцию delete )
     */
    private function mirror(string $dir): void
    {
        if(is_dir($this->project_dir.$dir))
        {
            $this->filesystem->mirror($this->project_dir.$dir, $this->new_project_dir.$dir);
        }
    }

    public function fileBundles(): string
    {
        $content = '<?php'.PHP_EOL;
        $content .= PHP_EOL;
        $content .= 'return ['.PHP_EOL;
        $content .= "\tSymfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],".PHP_EOL;
        $content .= '];'.PHP_EOL;

        return $content;
    }
}
