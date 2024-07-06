#!/bin/bash

MODEM_IP="192.168.0.1"
PASSWORD="YWRtaW4%3D"

LOGIN_RESPONSE=$(curl -s --header "Referer: http://$MODEM_IP/index.html" -d "isTest=false&goformId=LOGIN&password=$PASSWORD" http://$MODEM_IP/goform/goform_set_cmd_process)

if [[ $LOGIN_RESPONSE == *'"result":"0"'* ]]; then
    echo "Login OK"
    
    NETWORK_PROVIDER_RESPONSE=$(curl -s -H "Referer: http://$MODEM_IP/index.html" "http://$MODEM_IP/goform/goform_get_cmd_process?isTest=false&cmd=network_provider")
    
    if [[ $NETWORK_PROVIDER_RESPONSE != *'"network_provider":""'* ]]; then
        echo "Network provider is not empty. Rebooting..."
        
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
        echo "Network provider is empty. No reboot needed."
    fi
else
    echo "Login FAILED"
fi
