<?php

class Tournament_lib {
    const SCHEDULE_STATUS_NOTSTART = 1;
    const SCHEDULE_STATUS_INPROGRESS = 2;
    const SCHEDULE_STATUS_ENDED = 3;
    
    /**
     * @var \BaseController
     */
    protected $_CI;

    /**
     *
     * @var \Tournament_model
     */
    protected $tournament_model;
    protected $utils;

    public function __construct(){
        $this->_CI = &get_instance();
        $this->_CI->load->model('tournament_model');
        $this->_CI->load->library(array('utils'));
        $this->tournament_model = $this->_CI->tournament_model;
        $this->utils = $this->_CI->utils;
    }

    public function getCombineCode($currecny, $id){
        $currencyUpper = strtoupper($currecny);
        return sprintf('%s_%s', strtoupper($currencyUpper), $id);
    }
    
    public function getExpansionCode($combineCode){
        $code = explode('_', $combineCode);
        if(count($code) != 2){
            return [];
        }else{
            return [$code[0], $code[1]];
        }
    }

    public function generateApplyExternalUniqueid($tournamentId, $event_id, $playerId){
        return sprintf('%s_%s_%s', $tournamentId, $event_id, $playerId);
    }

    public function getScheduleStatus($contestStart, $contestEnd){
        $nowDate = strtotime('now');
        $start = strtotime($contestStart);
        $end = strtotime($contestEnd);
        if ($nowDate < $start) {
            return self::SCHEDULE_STATUS_NOTSTART;
        } else if ($nowDate >= $start && $nowDate <= $end) {
            return self::SCHEDULE_STATUS_INPROGRESS;
        } else {
            return self::SCHEDULE_STATUS_ENDED;
        }        
    }

    public function getBannerImgPath($fileName){
        $imgPath = $this->utils->getSystemUrl("player",'includes/images/tournaments/banner/');
        return sprintf('%s%s', $imgPath, $fileName);
    }

    public function getIconImgPath($fileName){
        $imgPath = $this->utils->getSystemUrl("player",'includes/images/tournaments/icon/');
        return sprintf('%s%s', $imgPath, $fileName);
    }

    public function getScheduleApplyCount($scheduleId){
        $counts = 0;
        $data = $this->tournament_model->getScheduleApplyCount($scheduleId);
        if(!empty($data) && is_array($data)){
            $counts = $data['applyCount'];
        }
        return $counts;
    }

    public function getScheduleTotalBonusByType($scheduleId, $type){
        $result = 0;
        $mappingType = [
            Tournament_model::SCHEDULE_BONUS_TYPE_SYSTEM => $this->tournament_model->getScheduleTotalSysBonusAmount($scheduleId),
            Tournament_model::SCHEDULE_BONUS_TYPE_APPLY_AMOUNT => $this->tournament_model->getScheduleTotalRegistrationFee($scheduleId),
            Tournament_model::SCHEDULE_BONUS_TYPE_SYSTEM_AND_APPLY_AMOUNT => $this->tournament_model->getScheduleTotalRegistrationFeeAndSysBonus($scheduleId),
        ];
        if(isset($mappingType[$type])){
            $result = array_shift($mappingType[$type]);
        }
        return $result;
    }

    public function getBonusValueByDistributionType($bonusValue, $type){
        $result = null;
        $mappingType = [
            Tournament_model::RANK_BONUS_TYPE_FIXED_AMOUNT => $bonusValue,
            Tournament_model::RANK_BONUS_TYPE_PERCENTAGE => $bonusValue.'%',
        ];
        if(isset($mappingType[$type])){
            $result = $mappingType[$type];
        }
        return $result;
    }

    public function calculateRankQuota($rankFrom, $rankTo){
        if($rankFrom > $rankTo){
            return 0;
        }
        return $rankTo - $rankFrom + 1;        
    }

    public function checkGameSettingsFlow($params){        
        $result = [
            'status' => true,
            'message' => '',
            'checkFuntions' => ['_checkCombinations', '_checkGameIdOwnerType'],
        ];
        foreach ($result['checkFuntions'] as $checkFuntion) {
            $checkResult = $this->$checkFuntion($params);
            if(!$checkResult['status']){
                $result['status'] = false;
                $result['message'] = $checkResult['message'];
            }
        }
        return  $result;
    }

