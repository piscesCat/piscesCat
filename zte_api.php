<?php
function base64_url_encode($input) {
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

function encode_to_hex($input_string) {
    return bin2hex(mb_convert_encoding($input_string, 'UTF-16BE'));
}

function decode_from_hex($hex_string) {
    return mb_convert_encoding(hex2bin($hex_string), 'UTF-8', 'UTF-16BE');
}

function loginToModem($modem_ip, $base64_encoded_password) {
    $login_url = "http://$modem_ip/goform/goform_set_cmd_process";
    $referer = "http://$modem_ip/index.html";
    $post_fields = [
        'isTest' => 'false',
        'goformId' => 'LOGIN',
        'password' => $base64_encoded_password
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

$modem_ip = "192.168.0.1";
$base64_encoded_password = base64_url_encode("admin");

if (loginToModem($modem_ip, $base64_encoded_password)) {
    echo "Login OK\n";

    $sms_list = fetchSmsListFromModem($modem_ip);
    echo "SMS List from modem: \n";
    print_r($sms_list);
} else {
    echo "Login Failed";
}
?>
