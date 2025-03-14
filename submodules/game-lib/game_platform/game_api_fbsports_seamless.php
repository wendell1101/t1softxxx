<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_fbsports_seamless extends Abstract_game_api {

    const URI_MAP = array(
        self::API_createPlayer => '/fb/data/api/v2/new/user/create',
        self::API_queryForwardGame => '/fb/data/api/v2/token/get',
        self::API_queryDemoGame => '/fb/data/api/v2/service/domain/list',
        self::API_syncGameRecords => '/fb/data/api/v2/order/file/ids',
        'syncOrderFile' => '/fb/data/api/v2/order/list'
    );

    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = 'fbsports_seamless_wallet_transactions';
        $this->original_table = 'fbsports_seamless_wallet_game_records';
        $this->apiUrl = $this->getSystemInfo('url','https://sptapi.server.st-newsports.com');
        $this->channelId = $this->getSystemInfo('channelId','1798970435894046721');
        $this->secretKey = $this->getSystemInfo('secret','A3XsbO8rMP5Utbp7JU6XJBApi5WXJS3r');
        $this->oddsLevel = $this->getSystemInfo('oddsLevel');
        $this->currencyIds = $this->getSystemInfo('currencyIds', [13]);# 13 = PHP

        /* start splice params */
        $this->currencyId = $this->getSystemInfo('currencyId', 13);# 13 = PHP
        $this->themeBg = $this->getSystemInfo('themeBg');#{themeBgColor in data object (H5 default background color, if you want to pass your own hexadecimal color value through the URL without the "#" symbol, for example: '6D060D')}
        $this->themeText = $this->getSystemInfo('themeText');#{themeFgColor in data object (H5/PC default selected color, if you want to pass your own format through the URL, for example: '{"h5FgColor":"333333","pcFgColor":"888888","columnType":1}')}
        $this->platformName = $this->getSystemInfo('platformName', 'FB Sports');
        $this->icoUrl = $this->getSystemInfo('icoUrl');
        $this->handicap = $this->getSystemInfo('handicap');
        $this->color = $this->getSystemInfo('color', 'dark');
        $this->tutorialPop = $this->getSystemInfo('tutorialPop', '1');#（First-time login popup tutorial prompt: Pass 1 to disable, leave blank for default.

        #{The type parameter only supports values (1-Live, 3-Today, 4-Early Market, 5-Popular Leagues); sportId represents the ID of the sportId, and leagueId represents the ID of the leagueId. Please note that type and (sportId/leagueId) should be passed together. If type is 5, leagueId should be passed (optional).
        $this->type = $this->getSystemInfo('type');
        $this->sportId = $this->getSystemInfo('sportId');

        $this->controlMenu = $this->getSystemInfo('controlMenu');#1{Whether to collapse Today/Early sports without data. Example: 1 - Collapse by default, 2 - Expand by default, 3 - Hide sports without data (optional)}
        $this->noType = $this->getSystemInfo('noType');#{Hide the multilingual entry on the Facebook web page; multilingual options can be changed through URL language parameters. (optional)}

        /* end splice params */


        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_bet_detail_ui = $this->getSystemInfo('use_bet_detail_ui', true);
        $this->show_player_center_bet_details_ui = $this->getSystemInfo('show_player_center_bet_details_ui', true);
        $this->allow_launch_demo_without_authentication = $this->getSystemInfo('allow_launch_demo_without_authentication', true);
        $this->enable_sync_order_file = $this->getSystemInfo('enable_sync_order_file', false);

        $this->demo_ui_config = $this->getSystemInfo('demo_ui_config', [
            "themeBgColor" => "#E632B7",//amusino default color config
            "h5FgColor" => "#E632B7",
            "pcFgColor" => "#CA33A3"
        ]);
    }

    public function isSeamLessGame()
    {
        return true;
    }
    
	public function getPlatformCode(){
        return FBSPORTS_SEAMLESS_GAME_API;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = false;
        $successCode = 0;
        if(isset($resultArr['success'])){
            if(isset($resultArr['code']) && $resultArr['code'] == $successCode){
                $success = true;
            }
        }
        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('==> fbsports got error ==>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    /**
     * overview : generate url
     *
     * @param $apiName
     * @param $params
     * @return string
     */
    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->apiUrl . $apiUri;
        $this->CI->utils->debug_log('==> fbsports generateUrl : ' . $url);
        return $url;
    }

    /**
     * overview : custom http call
     *
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        $sign = $params['sign'];
        $timestamp = $params['timestamp'];
        unset($params['timestamp'], $params['sign']);
        ksort($params);
        $params = json_encode($params);
        $headers = array(
            "Content-Type: application/json",
            "sign: {$sign}",  
            "timestamp: {$timestamp}",
            "merchantId: {$this->channelId}"
        );
        $this->CI->utils->debug_log('==> fbsports customHttpCall params : ', $params);
        $this->CI->utils->debug_log('==> fbsports customHttpCall headers : ', $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    }

    private function generateSign($params){
        ksort($params);
        if(isset($params['timestamp'])){
            $timeStamp = $params['timestamp'];
        } else {
            $timeStamp = $this->utils->getTimestampNow() * 1000;
        }
        unset($params['timestamp']);

        $string  = json_encode($params).".".$this->channelId.".".$timeStamp.".".$this->secretKey;
        $this->CI->utils->debug_log('==> fbsports stringThatNeedsToBeSigned : ' . $string);
        $sign = md5($string);
        $this->CI->utils->debug_log('==> fbsports sign : ' . $sign);
        return $sign;
    }

    /**
     * overview : create player game
     *
     * @param $playerName
     * @param $playerId
     * @param $password
     * @param null $email
     * @param null $extra
     * @return array
     */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->CI->utils->debug_log('==> fbsports gameUsername: ' . $gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
        );

        $params = array(
            "merchantUserId" => $gameUsername,
            "currencyIds" => $this->currencyIds,
        );
        
        if($this->oddsLevel){
            $params['oddsLevel'] = $this->oddsLevel;
        }

        $params['timestamp'] = $this->utils->getTimestampNow()* 1000; #should be unset
        $params['sign'] = $this->generateSign($params);#should be unset
        $this->CI->utils->debug_log('==> fbsports createPlayer params: ' . json_encode($params));
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    /**
     * overview : process result for createPlayer
     *
     * @param $params
     * @return array
     */
    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $this->CI->utils->debug_log('==> fbsports processResultForCreatePlayer result: ' . json_encode($resultJsonArr));

        if ($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $resultJsonArr['exists'] = true;
        }

        return array($success, $resultJsonArr);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function queryDemoGame($extra){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'extra' => $extra,
            'guestMode' => true
        );

        $params = array(
            "platForm" => isset($extra['is_mobile']) && $extra['is_mobile'] ? "h5" : "pc",
            "ip" => $this->CI->input->ip_address()
        );

        $params['timestamp'] = $this->utils->getTimestampNow()* 1000; #should be unset
        $params['sign'] = $this->generateSign($params);#should be unset
        return $this->callApi(self::API_queryDemoGame, $params, $context);
    }

    /**
     * overview : query forward game
     *
     * @param $playerName
     * @param $extra
     * @return array
     */
    public function queryForwardGame($playerName, $extra) {
        $demo = isset($extra['game_mode']) && $extra['game_mode'] == 'real' ?  false : true;
        if(empty($playerName) || $demo){
            return $this->queryDemoGame($extra);
        }
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $this->getPlayerIdFromUsername($playerName),
            'extra' => $extra
        );

        $params = array(
            "merchantUserId" => $gameUsername,
            "platForm" => isset($extra['is_mobile']) && $extra['is_mobile'] ? "h5" : "pc",
            "ip" => $this->CI->input->ip_address()
        );

        $params['timestamp'] = $this->utils->getTimestampNow()* 1000; #should be unset
        $params['sign'] = $this->generateSign($params);#should be unset
        $this->CI->utils->debug_log('==> fbsports queryForwardGame params: ' . json_encode($params));
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    /**
     * overview : process result for queryForwardGame
     *
     * @param $params
     * @return array
     */
    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $extra = $this->getVariableFromContext($params, 'extra');
        $guestMode = $this->getVariableFromContext($params, 'guestMode');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $this->CI->utils->debug_log('==> fbsports processResultForQueryForwardGame result: ' . json_encode($resultJsonArr));

        $result = array("url" => null);
        if ($success) {
            #params from response
            $token = isset($resultJsonArr['data']['token']) ? $resultJsonArr['data']['token'] : null;
            $h5Src = isset($resultJsonArr['data']['serverInfo']['h5Address']) ? $resultJsonArr['data']['serverInfo']['h5Address'] : null;
            $pcSrc = isset($resultJsonArr['data']['serverInfo']['pcAddress']) ? $resultJsonArr['data']['serverInfo']['pcAddress'] : null;
            $apiSrc = isset($resultJsonArr['data']['serverInfo']['apiServerAddress']) ? $resultJsonArr['data']['serverInfo']['apiServerAddress'] : null;
            $pushSrc = isset($resultJsonArr['data']['serverInfo']['pushServerAddress']) ? $resultJsonArr['data']['serverInfo']['pushServerAddress'] : null;
            $virtualSrc = isset($resultJsonArr['data']['serverInfo']['virtualAddress']) ? $resultJsonArr['data']['serverInfo']['virtualAddress'] : null;

            #default params
            $nickname  = $gameUsername;
            $language = $this->getLanguage($extra['language']);
            $isMobile = isset($extra['is_mobile']) && $extra['is_mobile'] ? true : false;
            $demo = isset($extra['game_mode']) && $extra['game_mode'] == 'real' ?  false : true;
            $themeBgColor = isset($resultJsonArr['data']['themeBgColor']) ? substr($resultJsonArr['data']['themeBgColor'], 1) : null;
            $themeFgColorArray = isset($resultJsonArr['data']['themeFgColor']) ? json_decode($resultJsonArr['data']['themeFgColor'],true) : null;
            $h5FgColor = isset($themeFgColorArray['h5FgColor']) ? substr($themeFgColorArray['h5FgColor'], 1) : null;
            $pcFgColor = isset($themeFgColorArray['pcFgColor']) ? substr($themeFgColorArray['pcFgColor'], 1) : null;
            if($demo){
                $token = "guestMode";
                $h5FgColor = isset($this->demo_ui_config['h5FgColor']) ? $this->demo_ui_config['h5FgColor']: null;
                $pcFgColor = isset($this->demo_ui_config['pcFgColor']) ? $this->demo_ui_config['pcFgColor']: null;
                $themeBgColor = isset($this->demo_ui_config['themeBgColor']) ? $this->demo_ui_config['themeBgColor']: null;
            }

            $themeFgColor = $isMobile ? $h5FgColor : $pcFgColor;

            if($guestMode){
                if(isset($resultJsonArr['data']) && !empty($resultJsonArr['data'])){
                    $data = $resultJsonArr['data'];
                    $apiKey = array_search('1', array_column($data, 'type'));
                    $pushKey = array_search('2', array_column($data, 'type'));
                    $h5Key = array_search('3', array_column($data, 'type'));
                    $pcKey = array_search('4', array_column($data, 'type'));
                    
                    $h5Src = isset($data[$h5Key]['domainList']['0']['domain']) ? $data[$h5Key]['domainList']['0']['domain'] : null;
                    $pcSrc = isset($data[$pcKey]['domainList']['0']['domain']) ? $data[$pcKey]['domainList']['0']['domain'] : null;
                    $apiSrc = isset($data[$apiKey]['domainList']['0']['domain']) ? $data[$apiKey]['domainList']['0']['domain'] : null;
                    $pushSrc = isset($data[$pushKey]['domainList']['0']['domain']) ? $data[$pushKey]['domainList']['0']['domain'] : null;
                    $token = "guestMode";
                }
            }

            $mainSrc = $isMobile ? $h5Src : $pcSrc;
            $params = array(
                "token" => $token,
                "nickname" => $nickname,
                "apiSrc" => $apiSrc,
                "virtualSrc" => $virtualSrc,
                "pushSrc" => $pushSrc,
                "language" => $language
            );

            if($this->platformName){
                $params['platformName'] = $this->platformName;
            }

            if($this->icoUrl){
                $params['icoUrl'] = $this->icoUrl;
            }

            if($this->handicap){
                $params['handicap'] = $this->handicap;
            }

            if($this->color){
                $params['color'] = $this->color;
            }

            if($this->currencyId){
                $params['currencyId'] = $this->currencyId;
            }

            if(!empty($themeBgColor)){
                $params['themeBg'] = $themeBgColor;
            }

            if(!empty($themeFgColor)){
                $params['themeText'] = $themeFgColor;
            }

            if($this->type){
                $params['type'] = $this->type;
            }

            if($this->sportId){
                $params['sportId'] = $this->sportId;
            }

            if($this->controlMenu){
                $params['controlMenu'] = $this->controlMenu;
            }

            if($this->noType){
                $params['noType'] = $this->noType;
            }

            if($this->tutorialPop){
                $params['tutorialPop'] = $this->tutorialPop;
            }

            $params = http_build_query($params);
            $urlSrc = "{$mainSrc}/index.html#/?{$params}";
            $this->CI->utils->debug_log('==> fbsports processResultForQueryForwardGame: '. $urlSrc);

            $result['url'] = $urlSrc;
        }

        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            case 'zh-CN':
                $language = 'CMN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            case 'id-ID':
                $language = 'IND';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            case 'vi-VN':
                $language = 'VIE';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            case 'ko-KR':
                $language = 'KOR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
            case 'th-TH':
                $language = 'THA';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
            case 'pt-PT':
                $language = 'BRA';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            case Language_function::INT_LANG_PORTUGUESE :
            case 'hi-IN':
                $language = 'HIN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_SPANISH:
            case 'es-ES':
                $language = 'SPA';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
            case 'ja-JP':
                $language = 'JPN';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_ARAB:
                // $language = 'SAU';
                break;
            default:
                $language = 'ENG';
                break;
        }
        return $language;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getTransactionsTable(){
        return $this->original_transaction_table; 
    }

    public function syncOriginalGameLogs($token = false) {
        if(!$this->enable_sync_order_file){
            return $this->returnUnimplemented();
        }
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $params = array(
            "startTime" => strtotime($queryDateTimeStart)*1000,
            "endTime" => strtotime($queryDateTimeEnd)*1000,
        );

        $params['timestamp'] = $this->utils->getTimestampNow()* 1000; #should be unset
        $params['sign'] = $this->generateSign($params);#should be unset
        $this->CI->utils->debug_log('==> fbsports syncOriginalGameLogs params: ' . json_encode($params));
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $orderDataList = [];
        $result = ['data_count' => 0];
        if($success){
            $data = isset($resultJsonArr['data']) ? $resultJsonArr['data'] : [];
            if(!empty($data)){
                foreach ($data as $key => $datai) {
                    $fileId = $datai['fileId'];
                    $fileData = $this->syncOrderFile($fileId);
                    $orderData = isset($fileData['data']) ?  $fileData['data'] : [];
                    if(!empty($orderData)){
                        foreach ($orderData as $orderDatai) {
                            $orderDataList[] = $orderDatai;
                        }
                    }
                }
            }
            if(!empty($orderDataList)){
                $this->preProcessOrder($orderDataList);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'fbsports_seamless_wallet_game_records',
                    $orderDataList,
                    'external_uniqueid',
                    'external_uniqueid',
                    ['orderStatus', 'version', 'lastModifyTime', 'createTime', 'cancelTime', 'settleTime', 'external_gameid'],
                    'md5_sum',
                    'id',
                    ['settleAmount']
                );

                $this->CI->utils->debug_log('after process available rows', count($orderDataList), count($insertRows), count($updateRows));

                unset($orderDataList);

                if (!empty($insertRows)){
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($insertRows);

                if (!empty($updateRows)){
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($updateRows);
            }
        }
        return array($success, $result);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('fbsports_seamless_wallet_game_records', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('fbsports_seamless_wallet_game_records', $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncOrderFile($fileId){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOrderFile',
        );

        $params = array(
            "fileId" =>$fileId,
        );

        $params['timestamp'] = $this->utils->getTimestampNow()* 1000; #should be unset
        $params['sign'] = $this->generateSign($params);#should be unset
        $this->CI->utils->debug_log('==> fbsports syncOrderFile params: ' . json_encode($params));
        return $this->callApi('syncOrderFile', $params, $context);
    }

    public function processResultForSyncOrderFile($params){
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $orderData = isset($resultJsonArr['data']) ? $resultJsonArr['data'] : [];
        return array($success, $resultJsonArr);
    }

    private function preProcessOrder(&$orderDataList){
        if(!empty($orderDataList)){
            foreach($orderDataList as $index => $record) {
                $params = $record;
                $params['external_uniqueid'] = $params['orderId'] = isset($params['id']) ? $params['id'] : null;
                $dataToInsert = array(
                    "cashOutOrderId" => isset($params['cashOutOrderId']) ? $params['cashOutOrderId'] : null,
                    "orderId" => isset($params['orderId']) ? $params['orderId'] : null,
                    "rejectReason" => isset($params['rejectReason']) ? $params['rejectReason'] : null,
                    "rejectReasonStr" => isset($params['rejectReasonStr']) ? $params['rejectReasonStr'] : null,
                    "userId" => isset($params['userId']) ? $params['userId'] : null,
                    "merchantId" => isset($params['merchantId']) ? $params['merchantId'] : null,
                    "merchantUserId" => isset($params['merchantUserId']) ? $params['merchantUserId'] : null,
                    "currency" => isset($params['currency']) ? $params['currency'] : null,
                    "exchangeRate" => isset($params['exchangeRate']) ? $params['exchangeRate'] : null,
                    "seriesType" => isset($params['seriesType']) ? $params['seriesType'] : null,
                    "betType" => isset($params['betType']) ? $params['betType'] : null,
                    "allUp" => isset($params['allUp']) ? $params['allUp'] : null,
                    "allUpAlive" => isset($params['allUpAlive']) ? $params['allUpAlive'] : null,
                    "orderStakeAmount" => isset($params['orderStakeAmount']) ? $params['orderStakeAmount'] : null,
                    "stakeAmount" => isset($params['stakeAmount']) ? $params['stakeAmount'] : null,
                    "liabilityStake" => isset($params['liabilityStake']) ? $params['liabilityStake'] : null,
                    "settleAmount" => isset($params['settleAmount']) ? $params['settleAmount'] : null,
                    "orderStatus" => isset($params['orderStatus']) ? $params['orderStatus'] : null,
                    "payStatus" => isset($params['payStatus']) ? $params['payStatus'] : null,
                    "oddsChange" => isset($params['oddsChange']) ? $params['oddsChange'] : null,
                    "device" => isset($params['device']) ? $params['device'] : null,
                    "ip" => isset($params['ip']) ? $params['ip'] : null,
                    "cashoutTime" => isset($params['cashoutTime']) ? $params['cashoutTime'] : null,
                    "betTime" => isset($params['betTime']) ? $params['betTime'] : null,
                    "settleTime" => isset($params['settleTime']) ? date('Y-m-d H:i:s', $params['settleTime']/1000) : null,
                    "createTime" => isset($params['createTime']) ? date('Y-m-d H:i:s', $params['createTime']/1000) : null,
                    "modifyTime" => isset($params['modifyTime']) ? date('Y-m-d H:i:s', $params['modifyTime']/1000) : null,
                    "cancelTime" => isset($params['cancelTime']) ? date('Y-m-d H:i:s', $params['cancelTime']/1000) : null,
                    "lastModifyTime" => isset($params['lastModifyTime']) ? date('Y-m-d H:i:s', $params['lastModifyTime']/1000) : null,
                    "remark" => isset($params['remark']) ? $params['remark'] : null,
                    "thirdRemark" => isset($params['thirdRemark']) ? $params['thirdRemark'] : null,
                    "relatedId" => isset($params['relatedId']) ? $params['relatedId'] : null,
                    "maxWinAmount" => isset($params['maxWinAmount']) ? $params['maxWinAmount'] : null,
                    "loseAmount" => isset($params['loseAmount']) ? $params['loseAmount'] : null,
                    "rollBackCount" => isset($params['rollBackCount']) ? $params['rollBackCount'] : null,
                    "itemCount" => isset($params['itemCount']) ? $params['itemCount'] : null,
                    "seriesValue" => isset($params['seriesValue']) ? $params['seriesValue'] : null,
                    "betNum" => isset($params['betNum']) ? $params['betNum'] : null,
                    "cashOutTotalStake" => isset($params['cashOutTotalStake']) ? $params['cashOutTotalStake'] : null,
                    "cashOutStake" => isset($params['cashOutStake']) ? $params['cashOutStake'] : null,
                    "liabilityCashoutStake" => isset($params['liabilityCashoutStake']) ? $params['liabilityCashoutStake'] : null,
                    "cashOutPayoutStake" => isset($params['cashOutPayoutStake']) ? $params['cashOutPayoutStake'] : null,
                    "acceptOddsChange" => isset($params['acceptOddsChange']) ? $params['acceptOddsChange'] : null,
                    "reserveId" => isset($params['reserveId']) ? $params['reserveId'] : null,
                    "cashOutCount" => isset($params['cashOutCount']) ? $params['cashOutCount'] : null,
                    "unitStake" => isset($params['unitStake']) ? $params['unitStake'] : null,
                    "reserveVersion" => isset($params['reserveVersion']) ? $params['reserveVersion'] : null,
                    "betList" => isset($params['betList']) ? json_encode($params['betList']) : null,
                    "maxStake" => isset($params['maxStake']) ? $params['maxStake'] : null,
                    "validSettleStakeAmount" => isset($params['validSettleStakeAmount']) ? $params['validSettleStakeAmount'] : null,
                    "validSettleAmount" => isset($params['validSettleAmount']) ? $params['validSettleAmount'] : null,
                    "cashOutCancelStake" => isset($params['cashOutCancelStake']) ? $params['cashOutCancelStake'] : null,
                    "cancelReasonCode" => isset($params['cancelReasonCode']) ? $params['cancelReasonCode'] : null,
                    "cancelCashOutAmountTo" => isset($params['cancelCashOutAmountTo']) ? $params['cancelCashOutAmountTo'] : null,
                    "unitCashOutPayoutStake" => isset($params['unitCashOutPayoutStake']) ? $params['unitCashOutPayoutStake'] : null,
                    "walletType" => isset($params['walletType']) ? $params['walletType'] : null,
                    "version" => isset($params['version']) ? $params['version'] : null,
                    "request_id" => $this->utils->getRequestId(), 
                    "external_uniqueid" => isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null,
                    "md5_sum" => isset($params['md5_sum']) ? $params['md5_sum'] : null,
                    "external_gameid" => isset($params['betType']) ? $params['betType'] : null,
                );
                
                if($params['betType'] == "1x1*1"){
                    if(isset($params['betList'][0]['sportId'])){ #try get sport id if single level
                        $dataToInsert['external_gameid'] = $params['betList'][0]['sportId'];
                    }
                } else {
                    $dataToInsert['external_gameid'] = "parlay";
                }
                $orderDataList[$index] = $dataToInsert;
                unset($data);
                ##end loop
            }
        }
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

      /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $tableName = $this->original_table;
        $sqlTime="fb.updated_at >= ? AND fb.updated_at <= ?";
        if($use_bet_time){
            $sqlTime="fb.createTime >= ? AND fb.createTime <= ?";
        }

        $sql = <<<EOD
SELECT
fb.id as sync_index,
fb.orderId as external_uniqueid,
fb.orderId as round_number,
fb.md5_sum,
fb.stakeAmount,
fb.settleAmount,
fb.cashOutPayoutStake,
fb.external_gameid,
fb.createTime,
fb.settleTime,
fb.modifyTime,
fb.lastModifyTime,
fb.orderStatus as status,
fb.betList,
fb.version,
gp.player_id,
gp.login_name,
gd.english_name,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as fb
LEFT JOIN game_provider_auth as gp ON fb.merchantUserId = gp.login_name and gp.game_provider_id=?
LEFT JOIN game_description as gd ON fb.external_gameid = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('==> fbsports queryOriginalGameLogs sql', $sql, $params);

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $results;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = $this->getStatus($row['status']);
    }

    private function getStatus($orderStatus){
        switch ($orderStatus) {
            case '5':#Settled
                return Game_logs::STATUS_SETTLED;
                break;
            case '2':#Rejected
                return Game_logs::STATUS_REJECTED;
                break;
            case '3':#Canceled
                return Game_logs::STATUS_CANCELLED;
                break;
            
            default:# Created|Confirming|Confirmed
                return Game_logs::STATUS_PENDING;
                break;
        }
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'version', 'lastModifyTime', 'external_gameid'],
                ['settleAmount']);
        }

        $settleAmount = !empty($row['settleAmount']) ? $row['settleAmount'] : 0;
        $stakeAmount = !empty($row['stakeAmount']) ? $row['stakeAmount'] : 0;
        $cashOutPayoutStake = !empty($row['cashOutPayoutStake']) ? $row['cashOutPayoutStake'] : 0;
        $winloss = ($settleAmount + $cashOutPayoutStake) - $stakeAmount;
        $createTime = $row['createTime'];
        $settleTime = $row['settleTime'];
        $modifyTime = $row['modifyTime'];
        $betDetails = json_decode($row['betList'], true);
        $prefix = $this->getSystemInfo('prefix_for_username');
        $gameUsername = $row['login_name'];

        $data =  [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['external_gameid'],
                'game_type' => null,
                'game' => $row['external_gameid'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => $stakeAmount,
                'result_amount' => $winloss,
                'bet_for_cashback' => $stakeAmount,
                'real_betting_amount' => $stakeAmount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $createTime,
                'end_at' => !empty($settleTime) ? $settleTime : $modifyTime,
                'bet_at' => $createTime,
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => $row['sync_index'],
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => $betDetails,
            'extra' => [
                'odds' => isset($betDetails[0]['odds']) ? $betDetails[0]['odds'] : null,
            ],
            'additional_details' => [
                'GAMEPROVIDER' => 'FBSPORTS',
                'SESSIONID' => $row['sync_index'],
                'TRANSACTIONID' => $row['external_uniqueid'],
                'GAMENAME' => $row['english_name'],
                'OUTLET' => '',
                'PLAYERACCOUNT' =>  (strpos($gameUsername, $prefix) === 0) ?  substr($gameUsername, strlen($prefix)) : $gameUsername,
                'PLAYERTYPE' => 'ONLINE',
                'GAMEDATE' => $createTime,
                'TOTALSTAKES' => $stakeAmount,
                'TOTALWINS' => $settleAmount + $cashOutPayoutStake,
                'PC1' => '',
                'PC2' => '',
                'PC3' => '',
                'PC4' => '',
                'PC5' => '',
                'JW1' => '',
                'JW2' => '',
                'JW3' => '',
                'JW4' => '',
                'JW5' => '',
                'UPDATEDATETIME' => !empty($settleTime) ? $settleTime : $modifyTime,
            ],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        return $data;
    }


    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['external_gameid'];
        $external_game_id = $row['external_gameid'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

     /**
     * queryTransactionByDateTime
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactionByDateTime($dateFrom, $dateTo){
        $tableName = $this->getTransactionsTable();
        $incType = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
        $decType = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
        $sqlTime="fb.created_at >= ? AND fb.created_at <= ?";

        $sql = <<<EOD
SELECT
fb.id as sync_index,
fb.external_uniqueid as external_uniqueid,
fb.business_id as round_no,
fb.method as trans_type,
ABS(fb.amount) as amount,
fb.md5_sum,
if(fb.transaction_type = "OUT", "{$decType}", "{$incType}") as transaction_type,
fb.created_at as transaction_date,
fb.before_balance,
fb.after_balance,
fb.player_id

FROM {$tableName} as fb
where
{$sqlTime}
EOD;

        
        $params=[
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('==> fbsports queryTransactions sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        #instead query unsettled,query settled to check if have settlement
        $sqlTime='fb.updated_at >= ? AND fb.updated_at <= ? and fb.settleAmount> 0 and fb.settleTime is not null';
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
fb.orderId as round_id, 
fb.orderId as transaction_id, 
fb.createTime as transaction_date,
fb.settleAmount as added_amount,
fb.stakeAmount as deducted_amount,
fb.external_uniqueid,
fb.orderStatus as order_status,
gp.player_id,
gd.id as game_description_id,
gd.game_type_id,
{$this->getPlatformCode()} as game_platform_id

from fbsports_seamless_wallet_game_records as fb
LEFT JOIN game_provider_auth as gp ON fb.merchantUserId = gp.login_name and gp.game_provider_id=?
LEFT JOIN game_description as gd ON fb.external_gameid = gd.external_game_id and gd.game_platform_id=?
where
{$sqlTime}
EOD;


        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('==> fbsports getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $results;
    }

    public function checkBetStatus($row){
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $businessId = $row['external_uniqueid'];
            $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom('fbsports_seamless_wallet_transactions', ['business_id'=> $businessId,  'method' => 'sync_transaction']);
            if(!$payoutExist){
                $row['transaction_type'] = "sync_orders";
                $row['amount'] = $row['added_amount'];
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                unset($row['order_status']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('FBSPORTS SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom('fbsports_seamless_wallet_transactions', ['business_id'=> $external_uniqueid,  'method' => 'sync_transaction']);
        if($payoutExist){
            return array('success'=>true, 'status'=> Game_logs::STATUS_SETTLED);
        }
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}