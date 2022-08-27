# scout-elasticsearch-model


## Requirements

The package has been tested in the following configuration:

* PHP version &gt;=7.1.3, &lt;=7.3
* Laravel Framework version &gt;=5.8, &lt;=6
* Elasticsearch version &gt;=7

## Installation

Use composer to install the package:

```
composer require stone-rain/scout-elasticsearch-model
```

If you are using Laravel version &lt;= 5.4 or [the package discovery](https://laravel.com/docs/5.5/packages#package-discovery)
is disabled, add the following providers in `config/app.php`:

```php
'providers' => [
    Laravel\Scout\ScoutServiceProvider::class,
    ScoutElasticModel\ElasticServiceProvider::class,
]
``` 