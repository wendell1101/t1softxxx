#!/usr/bin/env bash

docker-compose -f ./docker-compose-tmp.yml \
    exec --workdir=/home/vagrant/Code/og -ti og "$@"