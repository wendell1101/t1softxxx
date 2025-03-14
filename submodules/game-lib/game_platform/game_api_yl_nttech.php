<?php
require_once dirname(__FILE__) . '/game_api_common_nttech.php';

class Game_api_yl_nttech extends Game_api_common_nttech {
    public function getPlatformCode(){
        return YL_NTTECH_GAME_API;
    }

    public function __construct(){
        parent::__construct();
    }
    
    protected function getAPIKey($gameUsername) 
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetKey',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "cert" => $this->cert,
            "user" => $gameUsername,
            "userName" => $gameUsername,
            "currency" => $this->currency,
            "extension1" => $this->extension1
        );

        return $this->callApi(self::API_generateToken, $params, $context);
    }
    
    public function processResultForGetKey($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result= [];

        if($success){
            $result['status'] = $resultArr['status'];
            $result['key'] = $resultArr['key'];
        }
        return array($success, $result);
    }


    public function queryForwardGame($playerName, $extra = null) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $result = $this->getAPIKey($gameUsername);
        $success = (isset($result['success']) && $result['success']) ? $result['success'] : false;
        $language = $this->getSystemInfo('language', $extra['language']);
        if($success)
        {
            $params = array(
                'user' => $gameUsername,
                'key' => $result['key'],
                'extension1' => $this->extension1,
                'userName' => $gameUsername,
                'language' => $this->getLauncherLanguage($language),
                'returnURL' => $this->getHomeLink()
            );

            if(isset($extra['game_code'])) {
                $params['game_code'] = $extra['game_code'];
            }

            $url = $this->api_url . "/api/" . $this->site . "/loginV2?" . http_build_query($params);
            
            return ['success' => true, 'url' => $url];
        }
        return ['success' => false, 'url'=> null];
    }

    public function syncOriginalGameLogs($token = false)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $startDate = strtotime($startDate->format("Y-m-d H:i:s"))*1000;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $result = [];

        $params = [
            'cert' => $this->cert,
            'lastupdatedate' => $startDate,
            'extension1' => $this->extension1,
            'status' => -1 // all data
        ];

        $this->CI->utils->debug_log(__METHOD__. ' params', $params);

        $result = $this->callApi(self::API_getTransactionsByLastUpdateDate, $params, $context);

        return ['success' => $result['success'], 'result' => $result];
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $statusCode);
        $gameRecords = !empty($arrayResult['transactions']) ? $arrayResult['transactions']:[];

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        if($success && !empty($gameRecords)) {

            $this->processGameRecords($gameRecords, $responseResultId);

            // $this->CI->utils->debug_log('---------- YL result for processResultForSyncGameRecords ----------', $arrayResult);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) 
    {
        if(!empty($gameRecords)){

            foreach ($gameRecords as $index => $row) {
                $roundtime = isset($row['updateTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", strtotime($row['updateTime']))) : '';
                $newRecords['userid'] = isset($row["userId"]) ? $row["userId"] : '';
                $newRecords['gametype'] = isset($row["gameId"]) ? $row["gameId"] : '';
                $newRecords['extension1'] = isset($row["extension1"]) ? $row["extension1"] : $this->extension1;
                $newRecords['currency'] = isset($row["currency"]) ? $row["currency"] : $this->currency_type;
                $newRecords['tableid'] = isset($row['202106241753581710368578']) ? $row['202106241753581710368578'] : '';
                $newRecords['roundtime'] = $roundtime;
                $newRecords['betamount'] = isset($row['validbet']) ? $row['validbet'] : 0;
                $newRecords['updatetime'] = $roundtime;
                $newRecords['bettime'] = isset($row['betTransTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime($row['betTransTime']))) : '';
                $newRecords['roundid'] = isset($row['gameNumber']) ? $row['gameNumber'] : '';
                $newRecords['roundstarttime'] = isset($row['betTransTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime($row['betTransTime']))) : '';
                $newRecords['winloss'] = (isset($row['profit'])) ? $row['profit'] : 0;
                $newRecords['status'] = isset($row['status']) ? $row['status'] : '';
                $newRecords['validbet'] = isset($row['betAmount']) ? $row['betAmount'] : $row['betAmount'];
                $newRecords['response_result_id'] = $responseResultId;
                $newRecords['external_uniqueid'] = isset($row['id']) ? $row['id'] : '';
                $newRecords['updated_at'] = date("Y-m-d H:i:s");
                $newRecords['creationtime'] = date("Y-m-d H:i:s");


                
                $newRecords['odds'] = '';
                $newRecords['result'] =  '';
                $newRecords['gameround'] = '';
                $newRecords['category'] = '';
                $newRecords['txid'] = '';
                $newRecords['gameshoe'] = '';
                $newRecords['dealerdomain'] = '';
                $newRecords['txnamount'] = '';
                $newRecords['lossamount'] = '';
                $gameRecords[$index] = $newRecords;
                unset($newRecords);
            }
            
        }
    }

    public function getGameUsernameByPlayerUsername($playerUsername) {
        $game_user_name = parent::getGameUsernameByPlayerUsername($playerUsername);
        return strtolower($game_user_name);
    }
}
/*end of file*/