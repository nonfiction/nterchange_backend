nterchange
==========

Content Management that gets out of your way

Install
-------

This repository contains the core libraries, intended for use by a front-end.

Testing
-------

Set up the dependencies for the backend if necessary, then run PHPUnit.
This will clear and create a database called `nterchange_test`.

    composer install
    vendor/bin/phpunit -c test/phpunit.xml
