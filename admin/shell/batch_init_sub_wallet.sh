#!/bin/bash

echo "start `date`"

myarr=$1

echo $myar

PHP_PATH=$(if [ -f /usr/bin/php5.6 ]; then echo /usr/bin/php5.6; elif [ -f /usr/bin/php5 ]; then echo /usr/bin/php5; else echo /usr/bin/php; fi)

SHELL_DIR="$(dirname $(readlink -f $0))"
OG_BASEPATH="$SHELL_DIR/.."
echo "SHELL_DIR=$SHELL_DIR"
echo "OG_BASEPATH=$OG_BASEPATH"

for i in $myarr ; do {
	echo "$PHP_PATH $OG_BASEPATH/shell/ci_cli.php cli/command/init_sub_wallet $i \"$2\" \"$3\""
	$PHP_PATH $OG_BASEPATH/shell/ci_cli.php cli/command/init_sub_wallet $i "$2" "$3" 

} & done
wait

echo "done `date`" 
