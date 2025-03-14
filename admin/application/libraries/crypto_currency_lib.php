<?php

class Crypto_currency_lib {
    /**
     * @var \BaseController
     */
    protected $_CI;

    /**
     * @var \Abstract_crypto_payment_api
     */
    protected $_api;

    /**
     * @var array
     */
    protected $_coins;

    /**
     * @var array
     */
    protected $_chains;

    /**
     * @var array
     */
    protected $_crypto_target_db;

    public function __construct()
    {
        $this->_CI = &get_instance();
    }

    /**
     * @return bool
     */
    public function init()
    {
        $this->_coins = $this->_CI->config->item('crypto_currency_enabled_coins');
        if(empty($this->_coins) || !is_array($this->_coins)){
            return false;
        }

        $this->_chains = $this->_CI->config->item('crypto_currency_enabled_chain');
        if(empty($this->_chains) || !is_array($this->_chains)){
            return false;
        }

        $this->_crypto_target_db = $this->_CI->config->item('crypto_target_db');
        if(empty($this->_crypto_target_db) || !is_array($this->_crypto_target_db)){
            return false;
        }

        $api_id = $this->_CI->config->item('crypto_currency_use_api');
        if (empty($api_id)) {
            return false;
        }

        list($loaded, $apiClassName) = $this->_CI->utils->loadExternalSystemLib($api_id);

        if(false === $loaded){
            return false;
        }

        $this->_api = $this->_CI->$apiClassName;

        return true;
    }

    protected function _getAddressWithCoin($player_id, $coin_id)
    {
        if(empty($this->_api)) {
            return [];
        }

        if (false === $this->_api->isAvailable()) {
            return [];
        }

        $chains = $this->_api->getChains($coin_id);
        if (empty($chains)) {
            return [];
        }

        $address = [];

        foreach($chains as $chain_id) {
            $chain_id = strtoupper($chain_id);

            $result = $this->_api->getAddress($player_id, $chain_id, $coin_id);
            if(empty($result)) {
                continue;
            }

            $address[] = [
                'chainName' => $chain_id,
                'address' => $result,
                'allowDeposit' => true,
                'allowWithdrawal' => true,
            ];
        }

        return $address;
    }

    protected function _getAllAddressFromAPI($player_id)
    {
        $result = [];
        foreach ($this->_coins as $coin_id) {
            $coin_id = strtoupper($coin_id);
            $address = $this->_getAddressWithCoin($player_id, $coin_id);

            $result[] = [
                'coinId' => $coin_id,
                'chains' => $address
            ];
        }

        return $result;
    }

    public function getAllAddress($player_id)
    {
        $result = $this->_getAllAddressFromAPI($player_id);

        return $result;
    }

    public function getCryptoSetting($player_id)
    {
        $result = [];
        $playerWithdrawalRule = $this->_CI->utils->getWithdrawMinMax($player_id);
        $withdrawal_result = [
            'minPerTrans' => (double)$playerWithdrawalRule['min_withdraw_per_transaction'],
            'maxPerTrans' => (double)$playerWithdrawalRule['max_withdraw_per_transaction'],
            'dailyLimit'  => (double)$playerWithdrawalRule['daily_max_withdraw_amount']
        ];
        $result['withdrawal'] =  $withdrawal_result;
        return $result;
    }

    public function getEnabledCoinsAndChains(){
        return [
            'coins' => $this->_coins,
            'chains' => $this->_chains,
        ];
    }

    public function getTargetDataBaseByCoin($coin){
        $result = '';
        if($this->_crypto_target_db && is_string($coin)){
            foreach ($this->_crypto_target_db as $setCoin => $DB) {
                if(strtoupper($coin) === $setCoin && isset($DB['mdb_key'])) {
                    $result = $DB['mdb_key'];
                }
            }
        }
        return $result;
    }

    public function generatePlayerCryptoWalletWithAPI($playerId, $allAddress){
        if(!empty($allAddress) && is_array($allAddress)){
            $this->_CI->load->model(['player_crypto_wallet_info']);
            $this->_CI->load->library(['playerapi_lib']);
            foreach ($allAddress as $cryptoInfo) {
                if(empty($cryptoInfo['coinId']) || empty($cryptoInfo['chains'])){
                    continue;
                }
                $targetDBKey = $this->getTargetDataBaseByCoin($cryptoInfo['coinId']); 
                if(!empty($targetDBKey)){                            
                    $insertResult = $this->_CI->playerapi_lib->switchCurrencyForAction($targetDBKey, function() use ($playerId, $cryptoInfo){
                        return $this->_CI->player_crypto_wallet_info->insertCryptoWalletInfoFromAPI($playerId, $cryptoInfo);                                        
                    });
                    if(!$insertResult){
                        $this->_CI->utils->debug_log('============insert crypto wallet failed', $targetDBKey, $cryptoInfo);
                    }
                }                    
            }
        }
    }

}
