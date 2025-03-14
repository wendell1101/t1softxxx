#!/bin/bash
# Purpose: backup database
# Syntax: backup_all.sh <remote user> <remote host name> <local dir> <remote dir> [all|part] [num] [private key]
#      remote user: Login remote's user.
#      remote host name: Backup host name.
#      local dir: Local temporary directory.
#      remote dir: Remote backup's directory.
#      all|part: If all will backup all database, if part will not backup include game_log string tables." >&2
#      num: Retention backup files days default is 5.
#      private key: Login remote use's private key.

SHELL_DIR="$(dirname $(readlink -f $0))"
SLACK_SCRIPT=$SHELL_DIR/send_slack.py
CHANNEL="#deployment"
BOT_NAME="Backupall_$(hostname)"
#test root user
if [ $EUID -ne 0 ]; then
    echo please run on root
    exit 1
fi

if [ -z "$1" ] || [ -z "$2" ] || [ -z "$3" ] || [ -z "$4" ]; then
    echo "Argument Error" >&2
    echo "Syntax: backup_all.sh <remote user> <remote host name> <local dir> <remote dir> [all|part] [num]" >&2
    echo "     remote user: Login remote's user." >&2
    echo "     remote host name: Backup host name." >&2
    echo "     local dir: Local temporary directory." >&2
    echo "     remote dir: Remote backup's directory." >&2
    echo "     all|part: If all will backup all database, if part will not backup include game_log string tables." >&2
    echo "     num: Retention backup files days default is 3." >&2
    exit 1
fi

REMOTE_USER=$1
REMOTE_HOST_NAME=$2
BACKUP_DIR=$3
REMOTE_BACKUP_DIR=$4
if [ -z "$5" ]; then
    BACKUP_FILES="all"
else
    BACKUP_FILES=$5
fi
echo $BACKUP_FILES
if [ -z "$6" ]; then
    VALID_DAYS_COUNT=3
else
    VALID_DAYS_COUNT=$6
fi

function execute_target_cmd () {
    cmd=$1
    if [[ "$REMOTE_HOST_NAME" == '127.0.0.1' || "$REMOTE_HOST_NAME" == 'localhost' ]]; then
        result=$($cmd)
        echo $result
    else
        ssh $REMOTE_USER@$REMOTE_HOST_NAME "$cmd"
    fi
}

OG_BASEPATH="$(readlink -f $SHELL_DIR/..)"
echo "SHELL_DIR=$SHELL_DIR"
echo "OG_BASEPATH=$OG_BASEPATH"
echo "start `date`"

TIMESTAMP=`date +%Y%m%d%H%M%S`

echo $TIMESTAMP
PREFIX_OLD_MYSQL=mysqldb
BACKUP_MYSQL_DIR=${BACKUP_DIR}/${PREFIX_OLD_MYSQL}_$TIMESTAMP

TABLES_FILE="/tmp/tables_$TIMESTAMP"

test -d $BACKUP_MYSQL_DIR && echo "directory $BACKUP_MYSQL_DIR already exits." && exit 1

# backup db
if [ "$BACKUP_FILES" == "part" ]; then
    mysql -u root -e "SELECT CONCAT(table_schema,'.',table_name) FROM information_schema.tables WHERE table_name NOT LIKE '%game_log%'" > $TABLES_FILE
    innobackup_1=$(innobackupex --user=root --tables-file="$TABLES_FILE" $BACKUP_MYSQL_DIR --no-timestamp 2>&1 )
    rm -f $TABLES_FILE
else
    innobackup_1=$(innobackupex --user=root $BACKUP_MYSQL_DIR --no-timestamp 2>&1)
fi

innobackup_2=$(innobackupex --apply-log --export $BACKUP_MYSQL_DIR 2>&1)
mysqldump -u root --no-data og > $BACKUP_MYSQL_DIR/og_schema.sql

