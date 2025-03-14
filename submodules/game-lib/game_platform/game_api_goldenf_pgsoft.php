<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API DOCS NAME: PG Soft Integration Document Transfer mode
	* Document Number: V2.1.0


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_goldenf_pgsoft extends Abstract_game_api {

	const POST = "POST";
	const GET = "GET";
	const PUT = "PUT";
    const API_queryForwardGameDemo = "queryForwardGameDemo";
    const ORIGINAL_TABLE_NAME = "goldenf_pgsoft_game_logs";
    const TIMESTAMP_DIGIT = 10;
    const PAGE_SIZE = 2000;
    const MILLISECONDS = 1000;

    const MD5_FIELDS_FOR_ORIGINAL = [
		'player_name',
		'bet_id',
		'trans_type',
		'game_id',
		'game_code',
		'bet_amount',
		'win_amount',
		'pgsoft_created_at',
		'traceId',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'win_amount'
    ];

    const MD5_FIELDS_FOR_MERGE = [
		'player_username',
	    'bet_time',
		'bet_id',
		'game_code',
		'bet_amount',
		'win_amount',
		'external_uniqueid',
	    'response_result_id',
	    'game_code',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
    	'bet_amount',
		'win_amount',
    ];


	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
		$this->currency = $this->getSystemInfo('currency');
		$this->secret_key = $this->getSystemInfo('secret_key');
		$this->operator_token = $this->getSystemInfo('operator_token');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->max_call_attempt = $this->getSystemInfo('max_call_attempt', 10);
		$this->max_data_set = $this->getSystemInfo('max_data_set', 500);//maximum single value is 20000
		$this->use_new_syncing_method = $this->getSystemInfo('use_new_syncing_method', false);
        $this->terminate_on_error_count = $this->getSystemInfo('terminate_on_error_count', 3);
		$this->record_url = $this->getSystemInfo("record_url",null);

		// As i check on current setup there's an another last row version from API response which is on last_record_time not only the created_at on each record which can be use as last_sync_id
		$this->use_new_last_sync_id = $this->getSystemInfo('use_new_last_sync_id', false);
		/* For wallet code
		Specify wallet code (optional) (Applicable to API after version 2.04 )
			● Default: gf_main_balance
			● Main wallet: gf_main_balance
			● GF card game: gf_gflc_card
			● AG Fish game: gf_ag_fish
			● IA sports：gf_ia_esport
			● SBO sports: gf_sport_wallet
			● KY KaiYuan gaming: gf_ky_card
			● GMFX card game:gf_gmfx_card
			● GDQ card game: gf_gdq_card
			● MGPLUS Video/Slot game:gf_mgplus_wallet
			● Gioco Plus: gf_gps_wallet
			● XJ：gf_xj_sport
		*/
		$this->wallet_code = $this->getSystemInfo('wallet_code');
		/* FOR VENDOR CODE
		Specify product code (optional)
			● Default = show all
			● PG = PG slot game
			● LBKENO = LB-KENO lottery
			● AG = AG live video
			● AGFISH = AG fish game
			● AGSLOY = AG slot game
			● IA = IA sports
			● GFLC = GoldenF card game
			● KY = KY KaiYuan game
			● CQ9FISH = CQ9 fish game
			● CQ9 = CQ9 slot game
			● SBO = SBO sports
			● MGPLUS = MGPLUS Video/Slot game
			● GMFX = GMFX card game
			● eBET = eBET live video
			● GPS = Gioco Plus
			● GDQ = GDQ card game
			● XJ = XJ sport
		*/
		$this->vendor_code = $this->getSystemInfo('vendor_code');

		$this->method = self::POST; # default as POST
		$this->URI_MAP = array(
			self::API_createPlayer => '/Player/Create',
			self::API_queryPlayerBalance => '/GetPlayerBalance',
			self::API_isPlayerExist => '/GetPlayerBalance',
			self::API_depositToGame => '/TransferIn',
			self::API_withdrawFromGame => '/TransferOut',
			self::API_queryTransaction => '/Transaction/Record/Player/Get',
			self::API_queryForwardGame => '/Launch',
			self::API_queryForwardGameDemo => '/Demo',
			self::API_syncGameRecords => '/Bet/Record/Get',
			self::API_queryGameRecords => '/v3/Bet/Record/Get'
		);

	}

	public function getPlatformCode() {
		return GOLDENF_PGSOFT_API;
	}

	public function generateUrl($apiName, $params) {

		$api_url = $this->api_url;

		if(!empty($this->record_url)) {

			if($apiName == self::API_syncGameRecords || $apiName == self::API_queryGameRecords) {
				$api_url = $this->record_url;
			}

		}

		$apiUri = $this->URI_MAP[$apiName];
		$url = $api_url . $apiUri;
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if($this->method == self::POST){
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
	}

    private function getPlayerPGSoftCurrency($username){
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				return $currencyCode;
			}else{
				return $this->currency;
			}
		}else{
			return $this->currency;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(is_null(@$resultArr['error'])){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GOLDENF_PGSOFT_API API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"currency" => $this->getPlayerPGSoftCurrency($gameUsername)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			"player" => $gameUsername,
			"exists" => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result["exists"] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		#check if wallet code set
		if(!empty($this->wallet_code)){
			$params['wallet_code'] = $this->wallet_code;
		}

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();
		if($success){
			$result['balance'] = @floatval($resultArr['data']['balance']);
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername
		);

		#check if wallet code set
		if(!empty($this->wallet_code)){
			$params['wallet_code'] = $this->wallet_code;
		}

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			# Player not Exists
			if(isset($resultArr['error']['code'])&&$resultArr['error']['code']==3004){
				$success = true;
				$result['exists'] = false;
			}else{
				$result['exists'] = null;
			}

		}

		return array($success, $result);
    }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	private function getReasons($error_code){
        switch ($error_code) {
            case 3013:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case 3001:
            case 9421:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 1034:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 1200:
            case 1204:
            case 1303:
            case 1035:
            case 3040:
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case 1204:
                return self::REASON_INVALID_KEY;
                break;
            case 3004:
            case 3005:
            case 3006:
            case 1305:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 9400:
                return self::REASON_API_MAINTAINING;
                break;
            case 9422:
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case 9470:
                return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
		}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $this->dBtoGameAmount($amount),
            'external_transaction_id' => $transfer_secure_id,
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"amount" => $this->dBtoGameAmount($amount),
			"traceId" => $transfer_secure_id
		);

		#check if wallet code set
		if(!empty($this->wallet_code)){
			$params['wallet_code'] = $this->wallet_code;
		}

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function convertTransactionAmount($amount){ // to fix the actual amount in query status
        //return $this->dBtoGameAmount($amount);
		$precision = intval($this->getSystemInfo('conversion_precision', 2));
		return bcdiv($amount, 1, $precision);
    }

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if($success){
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if($playerId){
    //         	$after_balance = isset($resultArr['data']['balance_main'])?$resultArr['data']['balance_main']:null;
				// $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
    //             $this->insertTransactionToGameLogs($playerId, $gameUsername, $after_balance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
    //         }else{
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['error']['code'];
			if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['reason_id']=$this->getReasons($error_code);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
        }

        return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if(is_null($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
		}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $this->dBtoGameAmount($amount),
            'external_transaction_id' => $transfer_secure_id
        );

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"player_name" => $gameUsername,
			"amount" => $this->dBtoGameAmount($amount),
			"traceId" => $transfer_secure_id
		);

		#check if wallet code set
		if(!empty($this->wallet_code)){
			$params['wallet_code'] = $this->wallet_code;
		}

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
    //         	$after_balance = isset($resultArr['data']['balance_main'])?$resultArr['data']['balance_main']:null;
				// $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
	   //          $this->insertTransactionToGameLogs($playerId, $gameUsername, $after_balance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['error']['code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($error_code);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
		"secret_key" => $this->secret_key,
		"operator_token" => $this->operator_token,
		"player_name" => $gameUsername,
		"traceId" => $transactionId
		);

		#check if wallet code set
		if(!empty($this->wallet_code)){
			$params['wallet_code'] = $this->wallet_code;
		}

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success){
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
			$error_code = @$resultArr['error']['code'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	# Support Simplified Chinese, other languages are still under development.
	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case 1:
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case 3:
                $lang = 'ID'; // IDN
                break;
            case 4:
                $lang = 'VI'; // VIETNAM
                break;
            case 5:
                $lang = 'KO'; // KOREA
                break;
            case 6:
            case 'th-th':
                $lang = 'TH'; // thai
                break;
            default:
                $lang = 'zh-CN'; // default as chinese
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerToken = $this->getPlayerTokenByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardgame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$language = $this->getSystemInfo('language', $extra['language']); // to fix the language

		$params = array(
			"secret_key" => $this->secret_key,
			"operator_token" => $this->operator_token,
			"game_code" => $extra['game_code'],
			"player_name" => $gameUsername,
			"nickname" => $gameUsername,
			"language" => $language
		);

		$apiMethod = $extra['game_mode']=='real'?(self::API_queryForwardGame):(self::API_queryForwardGameDemo);
		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForQueryForwardgame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result['url'] = $resultArr['data']['game_url'];
		}

		return array($success, $result);
	}

	public function syncOriginalGameLogs($token = false) {
		if($this->use_new_syncing_method) {
			return $this->newSyncOriginalGameLogs($token);
		}

		$ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');
		if ($ignore_public_sync == true) {
			$this->CI->utils->debug_log('ignore public sync');
			return array('success' => true);
		}
		$attempt = 0;

		do {
			$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());
	    	$last_sync_id = !empty($last_sync_id)?$last_sync_id:0;

	    	$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
			);

			$params = array(
				"secret_key" => $this->secret_key,
				"operator_token" => $this->operator_token,
				"row_version" => $last_sync_id,
				"count" => $this->max_data_set,
				"timestamp_digit" => self::TIMESTAMP_DIGIT
			);

			#check if vendor code set
			if(!empty($this->vendor_code)){
				$params['vendor_code'] = $this->vendor_code;
			}
			$result =  $this->callApi(self::API_syncGameRecords, $params, $context);

			sleep(1);
			$attempt++;

			$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs attempt: ',$attempt);
			$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs orginal data: ',$result['original_data_count']);
		} while(($attempt < $this->max_call_attempt) && ($result['original_data_count'] >= $this->max_data_set));

		return array('success' => true);
	}

	private $last_rowversion=0;

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameRecords = isset($resultArr['data']['betlogs'])?$resultArr['data']['betlogs']:array();
		$isManualSync = $this->getVariableFromContext($params, 'is_manual_sync');
		$endRowVersion = $this->getVariableFromContext($params, 'end_row_version');

		$result = ['original_data_count' => 0];
		$dataCount = 0;
		if ($success) {
			$extra = [
				'response_result_id' => $responseResultId,
				'is_manual_sync' => $isManualSync,
				'end_row_version' => $endRowVersion,
			];

			if (!empty($gameRecords)) {
				$this->rebuildGameRecords($gameRecords, $extra);
			}

			## As per provider it's better to use the last_record_time for row_version upon syncing because the created_at on rebuildGameRecords is the strtime of each game log that is not pointing on the next transactions
			if ($this->use_new_last_sync_id && isset($resultArr['data']['last_record_time'])) {
				$this->CI->utils->info_log('GOLDEN PGSOFT UPDATED LAST_SYNC_ID ====>',$resultArr['data']['last_record_time']);
            	$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $resultArr['data']['last_record_time']);
			}

			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));
            $this->CI->utils->debug_log('GOLDENF PGSOFT last row version ========>', $this->last_rowversion);

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['original_data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$insertRows,
                	'insert',
                	$extra
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['original_data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$updateRows,
                	'update',
                	$extra
                );
            }
            unset($updateRows);
		}

		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra)
    {
		$rebuildRecords = [];

		foreach($gameRecords as $index => $record) {
			# exit function if max query date for sync meet
			# for manual sync only
			$this->last_rowversion = $record['created_at'];

			if ($extra['is_manual_sync'] && $record['created_at'] > $extra['end_row_version']) {
				break;
			}

            $logs = [
	            'player_name' 		=> isset($record['player_name']) 	? $record['player_name']:null,
				'bet_id' 			=> isset($record['bet_id']) 		? $record['bet_id']:null,
				'parent_bet_id' 	=> isset($record['parent_bet_id']) 	? $record['parent_bet_id']:null,
				'trans_type' 		=> isset($record['trans_type'])		? $record['trans_type']:null,
				'game_id' 			=> isset($record['game_id']) 		? $record['game_id']:null,
				'game_code' 		=> isset($record['game_code']) 		? $record['game_code']:null,
				'bet_amount' 		=> isset($record['bet_amount']) 	? $record['bet_amount']:null,
				'win_amount' 		=> isset($record['win_amount']) 	? $record['win_amount']:null,
				'pgsoft_created_at' => isset($record['created_at']) 	? $this->gameTimeToServerTime(date('Y-m-d H:i:s',$record['created_at']/self::MILLISECONDS)):null,
				'traceId' 			=> isset($record['traceId']) 		? $record['traceId']:null,

				# SBE USE
				'external_uniqueid'	 => isset($record['traceId']) ? hash('sha256',$record['traceId']) : null,
				'response_result_id' => $extra['response_result_id'],
				'last_sync_time' 	 => $this->CI->utils->getNowForMysql(),
            ];

            if (!$extra['is_manual_sync'] && !empty($this->last_rowversion)) {
            	$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $this->last_rowversion);
            }

            array_push($rebuildRecords, $logs);
        }

        $gameRecords = $rebuildRecords;
	}

	private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if (!empty($rows)) {
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(static::ORIGINAL_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(static::ORIGINAL_TABLE_NAME, $record);
                }
                $dataCount++;
            }
        }

        return $dataCount;
    }

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$table = self::ORIGINAL_TABLE_NAME;
        $sqlTime =  'pg.pgsoft_created_at >= ? and pg.pgsoft_created_at <= ?';

        $sql = <<<EOD
