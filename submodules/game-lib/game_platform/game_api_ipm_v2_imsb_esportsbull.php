<?php
require_once dirname(__FILE__) . '/game_api_v2_commmon_ipm.php';

class Game_api_ipm_v2_imsb_esportsbull extends Game_api_v2_commmon_ipm
{
    public $default_game_type;
    public $default_game_code;
    public $default_product_code;
    public $esportsbull_product_types;

    const ORIGINAL_GAME_LOGS_TABLE = "ipm_v2_imsb_esportsbull_game_logs";

    # Fields in tianhao_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'PlayerId',
        'LastUpdatedDate',
        'StakeAmount',
        'MemberExposure',
        'PayoutAmount',
        'WinLoss',
        'WagerType', 
        'IsSettled',
        'IsConfirmed',
        'IsCancelled',
        'BetTradeCommission',
        'BetTradeBuyBackAmount',
        'GameId',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'StakeAmount',
        'MemberExposure',
        'PayoutAmount',
        'WinLoss',
        'BetTradeCommission',
        'BetTradeBuyBackAmount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when tianhao_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'gameUsername',
        'game_date',
        'bet_time',
        'game_code',
        'payout_amount',
        'bet_amount',
        'result_amount',
        'is_settled',
        'is_confirmed',
        'is_cancelled',
        'game_description_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'payout_amount',
        'bet_amount',
        'result_amount'
    ];

    # Don't ignore on refresh 
    const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

    const RESYNC_BY_NO_OFDAYS_AGO = 7;

    const IPM_CODE = [
        "SPORTS" => [
            "GAME_CODE" => "IMSB",
            "PRODUCT_CODE" => 301,
            "DATE_FILTER_TYPE" => 4,
        ],
        "ESPORTS" => [
            "GAME_CODE" => "ESPORTSBULL",
            "PRODUCT_CODE" => 401,
            "DATE_FILTER_TYPE" => 3,
        ]
    ];

    const RESULT_STATUS = [
        "WIN" => 1,
        "LOSE" => 2,
        "DRAW" => 3
    ];

    const TAG_CODE_SPORTS = 'sports';
    const TAG_CODE_E_SPORTS = 'e_sports';
    
    public function __construct() 
    {
        parent::__construct();
        $this->currency = $this->getSystemInfo('currency');
        $this->default_game_type = $this->getSystemInfo('default_game_type', self::TAG_CODE_SPORTS);
        $this->default_game_code = $this->getSystemInfo('default_game_code', self::IPM_CODE["SPORTS"]["GAME_CODE"]);
        $this->default_product_code = $this->getSystemInfo('default_product_code', self::IPM_CODE["SPORTS"]["PRODUCT_CODE"]);
        $this->esportsbull_product_types = $this->getSystemInfo('esportsbull_product_types', [1]);
    }

    public function getPlatformCode() 
    {
        return IPM_V2_IMSB_ESPORTSBULL_API;
    }

    public function getLauncherLanguage($lang)
    {
        $this->CI->load->library("language_function");
        switch ($lang) 
        {
            case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'ZH-CN';
                break;
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'EN';
                break;
            case 'vi':
            case 'vi-vi':
            case 'vi-vn':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'VI';
                break;
            case 'th':
            case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'TH';
                break;
            default:
                $lang = 'EN';
                break;
        }

        return $lang;
    }

    private function getReason($error_code)
    {
        switch($error_code) 
        {
            case '506' :
            case '504' :
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case '508' :
                return self::REASON_INVALID_PRODUCT_WALLET;
                break;
            case '509' :
            case '516' :
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case '510' :
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case '519' :
            case '543' :
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case '514' :
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case '604' :
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
                break;
            case '605' :
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function getAccessToken($playerName = null, $extra) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_type = isset($extra["game_type"]) && !empty($extra["game_type"]) ? $extra["game_type"] : '';
        $game_code = isset($extra["game_code"]) && !empty($extra["game_code"]) ? $extra["game_code"] : '';

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName
        );

        if(isset($extra["language"]) && !empty($extra["language"]))
        {
            $language = $this->getLauncherLanguage($extra['language']);
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

        if(!empty($game_code)) {
            switch($game_code) {
                case self::IPM_CODE["SPORTS"]["GAME_CODE"]:
                    $product_code = self::IPM_CODE["SPORTS"]["PRODUCT_CODE"];
                    break;
                case self::IPM_CODE["ESPORTS"]["GAME_CODE"]:
                    $product_code = self::IPM_CODE["ESPORTS"]["PRODUCT_CODE"];
                    break;
                default:
                    $game_code = $this->default_game_code;
                    $product_code = $this->default_product_code;
                    break;
            }
        }else{
            switch($game_type) {
                case self::TAG_CODE_SPORTS:
                    $game_code = self::IPM_CODE["SPORTS"]["GAME_CODE"];
                    $product_code = self::IPM_CODE["SPORTS"]["PRODUCT_CODE"];
                    break;
                case self::TAG_CODE_E_SPORTS:
                    $game_code = self::IPM_CODE["ESPORTS"]["GAME_CODE"];
                    $product_code = self::IPM_CODE["ESPORTS"]["PRODUCT_CODE"];
                    break;
                default:
                    $game_code = $this->default_game_code;
                    $product_code = $this->default_product_code;
                    break;
            }
        }

        if($extra['is_mobile'])
        {
            $apiMethod = self::API_checkMobileLoginToken;
        }else{
            $apiMethod =  self::API_checkLoginToken;
        }

        $params = array(
            'MerchantCode' => $this->merchant_code,
            'PlayerId' => $gameUsername,
            'GameCode' => $game_code,
            'Language' => $language,
            'IpAddress'=> $this->CI->input->ip_address(),
            "ProductWallet"=> $product_code
        );

        $this->CI->utils->debug_log(__METHOD__ . ' ==========================>', 'getAccessToken_params', $params);

        return $this->callApi($apiMethod, $params, $context);
    }

    public function processResultForGetAccessToken($params) 
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForGetAccessToken ==========================>', $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null);

        if($success) 
        {
            $this->game_url = $resultArr["GameUrl"];
            return $success;
        }

    }

    public function queryPlayerBalance($playerName) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
            'MerchantCode' => $this->merchant_code,
            'PlayerId' => $gameUsername,
            "ProductWallet" => $this->default_product_code
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) 
    {
        $this->CI->utils->debug_log('##########  QUERY PLAYER BALANCE         #####################', $params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForQueryPlayerBalance ==========================>', $resultJsonArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array();

        if($success)
        {
            $result['balance'] = @floatval($resultJsonArr['Balance']);

            if($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) 
            {
                $this->CI->utils->debug_log('query balance playerId ========>', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
            }else{
                $this->CI->utils->debug_log('cannot get player id from ========>' . $playerName . ' getPlayerIdInGameProviderAuth');
            }
        }else{
            if(@$resultJsonArr['Code'] == self::PLAYER_NOT_EXIST) 
            {
                $result['exists'] = false;
            }else{
                $result['exists'] = true;
            }
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $data = array(
                "transaction_type" => self::DEPOSIT_TRANSACTION,
                "external_transaction_id" => $transfer_secure_id,
                "callback_method" => "processResultForDepositToGame",
                "game_username" => $gameUsername,
                "player_id" => $playerId,
                "amount" => $amount,
                "api_url" => self::API_depositToGame,
                "product_code" => $this->default_product_code
            );

        return $this->init_fund_transaction($data);
    }

    public function processResultForDepositToGame($params) 
    {
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
        );

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr,$gameUsername);
        $this->utils->debug_log("Deposit ResultArr ============================>", $resultJsonArr);
        $this->utils->debug_log("Deposit result from response result id ============================>", $responseResultId);

        if($success) 
        {
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $error_code = $resultJsonArr['Code'];
            $result['reason_id'] = $this->getReason($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $data = array(
                "transaction_type" => self::WITHDRAWAL_TRANSACTION,
                "external_transaction_id" => $transfer_secure_id,
                "callback_method" => "processResultForWithdrawFromGame",
                "game_username" => $gameUsername,
                "player_id" => $playerId,
                "amount" => $amount,
                "api_url" => self::API_withdrawFromGame,
                "product_code" => $this->default_product_code
            );

        return $this->init_fund_transaction($data);
    }

    public function processResultForWithdrawFromGame($params) 
    {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
        );

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        if($success)
        {
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $error_code = $resultArr['Code'];
            $result['reason_id'] = $this->getReason($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) 
    {
        $this->utils->debug_log('the extra------>', $extra);
        $playerName = $extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName'      => $extra['playerName'],
            'external_transaction_id' => $transactionId,
            'playerId' => $extra['playerId'],
        );

        $params = array(
            'MerchantCode' => $this->merchant_code,
            'PlayerId' => $gameUsername,
            'TransactionId' => $transactionId,
            'ProductWallet' => $this->default_product_code
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) 
    {
        $this->CI->utils->debug_log('##########  QUERY TRANSACTION  #####################');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForQueryTransaction ==========================>', $resultJsonArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN
        );

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        if($success) 
        {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token) 
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        
        //observer the date format
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

        $result = [
            "data_count" => 0,
            "data_count_insert" => 0,
            "data_count_update" => 0
        ];

        while($queryDateTimeMax > $queryDateTimeStart) 
        {
            $startDateParam=new DateTime($queryDateTimeStart);

            if($queryDateTimeEnd>$queryDateTimeMax)
            {
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }

            $endDateParamNew = $endDateParam->format('Y-m-d H:i:s');

            $startDateParam = $startDateParam->format('Y-m-d H.i.s');
            $endDateParam = $endDateParam->format('Y-m-d H.i.s');

            $page = 1;
            $perpage = 500;
            
            $ipm_product_codes = [
                self::IPM_CODE["SPORTS"]["PRODUCT_CODE"],
                self::IPM_CODE["ESPORTS"]["PRODUCT_CODE"]
            ];
    
            foreach($ipm_product_codes as $ipm_product_code)
            {
                if($ipm_product_code == self::IPM_CODE["ESPORTS"]["PRODUCT_CODE"])
                {
                    foreach($this->esportsbull_product_types as $key => $product_type) 
                    {
                        $record = $this->syncIpmGamelogs($startDateParam, $endDateParam, $perpage, $page, $ipm_product_code, self::IPM_CODE["ESPORTS"]["DATE_FILTER_TYPE"], $product_type);

                        if(!empty($record["result"]["data_count"]))
                        {
                            $this->CI->utils->debug_log("syncOriginalGameLogs DateFrom: {$startDateParam} --- DateTo: {$endDateParam}");
                            $result["data_count"] += $record["result"]["data_count"];
                            $result["data_count_insert"] += $record["result"]["data_count_insert"];
                            $result["data_count_update"] += $record["result"]["data_count_update"];
                        }else{
                            $result["data_count"] += 0;
                            $result["data_count_insert"] += 0;
                            $result["data_count_update"] += 0;
                        }
                    }
                }else{
                    $record = $this->syncIpmGamelogs($startDateParam, $endDateParam, $perpage, $page, $ipm_product_code, self::IPM_CODE["SPORTS"]["DATE_FILTER_TYPE"]);

                    if(!empty($record["result"]["data_count"]))
                    {
                        $this->CI->utils->debug_log("syncOriginalGameLogs DateFrom: {$startDateParam} --- DateTo: {$endDateParam}");
                        $result["data_count"] += $record["result"]["data_count"];
                        $result["data_count_insert"] += $record["result"]["data_count_insert"];
                        $result["data_count_update"] += $record["result"]["data_count_update"];
                    }else{
                        $result["data_count"] += 0;
                        $result["data_count_insert"] += 0;
                        $result["data_count_update"] += 0;
                    }
                }
            }
            
            $queryDateTimeStart = $endDateParamNew;

            $this->CI->utils->info_log(__METHOD__ . " >>>>> Please wait {$this->sleep_time} {$this->CI->utils->pluralize('second', 'seconds', $this->sleep_time)} until the sleep time is finished.", "sleep_time", $this->sleep_time . " " . $this->CI->utils->pluralize('second', 'seconds', $this->sleep_time));
            sleep($this->sleep_time);
            //var_dump($queryDateTimeStart);die();
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return array("success"=>true, "sync_details" => $result);
    }

    public function syncIpmGamelogs($startDateParam, $endDateParam, $perpage = null , $page, $product_code, $dateFilterType, $product_type = null)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDateStr' => $startDateParam,
            'endDateStr' => $endDateParam,
        );

        if(!empty($product_type))
        {
            $params = array(
                'MerchantCode'  => $this->merchant_code,
                'StartDate' => $startDateParam,
                'EndDate'   => $endDateParam,
                'Page' => $page,
                'ProductWallet' => $product_code,
                'Currency' => $this->currency,
                'DateFilterType' => $dateFilterType,
                'Language' => 'EN',
                'Product' => $product_type
            );
        }else{
            $params = array(
                'MerchantCode'  => $this->merchant_code,
                'StartDate' => $startDateParam,
                'EndDate'   => $endDateParam,
                'Page' => $page,
                'ProductWallet' => $product_code,
                'Currency' => $this->currency,
                'DateFilterType' => $dateFilterType,
                'Language' => 'EN'
            );
        }

        $this->CI->utils->debug_log('try load game logs syncOriginalGameLogs', $startDateParam, $endDateParam, $params);

        return $this->callApi(self::API_queryGameRecords, $params, $context);
    }

    public function processResultForSyncOriginalGameLogs($params) 
    {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);

        if(isset($resultJsonArr['Result']))
        {
            $gameRecords = $resultJsonArr['Result'] === array_values($resultJsonArr['Result']) ? $resultJsonArr['Result'] : array($resultJsonArr['Result']);
        }else{
            $gameRecords = array();
        }

        $result = [
            'data_count' => 0,
            "data_count_insert" => 0,
			"data_count_update" => 0
        ];
        
        if($success && !empty($gameRecords)) 
        {
            if(!empty($gameRecords))
            {
                # reprocess Game records
                $extra = ['responseResultId' => $responseResultId];
                $this->rebuildIPMGameRecords($gameRecords, $extra);
                
                $this->CI->utils->debug_log('IPM ESPORTS processResultForSyncOriginalGameLogs $gameRecords', $gameRecords);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_GAME_LOGS_TABLE,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

                $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

                if(!empty($insertRows)) 
                {
                    $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', ['responseResultId' => $responseResultId]);
                }

                unset($insertRows);

                if(!empty($updateRows)) 
                {
                    $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', ['responseResultId' => $responseResultId]);
                }

                unset($updateRows);
            }
        }

        $result['total_page'] = isset($resultJsonArr['Pagination']['TotalPage']) ? $resultJsonArr['Pagination']['TotalPage'] : 1;

        $dataResult = [
            "result" => $result
        ];

        return array(true, $dataResult);
    }

    public function rebuildIPMGameRecords(&$gameRecords, $extra)
    {
        foreach($gameRecords as $index => $record) 
        {
            $data['Provider'] = isset($record['Provider']) ? $record['Provider'] : null;
            $data['GameId'] = isset($record['GameId']) ? $record['GameId'] : null;
            $data['WagerCreationDateTime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WagerCreationDateTime'])));
            $data['LastUpdatedDate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['LastUpdatedDate'])));
            $data['PlayerId'] = isset($record['PlayerId']) ? $record['PlayerId'] : null;
            $data['ProviderPlayerId'] = isset($record['ProviderPlayerId']) ? $record['ProviderPlayerId'] : null;
            $data['OperatorName'] = isset($record['OperatorName']) ? $record['OperatorName'] : null;
            $data['ProviderOperatorId'] = isset($record['ProviderOperatorId']) ? $record['ProviderOperatorId'] : 0;
            $data['Currency'] = isset($record['Currency']) ? $record['Currency'] : null;
            $data['StakeAmount'] = isset($record['StakeAmount']) ? str_replace(',', '', $record['StakeAmount']) : 0;
            $data['Turnover'] = isset($record['Turnover']) ? str_replace(',', '', $record['Turnover']) : 0;
            $data['MemberExposure'] = isset($record['MemberExposure']) ? $record['MemberExposure'] : 0;
            $data['PayoutAmount'] = isset($record['PayoutAmount']) ? $record['PayoutAmount'] : 0;
            $data['WinLoss'] = isset($record['WinLoss']) ? str_replace(',', '', $record['WinLoss']) : 0;
            $data['ResultStatus'] = isset($record['ResultStatus']) ? $record['ResultStatus'] : null;
            $data['OddsType'] = isset($record['OddsType']) ? $record['OddsType'] : null;
            $data['TotalOdds'] = isset($record['TotalOdds']) ? $record['TotalOdds'] : 0;
            $data['WagerType'] = isset($record['WagerType']) ? $record['WagerType'] : null;
            $data['Platform'] = isset($record['Platform']) ? $record['Platform'] : null;
            $data['IsSettled'] = isset($record['IsSettled']) ? $record['IsSettled'] : 0;
            $data['IsResettled'] = isset($record['IsResettled']) ? $record['IsResettled'] : 0;
            $data['IsConfirmed'] = isset($record['IsConfirmed']) ? $record['IsConfirmed'] : 0;
            $data['IsCancelled'] = isset($record['IsCancelled']) ? $record['IsCancelled'] : 0;
            $data['BetTradeStatus'] = isset($record['BetTradeStatus']) ? $record['BetTradeStatus'] : null;
            $data['BetTradeCommission'] = isset($record['BetTradeCommission']) ? $record['BetTradeCommission'] : 0;
            $data['BetTradeBuyBackAmount'] = isset($record['BetTradeBuyBackAmount']) ? $record['BetTradeBuyBackAmount'] : 0;
            $data['ComboType'] = isset($record['ComboType']) ? $record['ComboType'] : null;
            $data['Confirmed'] = isset($record['Confirmed']) ? $record['Confirmed'] : null;
            $data['SettlementDateTime'] = isset($record['SettlementDateTime']) ? $record['SettlementDateTime'] : "0000-00-00 00:00:00";
            $data['Tolerance'] = isset($record['Tolerance']) ? $record['Tolerance'] : 0;
            $data['DetailItems'] = json_encode($record['DetailItems']);
            $data['SportsName'] = isset($record['DetailItems'][0]['SportsName']) ? $record['DetailItems'][0]['SportsName'] : date('ymd His');
            $data['response_result_id'] = $extra['responseResultId'];
            $data['external_uniqueid'] = $record['BetId'];
            $data['BetId'] = $record['BetId'];
            $data['betTradeSuccessDateTime'] = isset($record['betTradeSuccessDateTime']) ? $record['betTradeSuccessDateTime'] : null;

            $gameRecords[$index] = $data;
            unset($data);
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo = [])
    {
        $dataCount = 0;

        if(!empty($rows))
        {
            $responseResultId=$additionalInfo['responseResultId'];
            foreach($rows as $record) 
            {
                $record['last_sync_time'] = $this->utils->getNowForMysql();
                if($update_type=='update') 
                {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_GAME_LOGS_TABLE, $record);
                }else{
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_GAME_LOGS_TABLE, $record);
                }

                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }
    
    public function syncMergeToGameLogs($token) 
    {
        $enabled_game_logs_unsettle = true; 

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $original_game_logs_table = self::ORIGINAL_GAME_LOGS_TABLE;

        $sqlTime = "{$original_game_logs_table}.`updated_at` >= ? AND {$original_game_logs_table}.`updated_at` <= ?";

        if($use_bet_time)
        {
            $sqlTime = "{$original_game_logs_table}.`WagerCreationDateTime` >= ? AND {$original_game_logs_table}.`WagerCreationDateTime` <= ?";
        }

        $sql = <<<EOD
        SELECT 
          {$original_game_logs_table}.`id` AS sync_index,
          {$original_game_logs_table}.`PlayerId` AS gameUsername,
          {$original_game_logs_table}.`external_uniqueid`,
          {$original_game_logs_table}.`WagerCreationDateTime` as bet_time,
          {$original_game_logs_table}.`LastUpdatedDate` AS game_date,
          {$original_game_logs_table}.`SportsName` AS game_code,
          {$original_game_logs_table}.`response_result_id`,
          {$original_game_logs_table}.`PayoutAmount` AS payout_amount,
          {$original_game_logs_table}.`StakeAmount` AS bet_amount,
          {$original_game_logs_table}.`WinLoss` AS result_amount,
          {$original_game_logs_table}.`Platform` AS platform,
          {$original_game_logs_table}.`isSettled` AS is_settled,
          {$original_game_logs_table}.`isConfirmed` AS is_confirmed,
          {$original_game_logs_table}.`isCancelled` AS is_cancelled,
          {$original_game_logs_table}.`BetId` AS round_number,
          {$original_game_logs_table}.`SportsName` AS sports_name,
          {$original_game_logs_table}.`DetailItems` AS bet_details,
          {$original_game_logs_table}.`ResultStatus`,
          {$original_game_logs_table}.`updated_at`,
          {$original_game_logs_table}.`md5_sum`, 
          `game_provider_auth`.`player_id`,
          `game_description`.`id` AS game_description_id,
          `game_description`.`game_type_id`,
          `game_description`.`game_name` AS `game_description_name`

        FROM
          ({$original_game_logs_table}) 
          LEFT JOIN `game_description` ON {$original_game_logs_table}.SportsName = game_description.external_game_id AND game_description.game_platform_id = ? AND game_description.void_bet != 1 
          LEFT JOIN `game_type` ON game_description.game_type_id = game_type.id 
          JOIN `game_provider_auth` ON ({$original_game_logs_table}.`PlayerId` = `game_provider_auth`.`login_name` AND `game_provider_auth`.`game_provider_id` = ?) 
        WHERE {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if(isset($row["ResultStatus"]))
        {
            switch ($row["ResultStatus"]) {
                case self::RESULT_STATUS["WIN"]:
                    $note = "Win";
                    break;
                case self::RESULT_STATUS["LOSE"]:
                    $note = "Lose";
                    break;
                case self::RESULT_STATUS["DRAW"]:
                    $note = "Draw";
                    break;
                default:
                    $note = null;
                    break;
            }
        }else{
            $note = null;
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['gameUsername']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_time'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['bet_time'],
                'updated_at' => $row['game_date']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getGameRecordsStatus($row),
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $this->processGameBetDetail($row),
            'extra' => [
                'note' => $note
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_type_id'])) 
        {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = $this->getGameRecordsStatus($row);
    }

    public function getGameRecordsStatus($row) 
    {
        $status = Game_logs::STATUS_SETTLED;

        if($row['is_settled'] == self::SETTLED && $row['is_confirmed'] == self::Confirmed && $row['is_cancelled'] == self::NOT_CANCEL)
        {
            $status = Game_logs::STATUS_SETTLED;
        }else{
            if($row['is_settled'] == self::NOT_SETTLED)
            {
                $status = Game_logs::STATUS_PENDING;
            }
            
            if($row['is_confirmed'] == self::Cancelled && $row['is_cancelled'] == self::CANCEL)
            {
                $status = Game_logs::STATUS_CANCELLED;
            }
            
            if($row['is_confirmed'] == self::Confirmed && $row['is_cancelled'] == self::CANCEL)
            {
                $status = Game_logs::STATUS_CANCELLED;
            }
        }

        return $status;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) 
        {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id))
        {
            $gameDescId = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_description_id = $gameDescId;
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function processGameBetDetail($rowArray)
    {
        $bet_details =  array('sports_bet' => $this->setBetDetails($rowArray));

        $this->CI->utils->debug_log('=====> Bet Details return', $bet_details);

        return $bet_details;
    }

    public function setBetDetails($row)
    {
        $data = json_decode($row['bet_details'],true);
        $set = array();
        if(!empty($data))
        {
            foreach($data as $key => $game) 
            {
                $set[$key] = array(
                    'yourBet' => isset($game['bet_amount']) ? $game['bet_amount'] : 0,
                    'isLive' => null,
                    'odd' => isset($game['Odds']) ? $game['Odds'] : null,
                    'hdp'=> isset($game['Handicap']) ? $game['Handicap'] : null,
                    'htScore'=> null,
                    'eventName' => isset($game['EventName']) ? $game['EventName'] : null,
                    'league' => isset($game['CompetitionName']) ? $game['CompetitionName'] : null,
                );
            }
        }

        return $set;
    }

}


/*end of file*/
