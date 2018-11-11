# Szamlazz.hu

This package is a szamlazz.hu service provider and facade for Laravel


## Installing

You have to use Composer to install the library

```
composer require ferencbalogh/szamlazz
```


## Config

```
php artisan vendor:publish --provider="Billingo\API\Laravel\BillingoServiceProvider" --tag=config
```

This command will generate a `szamlazz.php` file inside your config directory (usually `config/`). Enter your API creditentials here.

Or you can use enviroment (.env) file and add the following variables.

```php
SZAMLAZZ_USERNAME=
SZAMLAZZ_PASSWORD=
SZAMLAZZ_APIURL=https://www.szamlazz.hu/
SZAMLAZZ_TIMEOUT=30
```

## Usage
```php
$receipt = Szamlazz::createReceipt();
```
