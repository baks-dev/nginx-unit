# BaksDev Nginx Unit

[![Version](https://img.shields.io/badge/version-7.1.5-blue)](https://github.com/baks-dev/nginx-unit/releases)
![php 8.3+](https://img.shields.io/badge/php-min%208.3-red.svg)

Модуль управления сервером Nginx Unit

## Установка

``` bash
$ composer require baks-dev/nginx-unit
```

## Важно!

При первой установке и запуске Certbot необходимо согласие на условие использования. Запускаем и соглашаемся в консоли:

certbot certonly --force-renewal --webroot -w /.....PATH_TO_PROJECT..../public/ --email <MY_EMAIL> -d <MY_DOMAIN>

## Дополнительно

Установка конфигурации и файловых ресурсов:

``` bash
$ php bin/console baks:assets:install
```

## Тестирование

``` bash
$ php bin/phpunit --group=nginx-unit
```

## Лицензия ![License](https://img.shields.io/badge/MIT-green)

The MIT License (MIT). Обратитесь к [Файлу лицензии](LICENSE.md) за дополнительной информацией.