    public function getTournamentGamesSetting($tournamentId){
        $tournamentGameStting = [];
        $tournament = $this->tournament_model->getTournamentById($tournamentId);
        if(empty($tournament)){
            return [];
        }

        $tournamentGameStting = [
            "gamePlatform" => json_decode($tournament['gamePlatformId'], true),
            "gameType" => json_decode($tournament['gameTypeId'], true), //$tournament['gameTypeId'],
            "gameTag" => json_decode($tournament['gameTagId'], true), //$tournament['gameTagId'],
            "gamedescription" => json_decode($tournament['gameDescriptionId'], true), //$tournament['gameDescriptionId'],
        ];

        $this->utils->info_log('getTournamentGamesSetting', ['tournamentId' => $tournamentId, 'tournamentGameStting' => $tournamentGameStting]);

        return [$tournamentGameStting['gamePlatform'], $tournamentGameStting['gameType'], $tournamentGameStting['gameTag'], $tournamentGameStting['gamedescription']];
    }

    public function getActiveEventList($current_time = null, $tournamentId = null){
        if($current_time == null){
            $current_time = $this->utils->getNowForMysql();
        }
        $eventlist = $this->tournament_model->getActiveEventsByCurrentTime(['current_time' => $current_time, 'tournamentId' => $tournamentId]);
        return $eventlist;
    }

    /**
     * getEventPlayerScore
     *
     * @param int $player_id
     * @param int $event_id
     * @param array $gameDescList
     * @param string $from 2020-01-01 00:00:00
     * @param string $to 2020-01-01 23:59:59
     * @return array [$score, $lastBet]
     */
    public function getEventPlayerScore($player_id, $event_id, $gameDescList, $from, $to)
    {
        $this->utils->info_log('getEventPlayerScore', ['player_id' => $player_id, 'event_id' => $event_id, 'gameDescList' => $gameDescList, 'from' => $from, 'to' => $to]);
        $formatDateMinute_contestStartedAt = $this->utils->formatDateMinuteForMysql(new DateTime($from));
        $formatDateMinute_contestEndedAt = $this->utils->formatDateMinuteForMysql(new DateTime($to));
        $result = $this->tournament_model->countPlayerScore($player_id, $gameDescList, $formatDateMinute_contestStartedAt, $formatDateMinute_contestEndedAt);
        $score = $this->utils->safeGetArray($result, 'total_score', 0);
        $lastBet = $this->utils->safeGetArray($result, 'lastbet', nUll);
        return [$score, $lastBet];
    }

    public function isEventSettle($contestEndedAt){
        $nowDate = strtotime('now');
        $end = strtotime($contestEndedAt);
        if ($nowDate > $end) {
            return true;
        } else {
            return false;
        }
    }

    public function hasSettleRecords($event_id){
        $result = $this->tournament_model->hasSettledApplyRecords($event_id);
        return $result;
    }

    /**
     * calcPlayerRank
     * 
     * @param int $event_id
     * 
     */
    public function calcPlayerRank($event_id){
        $this->utils->info_log('placePlayerRank', ['event_id' => $event_id]);
        $eventExist = $this->tournament_model->checkEventExist($event_id);
        if(empty($eventExist)){
            $this->utils->debug_log('placePlayerRank', ['event_id' => $event_id, 'message' => 'event not found']);
            return;
        }

        $playerList = $this->tournament_model->getPlayersHasScore($event_id);
        $this->utils->debug_log('placePlayerRank', ['event_id' => $event_id, 'playerList' => $playerList]);
        return $playerList;
    }

    public function calcPlayerBonus($event_id, $player_id, $rank, $rankSettingKeyArray){
        $this->utils->info_log('calcPlayerBonus', ['event_id' => $event_id, 'player_id' => $player_id, 'rank' => $rank]);
        $bonus = 0;
        if(isset($rankSettingKeyArray[$rank])){
            $_rank = $rankSettingKeyArray[$rank];
            $bonus = isset($_rank['currentBonusValue']) ? $_rank['currentBonusValue'] : $_rank['bonusValue'];
            $this->utils->info_log('calcPlayerBonus', ['rank' => $rankSettingKeyArray[$rank]]);
        }
        return $bonus;
    }

