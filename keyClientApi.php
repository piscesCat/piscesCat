<?php
class keyApiClient {
    public $udid;
    public $keyExpireTime;
    public $userDisplayName;
    public $deviceName;
    private $apiToken;
    private $secretKey;
    private $urlEnpoint;
    private $dataCryptExpireTime;
    private $dataSigture;
    
    public function __construct() {
        $this->urlEnpoint = 'https://udid-php-vercel.vercel.app';
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
        if ($this->genDataSigure === $this->dataSigture) {
            return $callback;
        }
    }
    
    private function getVendorIdentifier() {
        //NSString *vendorID = [[[UIDevice currentDevice] identifierForVendor] UUIDString];
        return 'test';
    }
    
    private function genDataSigure() {
        return md5($this->getVendorIdentifier() . $this->dataCryptExpireTime);
    }
    
    private function execute() {
        $this->checkUdid();
        $this->udid;
        return true;
    }
    
    private function requestUdid() {
        $this->udid = 'UDID_test';
    }
    
    private generateCryptKey($expireTime = null) {
        if ($expireTime === null) {
            $expireTime = $this->dataCryptExpireTime;
        }
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

private function strDecrypt($encryptedText, $expireTime) {
    $key = $this->generateCryptKey($expireTime);
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