<?php
/**
 * Ray Gaming Integration
 * OGP-14597
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_tpg extends Abstract_game_api {
    private $transaction_type_deposit;

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';

    const TRANSACTION_DEPOSIT = 1;
    const TRANSACTION_WITHDRAW = 2;

    const REASON_TRANSACTION_DENIED = 2;
    const REASON_DUPLICATE_TRANSFER = 40;
    const REASON_NO_ENOUGH_BALANCE = 42;

    const MD5_FIELDS_FOR_ORIGINAL = [
        'total_deduct_amount',
        'bet_per_line',
        'total_bet_multiplier',
        'is_free_spin',
        'freespin_won_count',
        'line_payout',
        'winning_lines_position',
        'has_wild_symbol',
        'winning_symbols',
        'original_payouts',
        'freespin_payout_multiplier',
        'total_payout_amount',
        'transaction_type',
        'bet_amount',
        'payout_amount',
        'round_id',
        'room_rate',
        'jackpot_commission',
        'jackpot_commission_rate',
        'fish_caught',
        'fish_url',
        'bet_line_count',
        'booster_price',
        'winning_lines',
        'minigame_won',
        'winning_lines',
        'match_count',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'total_deduct_amount',
        'total_payout_amount',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'total_deduct_amount',
        'total_payout_amount',
        'start_at',
        'bet_at',
        'round_number',
        'game_code',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'total_deduct_amount',
        'total_payout_amount',
    ];

    const API_playerLogin = 'playerLogin';
    public $original_table;
    public $api_url;
    public $operator_id;
    public $currency;
    public $method;
    public $launch_url;
    public $language;
    public $mobile_app_launch_url;
    public $use_fishing_app;
    protected $offset;
    protected $pageLimit;
    protected $syncSleepTime;

    public function __construct() {
        parent::__construct();
        $this->original_table = "tpg_game_logs";
        $this->api_url = $this->getSystemInfo('url','https://stagingapi.triple-pg.com');
        $this->operator_id = $this->getSystemInfo('operator_id','137');
        $this->currency = $this->getSystemInfo('currency','THB');
        $this->launch_url = $this->getSystemInfo('launch_url','https://stagingweblobby.triple-pg.com/game/direct2Game');
        $this->language = $this->getSystemInfo('language','th-th');
        $this->mobile_app_launch_url = $this->getSystemInfo('mobile_app_launch_url', 'test://bpfishing.com');
        $this->use_fishing_app =  $this->getSystemInfo('use_fishing_app', 'false');
        $this->method = self::METHOD_GET;
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->offset = $this->getSystemInfo('offset',0);
        $this->pageLimit = $this->getSystemInfo('pageLimit',100);
        $this->syncSleepTime = $this->getSystemInfo('syncSleepTime',5);
        $this->data_url = $this->getSystemInfo('data_url', $this->api_url);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);

        $this->URI_MAP = array(
            self::API_createPlayer => '/game/GetGameToken',
            self::API_login => '/game/GetGameToken',
            self::API_isPlayerExist => '/game/PlayerStatus',
            self::API_queryPlayerBalance => '/game/GetPlayerGameBalance',
            self::API_depositToGame => '/game/FundTransfer',
            self::API_withdrawFromGame => '/game/FundTransfer',
            self::API_syncGameRecords => '/NewGetBatchTxnHistory',
            self::API_queryTransaction => '/GetFundTransferHistory',
            self::API_playerLogin => '/game/PlayerLogin',
            self::API_queryBetDetailLink => '/GetTxnHistoryInHTML',
        );

    }

    public function getPlatformCode(){
        return TPG_API;
    }

    protected function customHttpCall($ch, $params) {
        if($this->method == self::METHOD_POST){
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
    }

    public function generateUrl($apiName, $params) {
        $endPoint = $this->URI_MAP[$apiName];
        $url = $this->api_url . $endPoint;

        if($apiName==self::API_syncGameRecords){
            $url = $this->data_url . $endPoint;
        }

        if($this->method == self::METHOD_GET) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    public function processResultBoolean($response_result_id, $result_array, $player_name = null, $apiName = null) {
        $success = ($result_array['status'] == 1 || $result_array['status'] == 'Active') ? true : false;

        if($apiName==self::API_syncGameRecords && isset($result_array['status']) && $result_array['status']==63){
            //just no data
            $success = true;
        }

        if(!$success){
            $this->setResponseResultToError($response_result_id);
            $this->CI->utils->debug_log('---------- TPG Process Result Boolean False ----------', $response_result_id, 'player_name', $player_name, 'result', $result_array);
        }

        return $success;
    }

    public function loginOrCreate($player_name, $method = self::API_createPlayer) {

        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $player_id = $this->CI->player_model->getPlayerIdByUsername($player_name);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'player_name' => $player_name,
            'playerId' => $player_id,
        );

        $data = [
            'operatorId' => $this->operator_id,
            'playerName' => $game_username,
            'displayName' => $game_username,
            'currency' => $this->currency,
            'loginIp' => $this->CI->utils->getIP()
        ];

        $this->method = self::METHOD_GET;
        $this->CI->utils->debug_log('---------- TPG login ----------', $data);

        return $this->callApi($method, $data, $context);
    }

    public function processResultForLogin($params){
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);

        if($success){
            $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
            $result['token'] = $array_result['message'];
        }

        $this->CI->utils->debug_log('---------- TPG result for login ----------', $array_result);
        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        return $this->loginOrCreate($playerName);
    }

    public function isPlayerExist($player_name) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($player_name);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $player_name,
        );

        $data = [
            'operatorId' => $this->operator_id,
            'playerName' => $gameUsername,
            'actionType' => 'CHECK_STATUS'
        ];

        $this->CI->utils->debug_log('---------- TPG isPlayerExist ----------', $data);

        return $this->callApi(self::API_isPlayerExist, $data, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $player_name = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $player_name);
        $result = ['exists' => false];

        if($success) {
            $result['exists'] = true;
        }
        $this->CI->utils->debug_log('---------- TPG result for isPlayerExist ----------', $arrayResult);
        return array($success, $result);
    }

    public function queryPlayerBalance($player_name) {
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $playerId = $this->getPlayerIdInGameProviderAuth($game_username);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $player_name,
        );

        $data = [
            'operatorId' => $this->operator_id,
            'playerName' => $game_username
        ];

        $this->CI->utils->debug_log('---------- TPG queryPlayerBalance ----------', $data);

        return $this->callApi(self::API_queryPlayerBalance, $data, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $player_name = $this->getVariableFromContext($params, 'playerName');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);
        $result = [];

        if($success) {
            $result["balance"] = $this->convertAmountToDB($array_result['playerBalance']);
        }
        $this->CI->utils->debug_log('---------- TPG result for queryPlayerBalance ----------', $array_result);
        return array($success, $result);
    }

    public function depositOrWithdraw($player_name, $amount, $transfer_secure_id, $transaction_type) {
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $order_id = $transfer_secure_id;
        if($order_id == null) {
            $order_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositOrWithdraw',
            'playerName' => $player_name,
            'external_transaction_id'=> $order_id
        );

        $data = [
            'operatorId' => $this->operator_id,
            'username' => $game_username,
            'displayName' => $game_username,
            'currency' => $this->currency,
            'amount' => $amount,
            'transferType' => $transaction_type,
            'transactionId' => $order_id,
            'clientIp' => $this->CI->utils->getIP()

        ];
        $this->CI->utils->debug_log('---------- TPG params for depositOrWithdraw ----------', $data);

        $this->method = self::METHOD_POST;
        if($transaction_type == self::TRANSACTION_DEPOSIT) {
            $this->transaction_type_deposit = self::TRANSACTION_DEPOSIT;
            return $this->callApi(self::API_depositToGame, $data, $context);
        }
        else {
            return $this->callApi(self::API_withdrawFromGame, $data, $context);
        }
    }

    public function processResultForDepositOrWithdraw($params) {
        $player_name = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);

        $result = array(
            'response_result_id' => $response_result_id,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $array_result['status'];

            if($this->transaction_type_deposit == self::TRANSACTION_DEPOSIT) {
                if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($status, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                    $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                    $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                    $success=true;
                }
            }

            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- TPG result for depositOrWithdraw ----------', $array_result);
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        return $this->depositOrWithdraw($playerName, $this->dBtoGameAmount($amount), $transfer_secure_id, self::TRANSACTION_DEPOSIT);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        return $this->depositOrWithdraw($playerName, $this->dBtoGameAmount($amount), $transfer_secure_id, self::TRANSACTION_WITHDRAW);
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['status'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- TPG result for withdrawFromGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case self::REASON_DUPLICATE_TRANSFER:
                $reasonCode = parent::REASON_DUPLICATE_TRANSFER;
                break;
            case self::REASON_TRANSACTION_DENIED:
                $reasonCode = parent::REASON_TRANSACTION_DENIED;
                break;
            case self::REASON_NO_ENOUGH_BALANCE:
                $reasonCode = parent::REASON_NO_ENOUGH_BALANCE;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    public function queryForwardGame($player_name, $extra) {
        $token = $this->loginOrCreate($player_name, self::API_login)['token'];

        if(isset($this->lobby_url) && !empty($this->lobby_url)) {
            $this->lobby_url = $this->getSystemInfo('lobby_url');
        } else {
            $this->lobby_url = $extra['is_mobile'] == 'true'
                             ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
                             : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
        }

        if($extra['game_mode'] == 'app') {
            $launch_url = $this->mobile_app_launch_url . "/?" . http_build_query(['loginToken' => $token]);
            $download_url = $this->getSystemInfo('download_url', '');
            $logo_url = $this->getSystemInfo('logo_url', '');
            $result = [
                'success' => true,
                'redirect' => false,
                'url' => $launch_url,
                'download_url' => $download_url,
                'logo_url' => $logo_url,
            ];
            return $result;
        }
        if(isset($extra['language'])){
            $this->language = $extra['language'];
        }
        $data = [
            'lang' => $this->getLauncherLanguage($this->language),
            'backUrl' => $this->lobby_url,
        ];
        $game_code = explode('-', $extra['game_code']);
        $game_mode = $extra['game_mode'] == 'real' ? 4 : 1;
        $launch_url = $this->launch_url . "/{$this->operator_id}/{$game_code[0]}/{$game_code[1]}/{$game_mode}/{$token}?" . http_build_query($data);
        $result = [
            'success' => true,
            'url' => $launch_url,
        ];

        return $result;
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
        	case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $language = 'zh-cn';
                break;
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $language = 'en';
                break;
			case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
				$language = 'vi';
				break;
            case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $language = 'th';
                break;
            case 'id-id':
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $language = 'id';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));

        $startDate->modify($this->getDatetimeAdjust());

        $queryDateTimeStart = $startDate->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDate->format("Y-m-d H:i:s");

        # Query Exact end
    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDate->format("Y-m-d H:i:s");
    	}

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        while ($queryDateTimeMax  > $queryDateTimeStart) {
		    $success = $this->processGameHistory($queryDateTimeStart, $queryDateTimeEnd);
			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
	    	}

			$this->CI->utils->debug_log("TPG start_end_time: ", ["start" => $queryDateTimeStart, "end" => $queryDateTimeEnd]);
		}           
        
        return array('success' => $success);
    }

    public function processGameHistory($startDate, $endDate){
        $while = true;
        $page = 1;
        while($while){

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
            );

            $data = [
                'operatorId' => $this->operator_id,
                'from' => date('Y-m-d\TH:i:s', strtotime($startDate)),
                'to' => date('Y-m-d\TH:i:s', strtotime($endDate)),
                'limit' =>  $this->pageLimit,
                'offset' => $this->offset
            ];

            $this->CI->utils->debug_log('---------- TPG params for syncOriginalGameLogs ----------', $data, 'offset', $this->offset,
        'startDate', $startDate, 'endDate', $endDate);

            $apiResult = $this->callApi(self::API_syncGameRecords, $data, $context);

            $this->CI->utils->debug_log('---------- TPG apiResult for syncOriginalGameLogs ----------', $apiResult);

            # we check here if row count in API response data index is more than page limit, we do pagination here
            if((isset($apiResult['success']) && !$apiResult['success']) || (isset($apiResult['is_max_return']) && $apiResult['is_max_return'])){
                $while = false;
            }else{
                $page++;
                $this->offset = (($page -1) * $this->pageLimit);
                sleep($this->syncSleepTime);
            }
        }

        return true;
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, null);
        $gameRecords = !empty($arrayResult['data']) ? $arrayResult['data']:[];
        $this->CI->utils->info_log('---------- TPG arrayResult ----------', $arrayResult);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'is_max_return' => false,
            'row_count' => 0
        );
        $totalRows = 0;
        if($success && !empty($gameRecords)) {

            $cntData = is_array($arrayResult['data']) ? count($arrayResult['data']) : null;
            $totalRows = isset($arrayResult['totalRows']) ? (int)$arrayResult['totalRows'] : 0;

            $this->CI->utils->info_log('---------- TPG totalRows for processResultForSyncGameRecords ----------', $totalRows);
            
            $dataResult = $this->insertOgl($gameRecords,$responseResultId);

            $dataResult['is_max_return'] = false;
            $dataResult['row_count'] = $totalRows;
        }else{
            $dataResult['is_max_return'] = true;

            $this->CI->utils->info_log('---------- TPG stop loop no data ----------');
        }

        return array($success, $dataResult);
    }

    /**
     * Insert Original Game logs daata
     *
     * @param array $gameRecords
     * @param int $responseResultId
     *
     * @return array
     *
     */
    public function insertOgl(&$gameRecords,$responseResultId)
    {

        $this->processGameRecords($gameRecords,$responseResultId);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            $this->original_table,
            $gameRecords,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_ORIGINAL,
            'md5_sum',
            'id',
            self::MD5_FLOAT_AMOUNT_FIELDS
        );

        $dataResult['data_count'] = is_array($gameRecords) ? count($gameRecords) : null;

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] = $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] = $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    public function processGameRecords(&$gameRecords, $responseResultId, $type = 'normal') {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $record['transaction_detail'] = json_decode($record['transaction_detail'], true);
                $data['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $data['bet_at'] = isset($record['created_at']) ? $record['created_at'] : null;
                $data['end_at'] = isset($record['updated_at']) ? $record['updated_at'] : null;
                $data['operator_id'] = isset($record['operator_id']) ? $record['operator_id'] : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['game_type'] = isset($record['game_type']) ? $record['game_type'] : null;
                $data['game_theme'] = isset($record['game_theme']) ? $record['game_theme'] : null;
                $data['game_code'] = (isset($record['game_type']) && isset($record['game_theme'])) ? ($record['game_type'] . '-'. $record['game_theme']) : null;
                $data['game_client_platform'] = isset($record['game_client_platform']) ? $record['game_client_platform'] : null;
                $data['is_test_account'] = isset($record['is_test_account']) ? $record['is_test_account'] : null;
                $data['completed'] = isset($record['completed']) ? $record['completed'] : null;

                $data['total_deduct_amount'] = isset($record['transaction_detail']['total_deduct_amount']) ? $this->gameAmountToDB($record['transaction_detail']['total_deduct_amount']) : null;
                $data['bet_per_line'] = isset($record['transaction_detail']['bet_per_line']) ? $this->gameAmountToDB($record['transaction_detail']['bet_per_line']) : null;
                $data['total_bet_multiplier'] = isset($record['transaction_detail']['total_bet_multiplier']) ? $record['transaction_detail']['total_bet_multiplier'] : null;
                $data['is_free_spin'] = isset($record['transaction_detail']['is_free_spin']) ? $record['transaction_detail']['is_free_spin'] : null;
                $data['freespin_won_count'] = isset($record['transaction_detail']['freespin_won_count']) ? $record['transaction_detail']['freespin_won_count'] : null;
                $data['line_payout'] = isset($record['transaction_detail']['line_payout']) ? $record['transaction_detail']['line_payout'] : null;
                $data['winning_lines_position'] = isset($record['transaction_detail']['winning_lines_position']) ? $record['transaction_detail']['winning_lines_position'] : null;
                $data['has_wild_symbol'] = isset($record['transaction_detail']['has_wild_symbol']) ? $record['transaction_detail']['has_wild_symbol'] : null;
                $data['winning_symbols'] = isset($record['transaction_detail']['winning_symbols']) ? $record['transaction_detail']['winning_symbols'] : null;
                $data['original_payouts'] = isset($record['transaction_detail']['original_payouts']) ? $record['transaction_detail']['original_payouts'] : null;
                $data['freespin_payout_multiplier'] = isset($record['transaction_detail']['freespin_payout_multiplier']) ? $record['transaction_detail']['freespin_payout_multiplier'] : null;
                $data['total_payout_amount'] = isset($record['transaction_detail']['total_payout_amount']) ? $this->convertAmount($record['transaction_detail']['total_payout_amount']) : null;
                $data['transaction_type'] = isset($record['transaction_detail']['transaction_type']) ? $record['transaction_detail']['transaction_type'] : null;
                $data['bet_amount'] = $this->processBetAmount($record);
                $data['payout_amount'] = isset($record['transaction_detail']['payout_amount']) ? $this->gameAmountToDB($record['transaction_detail']['payout_amount']) : 0;
                $data['round_id'] = isset($record['transaction_detail']['round_id']) ? $record['transaction_detail']['round_id'] : null;
                $data['room_rate'] = isset($record['transaction_detail']['room_rate']) ? $record['transaction_detail']['room_rate'] : null;
                $data['jackpot_commission'] = isset($record['transaction_detail']['jackpot_commission']) ? $record['transaction_detail']['jackpot_commission'] : null;
                $data['jackpot_commission_rate'] = isset($record['transaction_detail']['jackpot_commission_rate']) ? $record['transaction_detail']['jackpot_commission_rate'] : null;
                $data['fish_caught'] = isset($record['transaction_detail']['fish_caught']) ? $record['transaction_detail']['fish_caught'] : null;
                $data['fish_url'] = isset($record['transaction_detail']['fish_url']) ? $record['transaction_detail']['fish_url'] : null;
                $data['bet_line_count'] = isset($record['transaction_detail']['bet_line_count']) ? $record['transaction_detail']['bet_line_count'] : null;
                $data['booster_price'] = isset($record['transaction_detail']['booster_price']) ? $this->gameAmountToDB($record['transaction_detail']['booster_price']) : null;
                $data['winning_lines'] = isset($record['transaction_detail']['winning_lines']) ? $record['transaction_detail']['winning_lines'] : null;
                $data['minigame_won'] = isset($record['transaction_detail']['minigame_won']) ? $record['transaction_detail']['minigame_won'] : null;
                $data['winning_lines'] = isset($record['transaction_detail']['winning_lines']) ? $record['transaction_detail']['winning_lines'] : null;
                $data['match_count'] = isset($record['transaction_detail']['match_count']) ? $record['transaction_detail']['match_count'] : null;

                $data['sum_deduct_amount'] = isset($record['sum_deduct_amount']) ? $this->gameAmountToDB($record['sum_deduct_amount']) : null;
                $data['sum_payout_amount'] = isset($record['sum_payout_amount']) ? $this->gameAmountToDB($record['sum_payout_amount']) : null;
                $data['sum_jackpot_contribute'] = isset($record['sum_jackpot_contribute']) ? $this->gameAmountToDB($record['sum_jackpot_contribute']) : null;
                $data['sum_jackpot_won'] = isset($record['sum_jackpot_won']) ? $this->gameAmountToDB($record['sum_jackpot_won']) : null;
                $data['total_transaction'] = isset($record['total_transaction']) ? $this->gameAmountToDB($record['total_transaction']) : null;

                $data['external_uniqueid'] = $data['transaction_id'];
                $data['response_result_id'] = $responseResultId;
                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $table = $this->original_table;
        $sqlTime='tpg.end_at >= ? and tpg.end_at <= ?';
        $sql = <<<EOD
SELECT
    tpg.id as sync_index,
    tpg.username,
    tpg.total_deduct_amount,
    tpg.total_payout_amount,
    tpg.bet_amount,
    tpg.payout_amount,
    tpg.sum_deduct_amount,
    tpg.sum_payout_amount,
    tpg.end_at,
    tpg.bet_at,
    tpg.transaction_id as round_number,
    tpg.game_code,
    tpg.completed as status,

    tpg.external_uniqueid,
    tpg.updated_at,
    tpg.md5_sum,
    tpg.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as tpg
    LEFT JOIN game_description ON tpg.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON tpg.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $row['total_deduct_amount'] = is_null($row['total_deduct_amount']) ? 0 : $row['total_deduct_amount'];
        $row['bet_amount'] = is_null($row['bet_amount']) ? 0 : $row['bet_amount'];
        $row['sum_deduct_amount'] = is_null($row['sum_deduct_amount']) ? 0 : $row['sum_deduct_amount'];
        $row['total_payout_amount'] = is_null($row['total_payout_amount']) ? 0 : $row['total_payout_amount'];
        $row['payout_amount'] = is_null($row['payout_amount']) ? 0 : $row['payout_amount'];
        $row['sum_payout_amount'] = is_null($row['sum_payout_amount']) ? 0 : $row['sum_payout_amount'];

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                /** as per game provider, for all game types, use total deduct amount for bet amount and total_payout_amount for payout */
                'bet_amount'            => $row['total_deduct_amount'],
                'result_amount'         => $row['total_payout_amount'] - $row['total_deduct_amount'],
                'bet_for_cashback'      => $row['total_deduct_amount'],
                'real_betting_amount'   => $row['total_deduct_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => $row['bet_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        switch($row['status']) {
            case 0:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
            case 1:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
        }
    }


    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        //return '+8 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        //return '-8 hours';
    }*/

    public function queryTransaction($transfer_id, $extra) {
        $from_date = date('Y-m-d\TH:i:s', strtotime($extra['transfer_time'] . ' -1 minute'));
        $to_date = date('Y-m-d\TH:i:s', strtotime($extra['transfer_time'] . ' +1 minute'));
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transfer_id,
            'extra' => $extra
        );

        $data = [
            'operatorId' => $this->operator_id,
            'from' => $from_date,
            'to' => $to_date,
            'limit' => 100,
            'offset' => 0,
        ];

        $this->CI->utils->debug_log('---------- TPG params for queryTransaction ----------', $data);
        return $this->callApi(self::API_queryTransaction, $data, $context);
    }

	/**
	 * overview : process result for queryTransaction
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
	public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $extra = $this->getVariableFromContext($params, 'extra');
        $success = $this->processResultBoolean($response_result_id, $array_result);
        $result = [
            'response_result_id' => $response_result_id,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if($success) {
            $extra['playerName'] = $this->getGameUsernameByPlayerUsername($extra['playerName']);
            foreach($array_result['data'] as $transaction) {
                if(strtolower($transaction['username']) == strtolower($extra['playerName'])
                && ($transaction['transfer_type'] == ($extra['transfer_method'] == 'withdrawal') ? self::TRANSACTION_WITHDRAW : self::TRANSACTION_DEPOSIT)
                && $transaction['transaction_id'] == $extra['secure_id']
                && $transaction['amount'] == number_format($extra['amount'], 2, '.', '')) {
                    $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                    break;
                }
            }
        } else {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    /*
        The amount which is Total_Payout_Amount, is in string and has comma,
        which needs to be remove first before converting the amount.
    */
    public function convertAmount($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $amount = str_replace(',', '', $amount);
        $gameAmount = $amount / $conversion_rate;

        return $gameAmount;

    }

    /**
     * Process Bet Amount
     *
     * @param array $record
     * @return int $betAmount
     */
    public function processBetAmount($record)
    {
        $betAmount = isset($record['transaction_detail']['bet_amount'])
            ? $this->gameAmountToDB($record['transaction_detail']['bet_amount']) : 0;
        $boosterPrice = isset($record['transaction_detail']['booster_price']) ?
            $record['transaction_detail']['booster_price'] : null;
        $totalDeductAmount = isset($record['transaction_detail']['total_deduct_amount']) ?
            $record['transaction_detail']['total_deduct_amount'] : null;

        if(! is_null($boosterPrice) && !is_null($totalDeductAmount)){
            $betAmount = intval($totalDeductAmount) - intval($boosterPrice);
        }

        return $this->gameAmountToDB($betAmount);
    }


    public function checkGameIpWhitelistByGameProvider($visitorIp){
        return parent::checkGameIpWhitelistByGameProvider($visitorIp);
    }


    public function callback($method, $params) {
        if($method == 'login') {

            if(empty($params['username']) || empty($params['password'])) {
                $this->CI->session->set_flashdata('auth_error',  'con.04');
                redirect($_SERVER['HTTP_REFERER']);
            }

            $password = $this->CI->player_model->getPasswordByUsername($params['username']);
            if($params['password'] === $password) {
                $response = $this->playerLogin($params['username'], $params['token']);
                if($response['success']) {
                    $this->CI->session->set_flashdata('auth_success',  'con.03');
                    redirect($_SERVER['HTTP_REFERER']);
                }
                else {
                    $this->CI->session->set_flashdata('auth_error',  'Game Provider internal problem');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
            else {
                $this->CI->session->set_flashdata('auth_error',  'con.04');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function playerLogin($player_name, $token) {
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $player_id = $this->CI->player_model->getPlayerIdByUsername($player_name);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForplayerLogin',
            'player_name' => $player_name,
            'playerId' => $player_id,
        );

        $data = [
            'operatorId' => $this->operator_id,
            'pblt' => $token,
            'playerName' => $game_username,
            'displayName' => $game_username,
            'currency' => $this->currency,
            'loginIp' => $this->CI->utils->getIP()
        ];

        $this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- TPG playerLogin ----------', $data);

        return $this->callApi(self::API_playerLogin, $data, $context);
    }

    public function processResultForplayerLogin($params){
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $array_result = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($response_result_id, $array_result, $player_name);

        $this->CI->utils->debug_log('---------- TPG result for playerLogin ----------', $array_result);
        return array($success, $result);
    }

    public function getPLayerLoginLanguage($lang) {
        switch ($lang) {
        	case 'zh-cn':
                $language = LANGUAGE_FUNCTION::INT_LANG_CHINESE;
            break;
            case 'en':
                $language = LANGUAGE_FUNCTION::INT_LANG_ENGLISH;
            break;
            case 'vi':
                $language =  LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE;
            break;
            case 'th':
                $language =  LANGUAGE_FUNCTION::INT_LANG_THAI;
            break;
            case 'id':
                $language =  LANGUAGE_FUNCTION::INT_LANG_INDONESIAN;
            break;
            default:
                $language = LANGUAGE_FUNCTION::INT_LANG_ENGLISH;
            break;
        }
        return $language;
    }

    // public function queryBetDetailLinkByRoundId($roundId) {
    public function queryBetDetailLink($player_username, $roundId = null, $extra= null) {
 
        $params = array(
            "operatorId" => $this->operator_id,
            "roundId" => $roundId,
            "lang" => $this->getQueryBetDetailsLanguage($extra)
        );

        $endPoint = $this->URI_MAP[self::API_queryBetDetailLink];
        $url = $this->api_url . $endPoint;
        $url .= '?' . http_build_query($params);
        return array("success" => true, "url" => $url);
    }

    public function getQueryBetDetailsLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
                $language = 'zh_cn';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    } 
}
