#!/bin/bash

composer selfupdate --quiet
composer install -o --no-progress --prefer-dist
vendor/bin/phpunit tests
