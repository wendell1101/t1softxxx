#!/bin/bash

ssh vagrant@${APISYNC_SERVER} "sudo supervisorctl restart ${CLIENT_NAME}_queue_server_auto:${CLIENT_NAME}_queue_server_auto_000 ${CLIENT_NAME}_queue_server_event:${CLIENT_NAME}_queue_server_event_000 ${CLIENT_NAME}_queue_server_remote:${CLIENT_NAME}_queue_server_remote_000"

sleep 3

ssh vagrant@${APISYNC_SERVER} "sudo supervisorctl status | grep ${CLIENT_NAME}_queue_server"
