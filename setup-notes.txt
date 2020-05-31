# code-augmentor-php
PHP 7 Support for Code Augmentor

## Install Notes (on Windows 10)

   1. `composer install` took so long. Setting SSL endpoint with
`composer config --global repo.packagist composer https://packagist.org`
fixed it up.

   2. `composer dump-autoload` needed when using classmap instead of PSR-4.

   3. `composer require --dev phpunit/phpunit "^7"` to install dependency not meant for library consumers.

   4. `composer dump-autoload -o` to generate optimised autoload files.

