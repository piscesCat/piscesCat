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