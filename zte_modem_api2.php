#!/usr/bin/env php-cli
<?php
function encode_to_hex($input_string) {
    return bin2hex(mb_convert_encoding($input_string, 'UTF-16BE'));
}

function decode_from_hex($hex_string) {
    return mb_convert_encoding(hex2bin($hex_string), 'UTF-8', 'UTF-16BE');
}

function loginToModem($modem_ip, $password) {
    $login_url = "http://$modem_ip/goform/goform_set_cmd_process";
    $referer = "http://$modem_ip/index.html";
    $post_fields = [
        'isTest' => 'false',
        'goformId' => 'LOGIN',
        'password' => base64_encode($password)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $login_url);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $login_response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($login_response, true);
    if (isset($response_data['result']) && $response_data['result'] === "0") {
        return true;
    } else {
        return false;
    }
}

function fetchSmsListFromModem($modem_ip) {
    $get_url = "http://$modem_ip/goform/goform_get_cmd_process?isTest=false&cmd=sms_data_total&page=0&data_per_page=100&mem_store=1&tags=10&order_by=order+by+id+asc";
    $referer = "http://$modem_ip/index.html";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $get_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data',
        "Referer: $referer"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $get_response = curl_exec($ch);
    curl_close($ch);

    $decoded_response = json_decode($get_response, true);
    if (isset($decoded_response['messages'])) {
        foreach ($decoded_response['messages'] as &$message) {
            $encoded_content = $message['content'];
            $decoded_content = decode_from_hex($encoded_content);
            $message['decoded_content'] = $decoded_content;
        }
    }

    return $decoded_response;
}

function sendSmsFromModem($modem_ip, $phone_number, $message) {
    $url = "http://$modem_ip/goform/goform_set_cmd_process";
    $headers = ["Referer: http://$modem_ip/index.html"];
    
    $encoded_message = encode_to_hex($message);
    $encoded_phone_number = urlencode($phone_number);
    $current_datetime = date("d;m;y;H;i;s;O");
    $encoded_date = urlencode($current_datetime);
    
    $payload = [
        "isTest" => "false",
        "goformId" => "SEND_SMS",
        "notCallback" => "true",
        "Number" => $encoded_phone_number,
        "sms_time" => $encoded_date,
        "MessageBody" => $encoded_message,
        "ID" => "-1",
        "encode_type" => "GSM7_default"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        if (isset($result["result"]) && $result["result"] == "success") {
            return true;
        }
    }

    return false;
}

$modem_ip = "192.168.0.1";
$password = "admin";

if (loginToModem($modem_ip, $password)) {
    echo "Login OK\n";

    $phone_number = "1414";
    $message = "ACTIVE NETWORK";
    $send_result = sendSmsFromModem($modem_ip, $phone_number, $message);
    if ($send_result) {
        echo "SMS sent.\n";
    } else {
        echo "Send SMS failed.\n";
    }

    $sms_list = fetchSmsListFromModem($modem_ip);
    $del_sms = array();
    foreach($sms_list['messages'] as $sms) {
        if ($sms['number'] === '1414')) {
            $del_sms[] = $sms['id'];
        }
    }
    print_r($del_sms);
} else {
    echo "Login Failed";
}
