

# integrate your Laravel app with Odoo

---
## ⚠️ **This package currently depends on this [Odoo Reset API](https://apps.odoo.com/apps/modules/13.0/odoo_rest/), so ensure to purchase it, before start the integration**

---
<!--/delete-->
## Installation

You can install the package via Composer:

```bash
composer require aabadawy/laravel-odoo-integration
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Aabadawy\LaravelOdooIntegration\LaravelOdooIntegrationServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [
    'default'   => [
        'token' => env('ODOO_TOKEN'),
        'url' => env('ODOO_URL'),
        'user_id' => '1',
    ]
];
```


## Usage

```php

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/aabadawy/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [:author_name](https://github.com/:author_username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
