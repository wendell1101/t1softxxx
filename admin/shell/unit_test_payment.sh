#!/bin/bash

#test root user
if [ $EUID -ne 0 ]; then
    echo please run on root
    exit 1
fi

# if [ -z ${OG_BASEPATH+'x'} ]; then
#   export OG_BASEPATH=/home/vagrant/Code/og/admin
# fi

PHP_PATH=$(if [ -f /usr/bin/php5.6 ]; then echo /usr/bin/php5.6; elif [ -f /usr/bin/php5 ]; then echo /usr/bin/php5; elif [ -f /usr/local/bin/php5 ]; then echo /usr/local/bin/php5; else echo /usr/bin/php; fi)

SHELL_DIR="$(dirname $(readlink -f $0))"
OG_BASEPATH="$SHELL_DIR/.."

$PHP_PATH $SHELL_DIR/ci_cli.php "cli/unit_test_runner/payment" "$1" "$2" "$3" "$4" "$5" "$6" "$7"

#chmod logs
# chmod a+w $OG_BASEPATH/application/logs/*
