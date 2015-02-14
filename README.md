# Order System
This is a small order platform with a cookie based registration system.

## Requirements
 * PHP 5.5
 * composer
 * php-mcrypt

## Usage
open a new terminal window and navigate into this folder

> cd /some/path/projectX  
> make

ATTENTION: change relative path in config/propel.yaml

## TODO
 * frontend: flash messages
 * backend: flash messages
 * general: sqlite absolute path
 * templates in src and static files in public


## Docker

build

> docker build -t -i shop .

docker run -d -p 80:80 shop
