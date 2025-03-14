<?php

/**
 * Common Seamless method utilities
 */
trait common_seamless_utils
{
    /**
     * Return the Currency of Game, base in Extra Info or within Class
     * 
     * @return string
     */
    public function gameCurrency()
    {
        if(! empty($this->currencyTypeInExtraInfo)){
            return $this->currencyTypeInExtraInfo;
        }elseif(! empty($this->currencyType)){
            return $this->currencyType;
        }

        return $this->defaultGameCurrency;
    }

    /** 
     * Log certain Information in terminal
     * TODO make this accept multiple log
     * 
     * @param string $logMessage
     * @param string $logType
     * @param array $logValue
     * 
     * @return string
    */
    public function logThis($logMessage='',$logValue=[],$logType='debug_log')
    {
        $this->loadModel(['external_system']);

        $platformName = $this->CI->external_system->getSystemName($this->getPlatformCode());
        $logMessage = $platformName. ' ' .$logMessage;

        return $this->CI->utils->$logType($logMessage,$logValue);
    }

    /**
     * Load Model
     * 
     * @param array $model
     * 
     * @return void
     */
    public function loadModel(array $model)
    {
        return $this->CI->load->model($model);
    }

    /** 
     * Check if token is valid
     * 
     * @param string $token the token to validate
     * 
     * @return boolean
    */
    public function isTokenValid($token)
    {
        $playerInfo = parent::getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /**
     * Get Player Balance in this subwallet
     * 
     * @param int $playerId the player id
     * @param int|null $gameProviderId
     * 
     * @return mixed
     */
    public function getPlayerSubWalletBalance($playerId,$gameProviderId=null)
    {
        
        if(empty($playerId)){
            return null;
        }
        
        if(is_null($gameProviderId)){
            $gameProviderId = $this->getPlatformCode();
        }

        $this->CI->load->model('player_model');

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId,$gameProviderId);

        return $balance;
    }

    /**
     * Get player information by valid token
     * 
     * @param string $token
     * 
     * @return mixed
     */
    public function getPlayerInfoBasedInToken($token)
    {
        if(! $this->isTokenValid($token)){
            return null;
        }

        return parent::getPlayerInfoByToken($token);
    }

    /**
     * Get value of and array based in key, can pass default value
     * 
     * @param array $array
     * @param mixed $key
     * @param mixed|null $def
     * 
     * return mixed
     */
    public function getArrKeyVal($array,$key,$def=null)
    {
        if(! is_array($array)){
            $array = (array) $array;
        }

        $val = isset($array[$key]) ? $array[$key] : $def;

        return $val;
    }

    /** 
     * Round down number, meaning 0.019 will be 0.01 instead round up 0.019 to 0.02
    */
    public function round_down($number,$precision = 3)
    {

        $fig = (int) str_pad('1', $precision, '0');

        return (floor($number * $fig) / $fig);
    }

    /**
     * Generate Basic Autorization text
     * 
     * @param mixed $user
     * @param mixed $password
     * @param mixed|null $def
     * 
     * @return mixed
     */
    public function generateBasicAuth($user,$password,$def=null)
    {
        $t = base64_encode($user.':'.$password);

        if(!empty($t)){
            return $t;
        }

        return $def;
    }

    /** 
     * Insert Seamless error in table common_seamless_error_logs
     * 
     * @param array $data
     * 
     * @return int $lastInsertId
    */
    public function saveSeamlessError($data)
    {
        $lastInsertId = null;

        try{
            $result = $this->CI->common_seamless_error_logs->insertTransaction($data);

            if($result){
                $lastInsertId = $result;
            }

        }catch(\Exception $e){
            $this->CI->utils->error_log(__METHOD__.' error inserting into common_seamless_error_log',$e->getMessage());
        }

        return $lastInsertId;
    }
}
