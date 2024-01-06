<?php
class keyApiClient {
    public $udid;
    public $keyExpireTime;
    public $userDisplayName;
    public $deviceName;
    private $apiToken;
    private $secretKey;
    private $packageId;
    private $dataCryptExpireTime;
    
    public function __construct() {
        $this->dataCryptExpireTime = 15; // 15 seconds
    }
    
    public function getUdid() {
        return $this->udid;
    }
    
    public setApiToken($token) {
        $this->apiToken = $token;
    }
    
    public setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
    }
    
    public setPackageId($packageId) {
        $this->packageId = $packageId;
    }
    
    public onSuccess($callback) {
        if ($this->execute()) {
            return $callback();
        }
    }
    
    private function execute() {
        return true;
    }
    
    private function requestUdid() {
        $this->udid = 'UDID_test';
    }
    
    private generateCryptKey() {
        $expireTime = time() + $this->dataCryptExpireTime;
        return md5($this->secretKey . $expireTime);
    }
    
    private function strEncrypt($plainText) {
        $key = $this->generateCryptKey();
  $plainTextBytes = utf8_encode($plainText);
  $keyBytes = utf8_encode($key);
  $encryptedBytes = array();

  for ($i = 0; $i < strlen($plainTextBytes); $i++) {
    $encryptedBytes[] = ord($plainTextBytes[$i]) ^ ord($keyBytes[$i % strlen($keyBytes)]);
  }

  $encryptedString = base64_encode(implode(array_map('chr', $encryptedBytes)));
  return $encryptedString;
}

private function strDecrypt($encryptedText) {
    $key = $this->generateCryptKey();
  $encryptedBytes = array_map('ord', str_split(base64_decode($encryptedText)));
  $keyBytes = utf8_encode($key);
  $decryptedBytes = array();

  for ($i = 0; $i < count($encryptedBytes); $i++) {
    $decryptedBytes[] = chr($encryptedBytes[$i] ^ ord($keyBytes[$i % strlen($keyBytes)]));
  }

  $decryptedText = implode($decryptedBytes);
  return $decryptedText;
}
    private apiRequest($url, $requestData) {
    
    }
}