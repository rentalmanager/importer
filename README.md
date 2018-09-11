# Rental Manager - Importer

This package is the main package for downloading, parsing and importing feeds. It's installed as the last
package in the main namespace RentalManager since it uses every model available in the root namespace.

## Installation, Configuration

### Installation

Via Composer

After that just simply add the following line to the composer required packages

``` bash
composer require rentalmanager/importer
```


### Configuration

Once you install the package, it should be automatically discovered by the Laravel. To check this, in your terminal simply run the:

``` bash
$ php artisan
```

There you should find the all `rm-importer:*` commands.


After you have checked that the all commands are there you need a couple of things to do by yourself.

#### Migrate tables

Migrate table using

```bash
$ php artisan migrate
```

#### Laravel log

This package uses default Laravel log package for storing logs, but it uses different channels. 
You should open the file `config/logging.php` and add the following channels in the channel array:


```php
 'importer' => [
            'driver' => 'daily',
            'path' => storage_path('logs/importer.log'),
            'level' => 'debug',
            'days' => 7,
        ]
```

#### Config

Publish the config file `php artisan vendor:publish --tag="importer"`

All info comes later :)