SELECT
    pg.id as sync_index,
    pg.player_name AS player_username,
    pg.pgsoft_created_at as bet_time,
	pg.bet_id,
	pg.game_code,
	pg.bet_amount,
	pg_payoff.win_amount,
	pg.external_uniqueid,
    pg.response_result_id,
    pg.last_sync_time,
    pg.game_code,
    pg.game_code as game,
    pg.md5_sum,
    pg.traceId as bet_trace_id,
    pg_payoff.traceId as payoff_trace_id,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_type_id
FROM
    {$table} pg
    JOIN game_provider_auth
        ON pg.player_name = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    LEFT JOIN {$table} pg_payoff
    	ON pg_payoff.bet_id = pg.bet_id AND pg_payoff.trans_type = 'Payoff'
    LEFT JOIN game_description
        ON game_description.external_game_id = pg.game_code
        AND game_description.void_bet != 1
        AND game_description.game_platform_id = ?
WHERE
    {$sqlTime} AND pg.trans_type = 'Stake'
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow(
				$row, self::MD5_FIELDS_FOR_MERGE,
				self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $winAmount = isset($row['win_amount']) ? $row['win_amount'] : 0;
        $resultAmount = ((float)$winAmount - (float)$row['bet_amount']);

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $resultAmount,
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null
            ],
            'date_info' => [
                'start_at'              => $row['bet_time'],
                'end_at'                => $row['bet_time'],
                'bet_at'                => $row['bet_time'],
                'updated_at'            => $row['last_sync_time']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['bet_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            // 'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'bet_details' => $row['bet_details'],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
        $row['bet_details'] = array(
        	"Bet ID" => $row['bet_id'],
        	"Bet Trace ID" => $row['bet_trace_id'],
        	"Payoff Trace ID" => $row['payoff_trace_id'],
        );
    }

	public function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row->game_id;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->originalGameName);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->blockUsernameInDB($gameUsername);
		return array("success" => $result);
	}

	public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->unblockUsernameInDB($gameUsername);
		return array("success" => $result);
	}

	public function syncGameByDateTime($startDate = null, $endDate = null) {
		$attempt = 0;
		$result = null;
		$endRowVersion = strtotime($endDate);
		$this->last_rowversion = !empty($last_rowversion) ? $this->last_rowversion : strtotime($startDate);

		while (($this->last_rowversion <= $endRowVersion) && ($attempt < $this->max_call_attempt)) {
			do {
				$this->CI->utils->debug_log('>>>>>>>>>>>>>>>>>>: ', $this->last_rowversion, $endRowVersion);
		    	$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'is_manual_sync' => true,
					'end_row_version' => $endRowVersion,
				);

				$params = array(
					"secret_key" => $this->secret_key,
					"operator_token" => $this->operator_token,
					"row_version" => $this->last_rowversion,
					"count" => $this->max_data_set,
					"timestamp_digit" => self::TIMESTAMP_DIGIT
				);
				$result =  $this->callApi(self::API_syncGameRecords, $params, $context);

				sleep(1);
				$attempt++;

				if (isset($result['original_data_count']) && !empty($result['original_data_count'])) {
					$attempt = 0;
				}

				$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs attempt: ',$attempt);
				$this->CI->utils->debug_log('PGSOFT API:syncOriginalGameLogs orginal data: ',$result['original_data_count']);
			} while (isset($result['original_data_count']) && ($result['original_data_count'] >= $this->max_data_set));
		}

		return $result;
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function newSyncOriginalGameLogs($token) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d H:i:s');
        $endTime = $endDateTime->format('Y-m-d H:i:s');

        $result = array();
        $result [] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, $this->sync_time_interval, function($startDate, $endDate)  { // change the time interval and use $this->sync_time_interval instead.
        	$success=false;
        	$currentPage = 1;
        	$done = false;
        	$errorCount=0;
            $startTime = strtotime($startDate->format('Y-m-d H:i:s')) * 1000;
            $endTime = strtotime($endDate->format('Y-m-d H:i:s')) * 1000;
            $checkEndTime = ($this->utils->getTimestampNow() - 30) * 1000; #endTime should be 30 seconds earlier than current time

            if($endTime > $checkEndTime) {
            	$endTime = $checkEndTime;
            }

	    	$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForNewSyncOriginalGameLogs'
			);

			while (!$done) {
				$params = array(
					"secret_key" => $this->secret_key,
					"operator_token" => $this->operator_token,
					"vendor_code" => $this->vendor_code,
					"start_time" => $startTime,
					"end_time" => $endTime,
					"page" => $currentPage,
					"page_size" => self::PAGE_SIZE
				);

	            $api_result = $this->callApi(self::API_queryGameRecords, $params, $context);

	            $this->CI->utils->debug_log('GOLDENF PGSOFT api_result ========>', $api_result);

	            if(isset($api_result['success']) && $api_result['success']) {
	            	$totalPages = $api_result['page_count'];
	            	$currentPage += 1;
	            	$done = $currentPage > $totalPages;
	            	$this->CI->utils->debug_log('GOLDENF PGSOFT (API Result) ========>', 'totalPages:',$totalPages,'currentPage:',$currentPage,'done',$done);
	            } else {
	            	$errorCount++;
	            }

	            if($this->terminate_on_error_count<>0 && $errorCount >= $this->terminate_on_error_count) {
	            	$done = true;
	            }

	            if($done) {
	            	$success=true;
	            }
			}
	        return true;
        });

        return array('success' => true, 'result' => $result);

	}

	public function processResultForNewSyncOriginalGameLogs($params) {
		$this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameRecords = isset($resultArr['data']['betlogs'])?$resultArr['data']['betlogs']:array();
		$isManualSync = $this->getVariableFromContext($params, 'is_manual_sync');

		$result = [
			'original_data_count' => 0,
			'page' => 0,
			'page_count' => 0
		];

		$dataCount = 0;
		if ($success) {
			$extra = [
				'response_result_id' => $responseResultId,
				'is_manual_sync' => $isManualSync
			];

			$result = [
				'original_data_count' => 0,
				'page' => $resultArr['data']['page'],
				'page_count' => $resultArr['data']['page_count'],
			];

            $this->CI->utils->debug_log('GOLDENF PGSOFT result ========>', $result, 'extra', $extra);

			if (!empty($gameRecords)) {
				$this->rebuildGameRecords($gameRecords,$extra);
			}

			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['original_data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$insertRows,
                	'insert',
                	$extra
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['original_data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$updateRows,
                	'update',
                	$extra
                );
            }
            unset($updateRows);
		}

		return array($success, $result);
	}

}

/*end of file*/