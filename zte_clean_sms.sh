#!/usr/bin/env php-cli
<?php
ini_set('memory_limit', '256M');

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

    if ($get_response === false) {
        echo 'CURL error: ' . curl_error($ch);
        return null;
    }

    $get_response = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', $get_response);

    $decoded_response = json_decode($get_response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'JSON decode error: ' . json_last_error_msg();
        return null;
    }

    if (isset($decoded_response['messages'])) {
        foreach ($decoded_response['messages'] as &$message) {
            $encoded_content = $message['content'];
            $decoded_content = decode_from_hex($encoded_content);
            $message['content'] = $decoded_content;
        }
    }

    return $decoded_response;
}

function deleteSmsFromModem($modem_ip, $msg_ids) {
    $url = "http://$modem_ip/goform/goform_set_cmd_process";
    $referer = "http://$modem_ip/index.html";

    $msg_id_string = implode(';', $msg_ids);

    $data = array(
        'isTest' => 'false',
        'goformId' => 'DELETE_SMS',
        'msg_id' => $msg_id_string,
        'notCallback' => 'true'
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Referer: ' . $referer
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    if(curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    }
    curl_close($ch);

    $decoded_response = json_decode($response, true);
    if ($decoded_response && isset($decoded_response['result']) && $decoded_response['result'] === 'success') {
        return true;
    } else {
        return false;
    }
}

$modem_ip = "192.168.0.1";
$password = "admin";

if (loginToModem($modem_ip, $password)) {
    echo "Login OK\n";

    $sms_list = fetchSmsListFromModem($modem_ip);
    print_r($sms_list);
    $del_sms_ids = array();
    foreach($sms_list['messages'] as $sms) {
        $del_sms_ids[] = $sms['id'];
    }
    if(deleteSmsFromModem($modem_ip, $del_sms_ids)) {
        echo "SMS was deleted\n";
    } else {
        echo "Delete SMS failed\n";
    }
} else {
    echo "Login Failed\n";
}
