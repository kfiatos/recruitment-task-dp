#!/bin/bash

composer install --no-interaction --prefer-dist --optimize-autoloader  && composer config --no-plugins allow-plugins.infection/extension-installer true