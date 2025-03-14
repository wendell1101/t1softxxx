<?php

trait sbobet_bet_module {

        /**
     * overview : set member settings
     *
     * @param $playerName
     * @param $newBetSetting
     * @return array
     */
    public function setMemberBetSetting($playerName,$newBetSetting=null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType',
            'playerName' => $playerName,
        );
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        //default bet setting
        $betSettings = array(
            "sport_type" => 0,//all sports type
            "market_type" => 0,//all market type
            "min_bet" => $this->minimum_bet,
            "max_bet" => $this->maximum_bet,
            "max_bet_per_match" => $this->maxPerMatch,
        );

        if(!empty($newBetSetting)){
            $betSettings = $newBetSetting;
        }
        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,  
            "serverId"      => $this->server_id,
            "betSettings"   => $betSettings,
            "method"        => self::API_update_bet_settings_by_sportid_and_marketype
        );

        $this->CI->utils->debug_log("updatePlayerBetSettingsBySportTypeAndMarketType params ============================>", $params);
        return $this->callApi(self::API_update_bet_settings_by_sportid_and_marketype, $params, $context);
    }

    /**
     * overview : get member bet settings by sports type and market type
     *
     * @param $playerName
     * @return array
     */
    public function getMemberBetSetting($playerName = "testt1dev") {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetMemberBetSetting',
            'playerName' => $playerName,
        );
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);


        $params = array(
            "companyKey" => $this->company_key,
            "username" => $gameUsername,
            "serverId" => $this->server_id,
            'method' => self::API_get_member_bet_settings_by_sportid_and_marketype
        );
        // echo "<pre>";
        // print_r($params);
        return $this->callApi(self::API_get_member_bet_settings_by_sportid_and_marketype, $params, $context);
    }

    /**
     * overview : process result for getMemberBetSetting
     *
     * @param $params
     * @return array
     */
    public function processResultForGetMemberBetSetting($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        $result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
        return array($success, $result);
    }

    public function getAllSportType(){
        return array(
            "--" => lang('MixParlay'),
            "0" => lang('ALL'),
            "1" => lang('Soccer'),
            "2" => lang('BasketBall'),
            "3" => lang('Football'),
            "4" => lang('Ice Hockey'),
            "5" => lang('Badminton'),
            "6" => lang('Pool   '),
            "7" => lang('Motor Sport'),
            "8" => lang('Tennis'),
            "9" => lang('Baseball'),
            "10" => lang('Volleyball'),
            "11" => lang('Others'),
            "12" => lang('Golf'),
            "13" => lang('Boxing'),
            "14" => lang('Cricket'),
            "15" => lang('Table Tennis'),
            "16" => lang('Rugby'),
            "17" => lang('Handball'),
            "18" => lang('Cycling'),
            "19" => lang('Athletics'),
            "20" => lang('Beach Soccer'),
            "21" => lang('Futsal'),
            "22" => lang('Entertainment'),
            "23" => lang('Financial'),
            "24" => lang('Darts'),
            "25" => lang('Olympic'),
            "26" => lang('Lacrosse'),
            "27" => lang('Water Polo'),
            "28" => lang('Winter Sports'),
            "29" => lang('Squash'),
            "30" => lang('Field Hockey'),
            "31" => lang('Mixed Martial Arts'),
            "32" => lang('E Sports'),
            "33" => lang('Gaelic Football'),
            "34" => lang('Hurling'),
            "35" => lang('Muay Thai'),
            "36" => lang('Aussie Rules Football'),
            "37" => lang('Bandy'),
            "38" => lang('Winter Olympics'),
        );
    }

    public function getAllMarketType(){
        return array(
            "0" => lang('ALL'),
            "1" => lang('Handicap'),
            "2" => lang('Odd/Even'),
            "3" => lang('Over/Under'),
            "4" => lang('Correct Score'),
            "5" => lang('1X2'),
            "6" => lang('Total Goal'),
            "7" => lang('First Half Hdp'),
            "8" => lang('First Half 1x2'),
            "9" => lang('First Half O/U'),
            "10" => lang('HT/FT'),
            "11" => lang('Money Line'),
            "12" => lang('First Half O/E'),
            "13" => lang('First Goal/Last Goal'),
            "14" => lang('First Half CS'),
            "15" => lang('Double Chance'),
            "16" => lang('Live Score'),
            "17" => lang('First Half Live Score'),
            "39" => lang('Outright'),
            "40" => lang('Mix Parlay'),
        );
    }

    /**
     * overview : get current leagues 
     *
     * @param $leagueNameKeyWord
     * @param $fromDate
     * @param $endDate
     * @param $sportType
     * @return array
     */
    public function getLeagueIdAndName($leagueNameKeyWord = "cup", $fromDate = null, $endDate = null, $sportType = 1) {
        $fromDate = empty($fromDate) ? $this->utils->getFirstDateOfCurrentMonth() : $fromDate;
        $endDate = empty($endDate) ? $this->utils->getTodayForMysql() : $endDate;

        $searchKey = 'game-api-'.$this->getPlatformCode().'-leagues-'.$fromDate.'to'.$endDate.'-'.$leagueNameKeyWord.'-'.$sportType;
        $rlt=$this->CI->utils->getJsonFromCache($searchKey);
        if(!empty($rlt)){
            return $rlt;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLeagueAPI',
            'method' => 'getLeagueIdAndName'
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "leagueNameKeyWord" => $leagueNameKeyWord,
            "fromDate" => $fromDate,
            "endDate" => $endDate,
            "sportType" => $sportType,
            'method' => self::API_GetLeagueIdAndName
        );

        $rlt =  $this->callApi(self::API_GetLeagueIdAndName, $params, $context);
        if($rlt['success']){
            $this->CI->utils->saveJsonToCache($searchKey, $rlt);
        }
        return $rlt;
    }

    /**
     * overview : process result for all league setting api
     *
     * @param $params
     * @return array
     */
    public function processResultForLeagueAPI($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $method = $this->getVariableFromContext($params, 'method');
        $resultJson = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJson);
        $result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
        return array($success, $result);
    }

    /**
     * overview : set league bet setting
     *
     * @param $leagueId
     * @param $isLive
     * @param $minBet
     * @param $maxBet
     * @param $maxBetRatio
     * @param $groupType
     * @return array
     */
    public function setLeagueBetSetting($leagueId, $isLive = false, $minBet = 1, $maxBet = 1000, $maxBetRatio = 0.9, $groupType = "BIG") {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLeagueAPI',
            'method' => 'setLeagueBetSetting'
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "leagueId" => $leagueId,
            "currency" => $this->currency,
            "isLive" => $isLive,
            "minBet" => $minBet,
            "maxBet" => $maxBet,
            "maxBetRatio" => $maxBetRatio,
            "groupType" => $groupType,
            'method' => self::API_SetLeagueBetSetting
        );

        return $this->callApi(self::API_SetLeagueBetSetting, $params, $context);
    }

    /**
     * overview : get league bet setting
     *
     * @param $leagueId
     * @param $isLive
     * @return array
     */
    public function getLeagueBetSetting($leagueId, $isLive) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLeagueAPI',
            'method' => 'getLeagueBetSetting'
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "leagueId" => $leagueId,
            "currency" => $this->currency,
            "isLive" => $isLive,
            'method' => self::API_GetLeagueBetSetting
        );

        return $this->callApi(self::API_GetLeagueBetSetting, $params, $context);
    }

    /**
     * overview : set league group bet setting
     *
     * @param $groupType
     * @param $isLive
     * @param $minBet
     * @param $maxBet
     * @param $maxBetRatio
     * @return array
     */
    public function setLeagueGroupBetSetting($groupType = "BIG", $isLive = false, $minBet = 1, $maxBet = 1000, $maxBetRatio = 0.9) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLeagueAPI',
            'method' => 'setLeagueGroupBetSetting'
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "groupType" => $groupType,
            "currency" => $this->currency,
            "isLive" => $isLive,
            "minBet" => $minBet,
            "maxBet" => $maxBet,
            "maxBetRatio" => $maxBetRatio,
            'method' => self::API_SetLeagueGroupBetSetting
        );

        return $this->callApi(self::API_SetLeagueGroupBetSetting, $params, $context);
    }

    /**
     * overview : get league group bet setting
     *
     * @param $groupType
     * @param $isLive
     * @return array
     */
    public function getLeagueGroupBetSetting($groupType = "SMALL", $isLive = false) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLeagueAPI',
            'method' => 'getLeagueGroupBetSetting'
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "groupType" => $groupType,
            "currency" => $this->currency,
            "isLive" => $isLive,
            'method' => self::API_GetLeagueGroupBetSetting
        );

        return $this->callApi(self::API_GetLeagueGroupBetSetting, $params, $context);
    }

}
