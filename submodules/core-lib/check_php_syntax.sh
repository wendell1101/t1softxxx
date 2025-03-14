#!/usr/bin/env bash

# just test

pwd
find_php_syntax_error=`find -name "*.php" -type f | egrep -v "/vendor/" | awk '{print "php -l "$1}' | bash | grep -v 'No syntax errors detected'`

echo $find_php_syntax_error

if [ ! -z "$find_php_syntax_error" ]; then
  echo "php syntax error" >&2
  exit 1
fi

echo find duplicate funcId, funcCode in permissions.json

dup_funcId=`cat ./application/config/permissions.json | grep funcId | sort -n | uniq -c | awk '$1 > 1 {print $3}'`
dup_funcCode=`cat ./application/config/permissions.json | grep funcCode | sort -n | uniq -c | awk '$1 > 1 {print $3}'`

if [[ ! -z "$dup_funcId" ]]; then
  echo "find duplicate funcId $dup_funcId" >&2
  exit 1
elif [ ! -z "$dup_funcCode" ]; then
  echo "find duplicate funcCode $dup_funcCode" >&2
  exit 1
fi

echo check external_system_list.xml format

xml_error=`xmllint --noout ./application/config/external_system_list.xml`

if [ ! -z "$xml_error" ]; then
  echo "$xml_error" >&2
  exit 1
fi
