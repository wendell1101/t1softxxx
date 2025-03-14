#!/usr/bin/env sh

#set HOME
#current file directory
OGHOME=$(dirname $( readlink -f $0 ))

OGADMIN_HOME=$OGHOME/admin

PHP_PATH=$(if [ -f /usr/bin/php5.6 ]; then echo /usr/bin/php5.6; elif [ -f /usr/bin/php5 ]; then echo /usr/bin/php5; elif [ -f /usr/local/bin/php ]; then echo /usr/local/bin/php; else echo php; fi)

echo $PHP_PATH

$PHP_PATH $OGADMIN_HOME/public/index.php cli/migrate
$PHP_PATH $OGADMIN_HOME/public/index.php cli/command/incrementCMSVersion
#$PHP_PATH $OGADMIN_HOME/public/index.php cli/command/clearCache

# cd $OGHOME/api
# php artisan migrate
# cd $OGHOME
