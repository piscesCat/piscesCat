<?php
class keyApiClient {
    public $udid;
    public $keyExpireTime;
    public $userDisplayName;
    public $deviceName;
    private $apiToken;
    private $secretKey;
    private $apiBaseUrl;
    private $dataCryptExpireTime;
    
    public function __construct() {
        $this->apiBaseUrl = 'https://udid-php-vercel.vercel.app';
        $this->dataCryptExpireTime = time() + 15; // 15 seconds
        $this->execute();
    }
    
    public function getUdid() {
        return $this->udid;
    }
    
    public function setApiToken($token) {
        $this->apiToken = $token;
    }
    
    public function setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
    }
    
    public function onSuccess($callback) {
        
    }
    
    private function getVendorIdentifier() {
        //NSString *vendorID = [[[UIDevice currentDevice] identifierForVendor] UUIDString];
        return 'test';
    }
    
    private function execute() {
        $this->checkUdid(function () {
            $this->udid;
        });
    }
    
    private function checkUdid($callback) {
        $apiData = this->apiRequest('/check_udid', [
            'device_vendor_id' => $this->getVendorIdentifier(),
        ]);
        
        if ($apiData->status === 'success') {
            return $callback();
        } else {
            return $this->requestUdid();
        }
    }
    
    private function requestUdid() {
        $this->udid = 'UDID_test';
    }
    
    private function generateCryptKey($expireTime = null)
    {
        if ($expireTime === null) {
            $expireTime = $this->dataCryptExpireTime;
        }
        return md5($this->secretKey . $expireTime);
    }

    private function strEncrypt($arrayData)
    {
        if (!is_array($arrayData)) {
            return null;
        }
        $key = $this->generateCryptKey();
        $plainTextBytes = $this->utf8Encode(json_encode($arrayData));
        $keyBytes = $this->utf8Encode($key);
        $encryptedBytes = array();

        for ($i = 0; $i < strlen($plainTextBytes); $i++) {
            $encryptedBytes[] = ord($plainTextBytes[$i]) ^ ord($keyBytes[$i % strlen($keyBytes)]);
        }

        $encryptedString = base64_encode(implode(array_map('chr', $encryptedBytes)));
        return array(
            'data' => $encryptedString,
            'expires_time' => $this->dataCryptExpireTime,
        );
    }

    private function strDecrypt($jsonEncoded)
    {
        $jsonDecoded = json_decode($jsonEncoded);
        if (!isset($jsonDecoded->data) || !isset($jsonDecoded->expires_time)) {
            return null;
        }
        $key = $this->generateCryptKey($jsonDecoded->expires_time);
        $encryptedText = $jsonDecoded->data;
        $encryptedBytes = array_map('ord', str_split(base64_decode($encryptedText)));
        $keyBytes = $this->utf8Encode($key);
        $decryptedBytes = array();

        for ($i = 0; $i < count($encryptedBytes); $i++) {
            $decryptedBytes[] = chr($encryptedBytes[$i] ^ ord($keyBytes[$i % strlen($keyBytes)]));
        }

        if (null !== ($decryptedData = json_decode(implode($decryptedBytes)))) {
            return $decryptedData;
        }
    }

    private function utf8Encode($string)
    {
        return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, 'UTF-8, ISO-8859-1', true));
    }
    
    private apiRequest($apiPath, $postData = array()) {
        if (empty($this->apiToken)) {
            return 'Bắt buộc set API Token';
        }
        $curl = curl_init($this->apiBaseUrl . $apiPath);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ]);
        if (!empty($postData)) {
            $encryptedPostData = strEncrypt($postData);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonData));
        }
        $response = curl_exec($curl);
        if (curl_errno($curl) || !($dataDecrypted === strDecrypt($response)) {
            return 'Xảy ra sự cố với máy chủ!';
        }
        curl_close($curl);
        
        return $dataDecrypted;
    }
}