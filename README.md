# Order System
This is a small order platform with a session based registration system.

## Requirements
 * PHP 5.5
 * composer
 * php-mcrypt

## Usage
open a new terminal window and navigate into this folder

> cd /some/path/projectX

load all libraries used by this project
(therefore you need a composer.phar installation).

To install composer just run:

> curl -sS https://getcomposer.org/installer | php

Then you can load all dependencies:

> php composer.phar install

## TODO
 * makefile load php composer
 * frontend: userinfo header (logout...)
 * backend: <24h order show
 * frontend: flash messages
 * backend: flash messages
 * general: sqlite absolute path

## Developer Info

### Propel ORM

generate sql (propel)

> vendor/bin/propel sql:build
> vendor/bin/propel model:build
> vendor/bin/propel config:convert
> vendor/bin/propel sql:insert


vendor/bin/propel config:convert --output-dir=config
vendor/bin/propel model:build --output-dir=domain
vendor/bin/propel sql:build --output-dir=sql
vendor/bin/propel sql:insert --sql-dir=sql
