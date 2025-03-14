#!/bin/bash

PHP_PATH=$(if [ -f /usr/bin/php5.6 ]; then echo /usr/bin/php5.6; else echo /usr/bin/php; fi)

SHELL_DIR="$(dirname $(readlink -f $0))"
OG_BASEPATH="$SHELL_DIR/.."
echo "SHELL_DIR=$SHELL_DIR"
echo "OG_BASEPATH=$OG_BASEPATH"

if [ -z "$1" ]; then
    echo "usage: unittest_noroot.sh <function name> [parameters]"
    exit 1
fi

echo "start $1 `date`"

start_time=$(date "+%s")
echo $PHP_PATH $SHELL_DIR/ci_cli.php "unittest/unit_test_cli/$1" "$2" "$3" "$4" "$5" "$6" "$7" "$8" "$9"

$PHP_PATH $SHELL_DIR/ci_cli.php "unittest/unit_test_cli/$1" "$2" "$3" "$4" "$5" "$6" "$7" "$8" "$9"
end_time=$(date "+%s")

#chmod logs
# chmod a+w $OG_BASEPATH/application/logs/*

echo "Total run time: $(expr $end_time - $start_time) (s)"
echo "done $1 `date`"