# backup config
BACKUP_CONFIG_DIR=${BACKUP_DIR}/backup_config
echo copy to backup config: $BACKUP_CONFIG_DIR
# php.ini
mkdir -p $BACKUP_CONFIG_DIR/php_ini/cli
cp -f /etc/php5/cli/php.ini $BACKUP_CONFIG_DIR/php_ini/cli/
mkdir -p $BACKUP_CONFIG_DIR/php_ini/fpm
cp -f /etc/php5/fpm/php.ini $BACKUP_CONFIG_DIR/php_ini/fpm/
# nginx
mkdir -p $BACKUP_CONFIG_DIR/nginx_sites
cp -r /etc/nginx/sites-available/ $BACKUP_CONFIG_DIR/nginx_sites/
cp -r /etc/nginx/sites-enabled/ $BACKUP_CONFIG_DIR/nginx_sites/
# mysql
mkdir -p $BACKUP_CONFIG_DIR/mysql_cnf
cp -f /etc/mysql/my.cnf $BACKUP_CONFIG_DIR/mysql_cnf/
# .netrc
mkdir -p $BACKUP_CONFIG_DIR/ftp_info
cp -f /root/.netrc $BACKUP_CONFIG_DIR/ftp_info/root_.netrc

# backup files
# compress backup db files
cd $(dirname $BACKUP_MYSQL_DIR)
mysql_data_relative_path=$(basename $BACKUP_MYSQL_DIR)
config_relative_path=$(basename $BACKUP_CONFIG_DIR)

echo "end `date`"
check_1=$(echo $innobackup_1 | grep "completed OK" | wc -l)
check_2=$(echo $innobackup_2 | grep "completed OK" | wc -l)

if [ "$check_1" == "0" ] && [ "$check_2" == "0" ]; then
    $SLACK_SCRIPT $CHANNEL "$BOT_NAME" "Backup failed xtrabackex error"
fi

# Move dir
for i in `seq 3`; do
    if [[ $REMOTE_HOST_NAME == 'localhost' || $REMOTE_HOST_NAME == '127.0.0.1' ]]; then
        if [ "$(dirname $(readlink -f $BACKUP_MYSQL_DIR))" == "$(readlink -f $REMOTE_BACKUP_DIR)" ]; then
            break
        fi
        echo mv $BACKUP_MYSQL_DIR $REMOTE_BACKUP_DIR
        mv $BACKUP_MYSQL_DIR $REMOTE_BACKUP_DIR && break
    else
        if [ -z "$PRIVATE_KEY" ] || [ ! -f "$PRIVATE_KEY" ]; then
            echo "scp $BACKUP_MYSQL_DIR $REMOTE_USER@$REMOTE_HOST_NAME:$REMOTE_BACKUP_DIR"
            scp $BACKUP_MYSQL_DIR $REMOTE_USER@$REMOTE_HOST_NAME:$REMOTE_BACKUP_DIR && rm -f $BACKUP_MYSQL_DIR && break
        else
            echo "scp -i $PRIVATE_KEY $BACKUP_MYSQL_DIR $REMOTE_USER@$REMOTE_HOST_NAME:$REMOTE_BACKUP_DIR"
            scp -i $PRIVATE_KEY $BACKUP_MYSQL_DIR $REMOTE_USER@$REMOTE_HOST_NAME:$REMOTE_BACKUP_DIR && rm -f $BACKUP_MYSQL_DIR && break
        fi
    fi
done
# Clean expired backup files
echo "Clean all expired data"
valid_days=$(for i in `seq 0 $(expr $VALID_DAYS_COUNT - 1)`;do date -d "$i days ago" +%Y%m%d; done)
# Check all backup files
for backup_file in $(execute_target_cmd "ls $REMOTE_BACKUP_DIR"); do
    if [ -z "$(echo $backup_file | grep ^${PREFIX_OLD_MYSQL}_)" ]; then
        continue
    fi
    remove_flag='Y'
    backup_file_date=${backup_file#${PREFIX_OLD_MYSQL}_}
    for valid_day in $valid_days; do
        if [[ "$backup_file_date" =~ ^${valid_day}.* ]]; then
            remove_flag=''
            break
        fi
    done
    if [ $remove_flag ]; then
        if [[ "${REMOTE_BACKUP_DIR}/${backup_file}" != "" && "${REMOTE_BACKUP_DIR}/${backup_file}" != '/' ]]; then
            echo "remote: rm -f ${REMOTE_BACKUP_DIR}/${backup_file}"
            execute_target_cmd "rm -rf ${REMOTE_BACKUP_DIR}/${backup_file}"
        fi
    fi
done
