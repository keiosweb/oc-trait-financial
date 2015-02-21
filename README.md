# Keios/oc-trait-financial

[![Latest Version](https://img.shields.io/github/release/keiosweb/oc-trait-financial.svg?style=flat-square)](https://github.com/keiosweb/oc-trait-financial/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/keios/oc-trait-financial.svg?style=flat-square)](https://packagist.org/packages/keios/oc-trait-financial)

Trait for OctoberCMS' models providing [Keios/MoneyRight](http://github.com/keiosweb/moneyright) value object integration.

## Install

Via Composer

``` bash
$ composer require keios/oc-trait-financial
```

## Usage

``` php
class Account extends Model {           // example model extending October's October\Rain\Database\Model
    use \Keios\Financial\Financial;

    protected $financial = [
        'balance' => [                  // $model->balance instanceof \Keios\MoneyRight\Money // true
            'balance'  => 'amount',     // amount   : decimal(12,4) field in database
            'currency' => 'currency'    // currency : varchar(3) field in database
        ]
    ];
}
```

## Security

If you discover any security related issues, please email lukasz@c-call.eu instead of using the issue tracker.

## Credits

- [Keios Solutions](https://github.com/keiosweb)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
