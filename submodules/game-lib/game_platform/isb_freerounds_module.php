<?php

trait isb_freerounds_module {
    #This method returns a list of games with free round support 
    public function getAvailableGames() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAvailableGames',
        );

        $params = array(
            "isFreeround" => true,
            "method" => self::GET_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/get_available_games"
        );

        return $this->callApi(self::API_FR_getAvailableGames, $params, $context);
    }

    public function processResultForGetAvailableGames($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        return array(true, $resultJson);
    }

    #This method returns a list with all the free round packages and promotions for a player.
    public function getPlayerFreeRounds($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetPlayerFreeRounds',
            'playerName' => $playerName,
            'game_code' => isset($extra['game_code']) ? $extra['game_code'] : null
        );
        $isb_params = array(
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername
        );
        $hashCode = $this->getHashCode($isb_params);
        $params = array(
            "isFreeround" => true,
            "method" => self::POST_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/get_player_freerounds?hash=".$hashCode,
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername

        );

        return $this->callApi(self::API_FR_getPlayerFreeRounds, $params, $context);
    }

    public function processResultForGetPlayerFreeRounds($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $game_code = $this->getVariableFromContext($params, 'game_code');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = false;
        $pendingCampaign = array();
        $count = 0;
        if(strtolower($resultJson['status']) == self::FR_SUCCESS){
            $success = true;
            if(!empty($resultJson['packages'])){
                foreach ($resultJson['packages'] as $key => $package) {
                    //get all pending campaign only that need to be accept/declined by player
                    if(strtolower($package['freeround_status']) == self::FR_PENDING && $count == 0){
                        if (in_array($game_code, $package['skin_ids']))
                        {
                            $game_key = array_search($game_code,$package['skin_ids']);
                            $pendingCampaign = array(
                                "player_id" => $package['player_id'],
                                "freeround_id" => $package['freeround_id'],
                                "campaign_id" => $package['campaign_id'],
                                "operator_name" => $package['operator_name'],
                                "campaign_name" => $package['campaign_name'],
                                "campaign_expiration_date" => $package['campaign_expiration_date'],
                                "freeround_total" => $package['freeround_total'],
                                // "skin_ids" => $package['skin_ids'],
                                "freeround_status" => $package['freeround_status'],
                                "player_name" => $playerName,
                                "game_code" => $game_code,
                                "game_name" => $package['game_names'][$game_key],
                            );
                            $count++;
                        }
                    }
                }
            }
        }
        $resultJson = $pendingCampaign;
        return array($success, $resultJson);
    }

    private function getPlayerCampaignCacheKey($playerName, $campaign_id){
        return 'game-api-'.$this->getPlatformCode().'-campaign-'.$campaign_id.'-'.$playerName;
    }

    public function triggerPlayerFreeRounds($playerName,$campaign_id) {
        // $playerCampaignKey=$this->getPlayerCampaignCacheKey($playerName, 8);
        // $this->CI->utils->saveJsonToCache($playerCampaignKey, 'already trigger manually');
        // exit();
        $playerCampaignKey=$this->getPlayerCampaignCacheKey($playerName, $campaign_id);
        $cacheResult = $this->CI->utils->getJsonFromCache($playerCampaignKey);
        if(!empty($cacheResult)){
            return $cacheResult;
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerPlayerFreeRounds',
            'playerName' => $playerName
        );
        $isb_params = array(
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "campaign_id" => $campaign_id
        );

        $hashCode = $this->getHashCode($isb_params);
        $params = array(
            "isFreeround" => true,
            "method" => self::POST_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/trigger?hash=".$hashCode,
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "campaign_id" => $campaign_id
        );

        $result = $this->callApi(self::API_FR_getPlayerFreeRounds, $params, $context);

        if($result['success']){
            $this->CI->utils->saveJsonToCache($playerCampaignKey, $result);
        }
        return $result;
    }

    public function processResultForTriggerPlayerFreeRounds($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        $success = false;
        if(strtolower($resultJson['status']) == self::FR_SUCCESS){
            $success = true;
        }
        return array($success, $resultJson);
    }

    #This method returns a list of created campaign
    public function getCampaigns($playerName) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetCampaigns',
            'playerName' => $playerName
        );

        $params = array(
            "isFreeround" => true,
            "method" => self::GET_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/get_campaigns"
        );

        return $this->callApi(self::API_FR_getCampaigns, $params, $context);
    }

    public function processResultForGetCampaigns($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $availableCampaign = array();
        $triggeredCampaign = array();
        $currentDateTime = $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s');
        if(isset($resultJson['campaigns']) && !empty($resultJson['campaigns'])){
            foreach ($resultJson['campaigns'] as $key => $campaign) {
                $campaign['campaign_expiration_date'] = $this->gameTimeToServerTime($campaign['campaign_expiration_date']);
                $campaign['campaign_start_date'] = $this->gameTimeToServerTime($campaign['campaign_start_date']);
                if($campaign['campaign_expiration_date'] > $currentDateTime){
                    $availableCampaign[] = $campaign['campaign_id'];
                    $triggeredCampaign[] = $this->triggerPlayerFreeRounds($playerName,$campaign['campaign_id']);
                }
            }
        }

        $result = array(
            'availableCampaign' => $availableCampaign,
            'countOfTriggeredCampaign' => count($triggeredCampaign)
        );
        return array(true, $result);
    }

    public function getHashCode($params){
        return hash_hmac('SHA256', json_encode($params), $this->isb_api_hashcode_secret_key);
    }

    #This method will accept the pending free round for specific player
    public function acceptPlayerFreeRound($playerName, $freeRoundId) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAcceptCancelPlayerFreeRound',
            'playerName' => $playerName
        );
        $isb_params = array(
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "freeround_id" => (int)$freeRoundId
        );
        $hashCode = $this->getHashCode($isb_params);
        $params = array(
            "isFreeround" => true,
            "method" => self::POST_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/accept?hash=".$hashCode,
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "freeround_id" => (int)$freeRoundId

        );

        return $this->callApi(self::API_FR_acceptFreeRound, $params, $context);
    }

    public function cancelPlayerFreeRound($playerName, $freeRoundId) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForAcceptCancelPlayerFreeRound',
            'playerName' => $playerName
        );
        $isb_params = array(
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "freeround_id" => (int)$freeRoundId
        );
        $hashCode = $this->getHashCode($isb_params);
        $params = array(
            "isFreeround" => true,
            "method" => self::POST_METHOD,
            "uri" => $this->isb_freerounds_url."/api/".$this->isb_fr_api_version."/freerounds/promo/cancel?hash=".$hashCode,
            "operator_name" => $this->isb_operator_name,
            "player_id" => $gameUsername,
            "freeround_id" => (int)$freeRoundId

        );

        return $this->callApi(self::API_FR_cancelFreeRound, $params, $context);
    }

    public function processResultForAcceptCancelPlayerFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $success = false;
        if(strtolower($resultJson['status']) == self::FR_SUCCESS){
            $success = true;
        }
        return array($success, $resultJson);
    }
}
