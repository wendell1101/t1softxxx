#!/bin/bash

PROJECT_HOME=$(dirname $0)
cd $PROJECT_HOME
SUBMODULES_SRC="submodules/payment-lib/payment"
SUBMODULES_DST="/home/vagrant/Code/og/submodules/payment-lib/payment"

for POD in $(kubectl get pod |grep $CLIENT_NAME-og | awk '{print $1}')
  do
    echo "copy $SUBMODULES_SRC to $POD:$SUBMODULES_DST"
    kubectl cp $SUBMODULES_SRC $POD:$SUBMODULES_DST
done
