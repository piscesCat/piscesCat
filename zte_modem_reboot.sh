#!/usr/bin/env php-cli
<?php

$MODEM_IP = "192.168.0.1";
$PASSWORD = "admin";

$encoded_password = base64_encode($PASSWORD);

$login_data = array(
    'isTest' => 'false',
    'goformId' => 'LOGIN',
    'password' => $encoded_password
);

$login_response = curl_post("http://$MODEM_IP/goform/goform_set_cmd_process", $login_data);

if ($login_response !== false) {
    $login_result = json_decode($login_response, true);

    if ($login_result && isset($login_result['result']) && $login_result['result'] === '0') {
        $cmd_data = array(
            'isTest' => 'false',
            'cmd' => 'Device_Connected_Time,msisdn'
        );

        $response = curl_get("http://$MODEM_IP/goform/goform_get_cmd_process", $cmd_data);

        if ($response !== false) {
            $response_data = json_decode($response, true);

            if ($response_data && isset($response_data['Device_Connected_Time'], $response_data['msisdn'])) {
                $device_connected_time = (int)$response_data['Device_Connected_Time'];
                $msisdn = $response_data['msisdn'];

                echo "Device_Connected_Time: $device_connected_time\n";
                echo "MSISDN: $msisdn\n";

                if ($device_connected_time >= 120 && $msisdn !== "") {
                    $reboot_data = array(
                        'isTest' => 'false',
                        'goformId' => 'REBOOT_DEVICE'
                    );

                    $reboot_response = curl_post("http://$MODEM_IP/goform/goform_set_cmd_process", $reboot_data);

                    if ($reboot_response !== false) {
                        $reboot_result = json_decode($reboot_response, true);

                        if ($reboot_result && isset($reboot_result['result']) && $reboot_result['result'] === 'success') {
                            echo "Rebooting...\n";
                        } else {
                            echo "Reboot FAILED\n";
                        }
                    } else {
                        echo "Reboot request failed\n";
                    }
                } else {
                    echo "No reboot needed.\n";
                }
            } else {
                echo "Invalid or missing data in response\n";
            }
        } else {
            echo "Failed to retrieve response\n";
        }
    } else {
        echo "Login FAILED\n";
    }
} else {
    echo "Login request failed\n";
}

function curl_post($url, $data) {
    global $MODEM_IP;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_REFERER, "http://$MODEM_IP/index.html");
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function curl_get($url, $data) {
    global $MODEM_IP;
    $url = $url . '?' . http_build_query($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_REFERER, "http://$MODEM_IP/index.html");
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

?>
