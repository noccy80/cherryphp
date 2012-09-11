#!/bin/bash

if [ "$UID" != "0" ]; then
    echo "Run this script as root."
    exit 1
fi

cat <<EOF
This script will install CherryPHP into /opt/cherryphp.
It will also install the cherry tools into ~/bin.

Press enter to continue or Ctrl-C to cancel.
EOF
read
cd bin
export CHERRY_LIB=../
./cherry install-all
./cherry install-tools
