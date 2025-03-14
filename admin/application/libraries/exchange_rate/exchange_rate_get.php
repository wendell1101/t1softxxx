<?php
class exchange_rate_get {
    private $CI;
    public $apiName;
    public function __construct() {
        $this->CI = &get_instance();
        $this->load = $this->CI->load;
        $this->load->library(array('utils'));
        $this->utils = $this->CI->utils;
        $this->apiName = null;
    }

    public function loadExchangeRateApi($apiName) {
        $classExists = file_exists(strtolower(APPPATH.'libraries/exchange_rate/'.$apiName.".php"));
        if (!$classExists) {
            $this->utils->debug_log("Exchange api class cannot find");
            return false;
        }
        $this->load->library('exchange_rate/'.$apiName);
        $this->apiName = $this->CI->$apiName;
        $this->utils->debug_log("load exchange rate api success", $apiName );
        return $this->apiName;
    }

    public function checkEqualExchangeRate($base, $target) {
        $equalExchangeRate = $this->utils->getConfig('equal_exchange_rate');
        return in_array(strtolower($target), $equalExchangeRate) && in_array(strtolower($base), $equalExchangeRate);
    }

    public function getExchangeRate($apiName, $base, $target) {
        if ($base == $target || $this->checkEqualExchangeRate($base, $target)) {
            return [
                [
                    'base' => $base,
                    'target' => $target,
                    'exchangeRate' => 1,
                ],
                [
                    'base' => $target,
                    'target' => $base,
                    'exchangeRate' => 1,
                ]
            ];
        }

        $this->loadExchangeRateApi($apiName);
        if (!empty($this->apiName)) {
            $exchangeRate = $this->apiName->getExchangeRate($base, $target);
            if (!empty($exchangeRate)) {
                $result = [
                    [
                        'base' => $base,
                        'target' => $target,
                        'exchangeRate' => $exchangeRate,
                    ],
                    [
                        'base' => $target,
                        'target' => $base,
                        'exchangeRate' => $this->apiName->getDecimalPlaceSetting(1 / $exchangeRate),
                    ]
                ];
                $this->utils->debug_log("Exchange api getExchangeRate", $exchangeRate);
                $this->utils->debug_log("Exchange api result", $result);
                return $result;
            } else {
                $this->utils->debug_log("Exchange api getExchangeRate is error");
            }
        } else {
            $this->utils->debug_log(__METHOD__, "exchange api setting has error");
        }
        return [];
    }
}