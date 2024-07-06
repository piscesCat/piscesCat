#!/bin/bash

MODEM_IP="192.168.0.1"
PASSWORD="admin"

LOGIN_RESPONSE=$(curl -s --header "Referer: http://$MODEM_IP/index.html" -d "isTest=false&goformId=LOGIN&password=$PASSWORD" http://$MODEM_IP/goform/goform_set_cmd_process)

if [[ $LOGIN_RESPONSE == *'"result":"0"'* ]]; then
    echo "Login OK"
    REBOOT_RESPONSE=$(curl -s "http://$MODEM_IP/goform/goform_set_cmd_process" \
    -X POST \
    -H "Referer: http://$MODEM_IP/index.html" \
    -d "isTest=false&goformId=REBOOT_DEVICE")
    
    if [[ $REBOOT_RESPONSE == *'"result":"success"'* ]]; then
        echo "Reboot OK"
    else
        echo "Reboot FAILED"
    fi
else
    echo "Login FAILED"
fi
