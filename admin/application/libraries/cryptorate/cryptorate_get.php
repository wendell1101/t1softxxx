<?php

/**
 * Wrapper class used to send CryptoRate through API.
 *
 * CryptoRate API configuration sample is defined in config_default_common.php. Each API should have
 * their own parameters defined in config_secret_local.php.
 *
 * Check the documentation of each CryptoRate API implementation class to see what parameters are expected.
 */
class Cryptorate_get {
    private $CI;

    public function __construct() {
        $this->CI = &get_instance();
    }

    public function getConvertCryptoCurrency($amount, $base, $target, $paymentType = '', $isPCFApi = false) {
        if(!empty($target)) {
            $apiName = $this->getCustomApiName($target);

            if (!$apiName) {
                $this->CI->utils->debug_log("Customer api getConvertCryptoCurrency Cannot find " . $apiName . ' api');
                return false;
            }

            if($isPCFApi){
                $convertCryptoCurrency = $apiName->convertCryptoCurrencyByPcfApi($amount, $base, $target, $paymentType);
            }else{
                $convertCryptoCurrency = $apiName->convertCryptoCurrency($amount, $base, $target, $paymentType);
            }

            if(is_array($convertCryptoCurrency)){
                $this->CI->utils->debug_log("Customer api CryptoRate",$convertCryptoCurrency);
                return $convertCryptoCurrency;
            }

        }else{
            $this->CI->utils->debug_log(__METHOD__, "Customer api CryptoRate is error");
            return false;
        }
    }

    public function getCustCryptoUpdateTiming($cryptocurrency) {
        if(!empty($cryptocurrency)) {
            $apiName = $this->getCustomApiName($cryptocurrency);
            if (!$apiName) {
                $this->CI->utils->debug_log("Customer api getCustCryptoUpdateTiming Cannot find " . $apiName . ' api');
                return false;
            }
            $updateTiming = $apiName->getUpdateTiming();

            if(!empty($updateTiming)){
                $this->CI->utils->debug_log("Customer api getUpdateTiming",$updateTiming);
                return $updateTiming;
            }else{
                return 30*60; //default sec
            }

        }else{
            $this->CI->utils->debug_log(__METHOD__, "Customer api CryptoRate is error");
            return false;
        }
    }

    public function getCustCryptoAllowCompareDigital($cryptocurrency) {
        if(!empty($cryptocurrency)) {
            $apiName = $this->getCustomApiName($cryptocurrency);
            if (!$apiName) {
                $this->CI->utils->debug_log("Customer api getCustCryptoAllowCompareDigital Cannot find " . $apiName . ' api');
                return false;
            }
            $allowCompareDigital = $apiName->getAllowCompareDigital();
            if(!empty($allowCompareDigital)){
                $this->CI->utils->debug_log("Customer api getCustCryptoAllowCompareDigital", $allowCompareDigital);
                return $allowCompareDigital;
            }else{
                return 0;
            }
        }else{
            $this->CI->utils->debug_log(__METHOD__, "Customer api CryptoRate is error");
            return false;
        }
    }

    public function getCustCryptoInputDecimalPlaceSetting($cryptocurrency,$reciprocal = true) {
        if(!empty($cryptocurrency)) {
            $apiName = $this->getCustomApiName($cryptocurrency);
            if (!$apiName) {
                $this->CI->utils->debug_log("Customer api getCustCryptoInputDecimalPlaceSetting Cannot find " . $apiName . ' api');
                return false;
            }
            $custCryptoInputDecimalPlaceSetting = $apiName->getInputDecimalPlaceSetting($reciprocal);

            if(!empty($custCryptoInputDecimalPlaceSetting)){
                $this->CI->utils->debug_log("Customer api getInputDecimalPlaceSetting",$custCryptoInputDecimalPlaceSetting);
                return $custCryptoInputDecimalPlaceSetting;
            }else{
                return 0; //default int
            }

        }else{
            $this->CI->utils->debug_log(__METHOD__, "Customer api CryptoRate is error");
            return false;
        }
    }

    public function getDecimalPlaceSetting($cryptocurrency, $number) {
        if(!empty($cryptocurrency)) {
            $apiName = $this->getCustomApiName($cryptocurrency);
            if (!$apiName) {
                $this->CI->utils->debug_log("Customer api getgetDecimalPlaceSetting Cannot find " . $apiName . ' api');
                return false;
            }
            $format_number = $apiName->getDecimalPlaceSetting($number);

            if(!empty($format_number)){
                $this->CI->utils->debug_log("Customer api getgetDecimalPlaceSetting",$format_number);
                return $format_number;
            }else{
                return 0; //default int
            }

        }else{
            $this->CI->utils->debug_log(__METHOD__, "Customer api getDecimalPlaceSetting is error");
            return false;
        }
    }

    public function getCustomApiName($cryptocurrency) {
        $apiNames = $this->CI->config->item('custom_cryptorate_api');
        if(is_array($apiNames)){
            foreach ($apiNames as $customApiCryptoCurrency => $apiName) {
                if(strpos(strtoupper($cryptocurrency), $customApiCryptoCurrency) !== false){
                    $classExists = file_exists(strtolower(APPPATH.'libraries/cryptorate/'.$apiName.".php"));

                    if (!$classExists) {
                        $this->CI->utils->debug_log("Customer api CryptoRate class cannot find");
                        return false;
                    }
                    $this->CI->load->library('cryptorate/'.$apiName);
                    $cryptoRateApi = $this->CI->$apiName;
                    $this->CI->utils->debug_log("Customer api apiName",$apiName, $cryptocurrency );
                    return $cryptoRateApi;
                }
            }
        }else{
            return false;
        }
    }
}
