#!/bin/bash

set -xe

composer_update () {
    COMPOSER_ARGS=$1 ;

    composer update ${COMPOSER_ARGS} ;
    composer show ;
}

phpunit () {
    COVERAGE=$1

    if [[ ${COVERAGE} == '1' ]] ; then
        echo "zend_extension=xdebug.so" > /etc/php7/conf.d/xdebug.ini ;
        export PHPUNIT_ARGS='--coverage-text --coverage-html coverage' ;
    fi ;

    if [ ! -f phpunit.phar ]; then
            curl -o phpunit.phar -L https://phar.phpunit.de/phpunit-7.phar ;
            chmod +x phpunit.phar ;
    fi ;

    ./phpunit.phar --version ;

    ./phpunit.phar --configuration phpunit.xml.dist --colors=never ${PHPUNIT_ARGS}
}

phpmd () {
    if [ ! -f phpmd ]; then
        curl -o phpmd -L http://static.phpmd.org/php/latest/phpmd.phar ;
        chmod +x phpmd ;
    fi ;

    ./phpmd src text ruleset.xml --reportfile-html phpmd.html ;
}

case $1 in
    composer_update_prefer_latest) composer_update ;;
    composer_update_prefer_lowest) composer_update '--prefer-lowest' ;;
    phpunit) phpunit 0 ;;
    phpunit_with_coverage) phpunit 1 ;;
    phpmd) phpmd ;;
esac
