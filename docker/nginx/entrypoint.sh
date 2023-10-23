#!/usr/bin/env bash

if [ "true" == "${XDEBUG_ENABLED}" ]; then
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini
else
    rm -rf /usr/local/etc/php/conf.d/xdebug.ini
fi

docker-php-entrypoint $@
