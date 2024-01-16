<?php
class keyApiClient {
    public $udid;
    private $secretKey;
    private $apiBaseUrl;
    private $dataCryptExpireTime;
    
    public function __construct() {
        $this->apiBaseUrl = 'https://khaiphan.vercel.app/api-v1';
        $this->dataCryptExpireTime = time() + 15; // 15 seconds
    }
    
    public function getUdid() {
        return $this->udid;
    }
    
    public function setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
    }
    
    public function onSuccess($callback) {
        return $this->execute($callback);
    }
    
    private function getVendorIdentifier() {
        //NSString *vendorID = [[[UIDevice currentDevice] identifierForVendor] UUIDString];
        return 'test';
    }
    
    private function execute($callback) {
        $this->checkUdid($callback);
    }
    
    private function checkUdid($callback) {
        $apiData = $this->apiRequest('/check_udid', [
            'device_vendor_id' => $this->getVendorIdentifier(),
        ]);
        
        if ($apiData['status'] === 'success') {
            $this->udid = $apiData['udid'];
            return $callback();
        } else {
            return $this->requestUdid();
        }
    }
    
    private function requestUdid() {
        $apiData = this->apiRequest('/request_udid', [
            'device_vendor_id' => $this->getVendorIdentifier(),
        ]);
        
        if ($apiData['status'] === 'success') {
            return $apiData['mobile_config_url'];
        }
    }
    
    private function generateCryptKey($expireTime = null)
    {
        if ($expireTime === null) {
            $expireTime = $this->dataCryptExpireTime;
        }
        return md5($this->secretKey . $expireTime);
    }

    public function dataEncrypt($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $key = $this->generateCryptKey();
        $plainTextBytes = $this->utf8Encode($data);
        $keyBytes = $this->utf8Encode($key);
        $encryptedBytes = array();

        for ($i = 0; $i < strlen($plainTextBytes); $i++) {
            $encryptedBytes[] = ord($plainTextBytes[$i]) ^ ord($keyBytes[$i % strlen($keyBytes)]);
        }

        $encryptedData = base64_encode(implode(array_map('chr', $encryptedBytes)));
        return array(
            'data' => $encryptedData,
            'expires_time' => $this->dataCryptExpireTime,
        );
    }

    public function dataDecrypt($encryptedData, $expiresTime)
    {
        $key = $this->generateCryptKey($expiresTime);
        $encryptedText = $encryptedData;
        $encryptedBytes = array_map('ord', str_split(base64_decode($encryptedText)));
        $keyBytes = $this->utf8Encode($key);
        $decryptedBytes = array();

        for ($i = 0; $i < count($encryptedBytes); $i++) {
            $decryptedBytes[] = chr($encryptedBytes[$i] ^ ord($keyBytes[$i % strlen($keyBytes)]));
        }

        if (null !== ($decryptedData = json_decode($_decryptedData = implode($decryptedBytes), true))) {
            return $decryptedData;
        } else {
            return $_decryptedData;
        }
    }

    private function utf8Encode($string)
    {
        return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, 'UTF-8, ISO-8859-1', true));
    }
    
    private apiRequest($apiPath, $postData = array()) {
        $curl = curl_init($this->apiBaseUrl . $apiPath);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $encryptedData = $this->dataEncrypt($postData);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $encryptedData);
        $response = curl_exec($curl);
        curl_close($curl);
        $arrayResp = json_decode($response, true);
        $dataDecrypted = $this->dataDecrypt($arrayResp['data'], $arrayResp['expires_time']);
        
        return $dataDecrypted;
    }
}