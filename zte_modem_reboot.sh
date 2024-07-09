#!/bin/bash

MODEM_IP="192.168.0.1"
PASSWORD="YWRtaW4%3D"

LOGIN_RESPONSE=$(curl -s --header "Referer: http://$MODEM_IP/index.html" -d "isTest=false&goformId=LOGIN&password=$PASSWORD" http://$MODEM_IP/goform/goform_set_cmd_process)

if [[ $LOGIN_RESPONSE == *'"result":"0"'* ]]; then
    echo "Login OK"
    
    RESPONSE=$(curl -s -H "Referer: http://$MODEM_IP/index.html" "http://$MODEM_IP/goform/goform_get_cmd_process?isTest=false&cmd=Device_Connected_Time,msisdn")
    
    DEVICE_CONNECTED_TIME=$(echo $RESPONSE | grep -o '"Device_Connected_Time":"[0-9]*"' | grep -o '[0-9]*')
    MSISDN=$(echo $RESPONSE | grep -o '"msisdn":"[^"]*"' | cut -d':' -f2 | tr -d '"')

    if [[ $DEVICE_CONNECTED_TIME -ge 120 && $MSISDN != "" ]]; then
        echo "Rebooting..."
        
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
        echo "No reboot needed."
    fi
else
    echo "Login FAILED"
fi