    /**
     * _getEventRankSettingKeyArray
     * 
     * @param int $event_id
     * @param int $bonusPoolAmount //from schedule
     * @param int $distributionType //from schedule prevent to use tournament rank setting
     * 
     */
    public function _getEventRankSettingKeyArray($event_id, $bonusPoolAmount, $distributionType) {

        $settings = $this->tournament_model->getTournamentRanksSetting($event_id);
        $result = [];
        foreach ($settings as $setting) {
            for ($i = $setting['rankFrom']; $i <= $setting['rankTo']; $i++) {
                switch ($distributionType) {
                    case Tournament_model::RANK_BONUS_TYPE_FIXED_AMOUNT:
                        $setting['currentBonusValue'] = $setting['bonusValue'];
                        break;
                    case Tournament_model::RANK_BONUS_TYPE_PERCENTAGE:
                        $setting['currentBonusValue'] = $bonusPoolAmount * $setting['bonusValue'] / 100;
                        break;
                }
                $result[$i] = $setting;
            }
        }
        return $result;
    }

    /**
     * isDisabledPlayerTag
     * 
     * @param int $playerId
     * @param bool $result
     * 
     */
    public function isDisabledPlayerTag($playerId){
        //todo if needed
        return false;
    }

    public function checkEventDistributionTime($event){
        $nowDate = strtotime('now');
        $start = strtotime($event['distributionTime']);
        if ($nowDate > $start) {
            return true;
        } else {
            return false;
        }
    }

    public function getEventDetails($event_id){
        $rowData = $this->tournament_model->getEventById($event_id);
        $eventDetail = [
			"eventId" => $rowData['eventId'],
			"tournamentId" => $rowData['tournamentId'],
			"tournamentName" => $rowData['tournamentName'],
			"scheduleId" => $rowData['scheduleId'],
			"scheduleName" => $rowData['scheduleName'],
			"currency" => $rowData['currency'],
			"tournamentStatus" => $rowData['tournamentStatus'], //0:停用 ,1: 啟用 啟用則api 需回傳, 2:刪除
			"tournamentStartedAt" => $rowData['tournamentStartedAt'],
			"tournamentEndedAt" => $rowData['tournamentEndedAt'],
			"applyStartedAt" => $rowData['applyStartedAt'],
			"applyEndedAt" => $rowData['applyEndedAt'],
			"contestStartedAt" => $rowData['contestStartedAt'],
			"contestEndedAt" => $rowData['contestEndedAt'],
            "withdrawalConditionTimes" => $rowData['withdrawalConditionTimes'],
            "distributionType" => $rowData['distributionType'],
            "distributionTime" => $this->utils->safeGetArray($rowData, 'distributionTime', null),
			"applyAmount" => $rowData['applyAmount'],
			"eventStatus" => $rowData['eventStatus'], //0:停用 ,1: 啟用 啟用則api 需回傳, 2:刪除
			"applyCountThreshold" => $rowData['applyCountThreshold'],
		];
        $eventDetail['eventRequirements'] = [
            "applyConditionDepositAmount" => $rowData['applyConditionDepositAmount'],
            "applyConditionCountPeriod" => $rowData['applyConditionCountPeriod'], // 1:從註冊日開始, 2:區間
            "applyConditionCountPeriodStartAt" => $this->utils->safeGetArray($rowData, 'applyConditionCountPeriodStartAt', null),
            "applyConditionCountPeriodEndAt" => $this->utils->safeGetArray($rowData, 'applyConditionCountPeriodEndAt', null),
        ];
        return $eventDetail;
    }

    private function _checkCombinations($params){
        $this->_CI->load->model(['game_description_model']);
        $requireParams = ['platformId', 'gameTypeId', 'gameId'];
        $result = [
            'status' => true,
            'message' => '',
        ];
        foreach ($requireParams as $require) {
            if(!isset($params[$require])){
                $result['status'] = false;
                $result['message'] = 'Missing require params in checkCombinations';
                return $result;
            }
        }
        if(!$this->_CI->game_description_model->checkExistCombination($params['platformId'], $params['gameTypeId'] ,$params['gameId'])){
            $result['status'] = false;
            $result['message'] = 'this game combination is not exist';
            return $result;
        }
        return $result;
    }

    private function _checkGameIdOwnerType($params){
        $this->_CI->load->model(['game_tag_list']);
        $requireParams = ['gameId', 'gameTagId'];
        $result = [
            'status' => true,
            'message' => '',
        ];
        foreach ($requireParams as $require) {
            if(!isset($params[$require])){
                $result['status'] = false;
                $result['message'] = 'Missing require params in checkGameIdOwnerType';
                return $result;
            }
        }
        if(empty($this->_CI->game_tag_list->getTagByGameIdAndTagId($params['gameId'], $params['gameTagId']))){
            $result['status'] = false;
            $result['message'] = 'this game id does not belong game tag';
            return $result;
        }
        return $result;
    }
}
