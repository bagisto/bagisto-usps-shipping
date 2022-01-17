# Introduction

Bagisto Usps Shipping add-on provides Usps Shipping methods for shipping the product. By using this, you can provide Usps (United States Postal Service) shipping. This shipping service calculates the shipping rate according to the  product weight and the origin address. This allows the customers to choose the USPS shipping method on the checkout page.

It packs in lots of demanding features that allows your business to scale in no time:

- The admin can enable or disable the Usps Shipping method.
- The admin can set the Usps shipping method name that will be shown from the front side.
- The admin can define the allowed methods.
- Dynamic shipping method for freight calculation.
- Tax rate can be calculated based on Usps shipping

## Requirements:

- **Bagisto**: v1.3.3

## Installation :
- Run the following command
```
composer require bagisto/bagisto-usps-shipping
```

- Run these commands below to complete the setup
```
composer dump-autoload
```

```
php artisan migrate
php artisan route:cache
php artisan config:cache
php artisan vendor:publish
```
-> Press 0 and then press enter to publish all assets and configurations.

> now execute the project on your specified domain.
