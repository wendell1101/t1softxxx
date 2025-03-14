#!/bin/bash

if [ -d "og_config" ]; then
	cd og_config
	git pull
else
	git clone git@git.smartbackend.com:sbs/og_config.git
fi
