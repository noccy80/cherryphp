#!/bin/bash

if [ "$UID" != "0" ]; then
    echo "Run this script as root."
    exit 1
fi

cd bin
export CHERRY_LIB=../
./cherry install-all
./cherry install-tools
