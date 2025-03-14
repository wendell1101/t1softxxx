#!/bin/bash

target_path=submodules/game-lib/models/game_description/json_data

for target in $(ls $target_path/*.json)
  do
    cat $target | jq . 1>/dev/null 2>/tmp/error_json.txt
    if [[ -s /tmp/error_json.txt ]]; then
        echo $target format error
        cat /tmp/error_json.txt
        rm -f /tmp/error_json.txt
        exit 1
    fi
done

