#!/bin/bash

#test root user
if [ $EUID -ne 0 ]; then
	echo please run on root
	exit 1
fi

# export OG_BASEPATH=/home/vagrant/Code/og/admin
SHELL_DIR="$(dirname $(readlink -f $0))"
OG_BASEPATH="$SHELL_DIR/.."
echo "SHELL_DIR=$SHELL_DIR"
echo "OG_BASEPATH=$OG_BASEPATH"

# mkdir -p /var/game_platform/agin
# chmod a+r /var/game_platform/agin

#edit ~/.netrc
#machine ftp.agingames.com
#
#    login <username>
#
#    password <password>

#chmod 600 ~/.netrc

echo start `date`
# while [ : ]
# do
  # echo run sync ftp


LOCAL_DIR=/var/game_platform/ag

mkdir -p $LOCAL_DIR
chmod 777 $LOCAL_DIR

/usr/local/bin/lftp -e "
set ftp:list-options -a;

set xfer:log 1;

set xfer:log-file /var/log/sync_ag_ftp_transfer.log;

set net:timeout 5;

set net:max-retries 2;

set net:reconnect-interval-base 3;

set mirror:parallel-directories 1;

open xml.agingames.com;

lcd $LOCAL_DIR;

cd /;

mirror --dereference --use-cache --verbose --no-umask --parallel=5 --newer-than='now-3days' --log=/var/log/sync_ag_ftp_mirror.log --exclude-glob .svn;

exit;

"

# done

  # lftp -f $OG_BASEPATH/shell/sync_ag_ftp_commands
  # echo sleep
  # sleep 10
# done

echo end `date`
