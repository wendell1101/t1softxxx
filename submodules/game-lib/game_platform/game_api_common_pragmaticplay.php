<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_common_pragmaticplay extends Abstract_game_api {

	private $api_url;
	private $currency;
	private $secureLogin;
	private $secretKey;
	private $lobby_url;
	private $demo_url;
	private $force_to_use_home_link;

	const TRANSFER_IN = 'IN';
	const TRANSFER_OUT = 'OUT';
	// const API_TransferCredit ='transfer';
	const API_getGameRounds ='getGameRounds';
	const API_syncNewGameRecords = 'syncNewGameRecords';
	const API_syncNewGameRecordsSettledOnly = 'API_syncNewGameRecordsSettledOnly';

	const URI_MAP = array(
		self::API_createPlayer => '/http/CasinoGameAPI/player/account/create/',
		self::API_queryPlayerBalance => '/http/CasinoGameAPI/balance/current/',
		self::API_depositToGame => '/http/CasinoGameAPI/balance/transfer/',
        self::API_withdrawFromGame => '/http/CasinoGameAPI/balance/transfer/',
		self::API_isPlayerExist => '/http/CasinoGameAPI/balance/current/',
		self::API_queryForwardGame => '/http/CasinoGameAPI/game/start/',
		self::API_logout => '/http/CasinoGameAPI/game/session/terminate/',
		self::API_syncGameRecords => '/DataFeeds/transactions/',
		self::API_queryTransaction => '/http/CasinoGameAPI/balance/transfer/status/',
		self::API_getGameProviderGamelist => '/http/CasinoGameAPI/getCasinoGames/',
		self::API_syncNewGameRecordsSettledOnly => '/DataFeeds/gamerounds/finished/',
		self::API_syncNewGameRecords => '/DataFeeds/gamerounds/',
		self::API_getGameRounds => '/http/HistoryAPI/GetGameRounds/',
        self::API_createFreeRoundBonus => '/http/FreeRoundsBonusAPI/createFRB/',
        self::API_cancelFreeRoundBonus => '/http/FreeRoundsBonusAPI/cancelFRB/',
        self::API_queryFreeRoundBonus => '/http/FreeRoundsBonusAPI/getPlayersFRB/',
        self::API_queryBetDetailLink => '/http/HistoryAPI/OpenHistoryExtended/',
        self::API_queryIncompleteGames => '/DataFeeds/gamerounds/incomplete/',
	);

    const GAME_WIN = 'W';
    const GAME_BET = 'B';

    const MD5_FIELDS_FOR_ORIGINAL =[
    	'playerid',
		'extplayerid',
		'gameid',
		'playsessionid',
		'timestamp',
		'referenceid',
		'type',
		'amount',
		'bet',
		'win',
		'start_date',
		'end_date',
		'status',
		'type_game_round',
		'parent_session_id'
	];

	const SIMPLE_MD5_FIELDS_FOR_ORIGINAL =[
    	'playerid',
		'extplayerid',
		'gameid',
		'playsessionid',
		'timestamp',
		'referenceid',
		'amount',
		'bet',
		'win',
		'end_date',
		'status',
		'type_game_round'
	];

	const MD5_FLOAT_AMOUNT_FIELDS=[
		'amount',
		'bet',
		'win'
	];

	# Fields in game_logs we want to detect changes for merge, and when pragmaticplay_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
    	'game_code',
    	'amount',
    	'game_date',
    	'type',
		'username',
		'table_name',
		'round_id',
		'after_balance'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
    	'amount'
    ];

    private $player_id_cache = [];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->secureLogin = $this->getSystemInfo('secureLogin');
		$this->secretKey = $this->getSystemInfo('secretKey');
		$this->lobby_url = $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url');
		$this->demo_url = $this->getSystemInfo('demo_url');
		$this->fishing_demo_url = $this->getSystemInfo('fishing_demo_url');
		$this->card_demo_url = $this->getSystemInfo('card_demo_url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->cashier_url = $this->getSystemInfo('cashier_url');
		$this->timeZone = $this->getSystemInfo('timeZone',"GMT+8");
		$this->enable_unsettle_game_logs = $this->getSystemInfo('enable_unsettle_game_logs',false);
		$this->merge_free_spin_data = $this->getSystemInfo('merge_free_spin_data', true);
		$this->convertGamelogsToCsvFile = $this->getSystemInfo('convertGamelogsToCsvFile', true);
        $this->extra_info_currency_overwrite_player_currency=$this->getSystemInfo('extra_info_currency_overwrite_player_currency', false);
        $this->data_type_for_sync = $this->getSystemInfo('data_type_for_sync',['RNG']);
		$this->sleep_interval = $this->getSystemInfo('sleep_interval', '60');

        $this->use_extrainfo_lobby_url = $this->getSystemInfo('use_extrainfo_lobby_url', false);
        $this->use_extrainfo_cashier_url = $this->getSystemInfo('use_extrainfo_cashier_url', false);
		$this->card_games_list = $this->getSystemInfo('card_games_list', ['mpnn','qzpj','sang','qznn','black','brnn','erba','gflower','holdem','ksznn','lznn','ddz']);
		$this->request_id = $this->getPlatformCode().$this->CI->utils->getTimestampNow();

        $this->random_proxy_settings=$this->getSystemInfo('random_proxy_settings');

		$this->force_to_use_home_link = $this->getSystemInfo('force_to_use_home_link',false);

		$this->return_zero_balance_on_error = $this->getSystemInfo('return_zero_balance_on_error',false);
		$this->set_success_when_deposit_and_error_is_1=$this->getSystemInfo('set_success_when_deposit_and_error_is_1', false);
    }

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

    protected function makeHttpOptions($options) {
        if(!empty($this->random_proxy_settings) && is_array($this->random_proxy_settings)){
            //overwrite call_socks5_proxy
            // ['', '']
            $anyOne=rand(0,count($this->random_proxy_settings)-1);
            $options['call_socks5_proxy']=$this->random_proxy_settings[$anyOne];
            $this->CI->utils->debug_log('pick proxy', $anyOne, $options['call_socks5_proxy']);
        }
        return $options;
    }

	public function getHttpHeaders($params){
		$this->CI->utils->debug_log('getHttpHeaders request id',$this->request_id);
		return array(
			"Content-Type" => "application/x-www-form-urlencoded",
			"Cache-Control" => "no-cache",
			"x-request-id" => $this->request_id
		);
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$req_params = http_build_query($params);
		if (!array_key_exists('timepoint', $params)) {
			$url = $this->api_url . $apiUri ;
		} else {
			$url = $this->api_url . $apiUri . "?" . $req_params;
		}
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if (!array_key_exists("timepoint", $params)) {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		}
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName=null) {
		$success = false;
		if(isset($resultArr['error'])&&$resultArr['error']=='0'||$resultArr['error']=='17'){
			$success = true;
		}

		if($this->set_success_when_deposit_and_error_is_1){
	        //for transfer deposit only
	        if(isset($resultArr['error']) && $resultArr['error'] == '1' && $apiName == self::API_depositToGame){
	            $success = true;
	        }
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PRAGMATICPLAY_API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function syncAfterBalance($token){
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $originalRecords = $this->queryRealOriginalGameLogsAfterBalanceIsNull($startDate,$endDate);
        $data_count = 0;
        $mergeToGamelogs = [];
        foreach ($originalRecords as $key => $record) {
        	$gameUsername = $record['username'];
        	$gameCode = $record['gameid'];
        	$datePlayed = date('Y-m-d', strtotime($startDate));
        	$externalUniqueId = $record['external_uniqueid'];
        	$data = $this->getGameRoundsDetails($gameUsername,$gameCode,$datePlayed,$externalUniqueId);

        	if($data['success']&&isset($data['details'])){
	        	# update original
	        	$record['after_balance'] = $data['details']['balance'];
        		$this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
        		# consolidate all gamelogs to update in gamelogs
        		$mergeToGamelogs[$key]['after_balance'] = $record['after_balance'];
        		$mergeToGamelogs[$key]['external_uniqueid'] = $externalUniqueId;
                $data_count++;
        	}
        }

        # update gamelogs
        $this->updateAfterBalanceOnGamelogs($mergeToGamelogs);
        $original_count = count($originalRecords);

		unset($originalRecords);
		unset($data);
		# add logs
		$this->CI->utils->debug_log("PP after balance updated count: ",$data_count,"start_date: ",$startDate,"end_date: ",$endDate);
        return array("success" => true,"data_count"=>$data_count, "original_count" => $original_count);
	}

	public function queryRealOriginalGameLogsAfterBalanceIsNull($dateFrom, $dateTo){
		$this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
			SELECT
				id,
			   	username,
			   	gameid,
			   	external_uniqueid
			FROM
			  {$this->original_table} USE INDEX (idx_end_date)
			WHERE end_date >= ?
			  AND end_date <= ? and after_balance IS NULL
EOD;

		$params=[
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('queryRealOriginalGameLogsAfterBalanceIsNull --->',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


	public function getGameRoundsDetails($gameUsername,$gameCode,$datePlayed,$externalUniqueId) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultGetGameRoundsDetails',
            'externalUniqueId' => $externalUniqueId,
        );

        $params = array(
            'datePlayed' => $datePlayed,
            'gameId' => $gameCode,
            'playerId' => $gameUsername,
            'secureLogin' => $this->secureLogin,
            'timeZone' => $this->timeZone,
        );

        $query = '';
        foreach ($params as $key => $value) {
        	$query .= $key."=".$value.'&';
        }
        $query = substr($query, 0, -1);
        $params['hash'] = MD5($query.$this->secretKey);
        return $this->callApi(self::API_getGameRounds, $params, $context);
    }

    public function processResultGetGameRoundsDetails($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $externalUniqueId = $this->getVariableFromContext($params, 'externalUniqueId');
        $result = [];
        # roundId = external_uniqueid
        if($success){
        	$gameReords = @$resultArr['rounds'];
        	foreach ($gameReords as $key => $record) {
        		if($externalUniqueId != $record['roundId']){
        			# get specific round only
        			continue;
        		}
        		$result['details']= $record;
        	}
        	unset($gameReords);
        }
        return array($success, $result);
    }

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'playerId' => $playerId
		);

		$params = array(
			'externalPlayerId' => $gameUsername,
			'secureLogin' => $this->secureLogin,
		);
		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = [];

        if($success){
        	if ($resultArr['error']=="17") {
	        	$result = array('exists' => false); # Player not found
	        }else{
	        	$result = array('exists' => true);
	        	$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        }
        }else{
            $result = array('exists' => null); #api other error
	    }

        return array($success, $result);
    }

    private function getPlayerPragmaticPlayCurrency($gameUsername){
        if($this->extra_info_currency_overwrite_player_currency){
            return $this->currency;
        }

		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
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

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'playerId' => $playerId
		);

		$params = array(
			'currency' => $this->getPlayerPragmaticPlayCurrency($gameUsername),
			'externalPlayerId' => $gameUsername,
			'secureLogin' => $this->secureLogin,
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if ($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists'] = true;
        }

		return array($success, $result);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function QueryBalanceGameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
    }

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->request_id .= "|" . $playerName;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'externalPlayerId' => $gameUsername,
			'secureLogin' => $this->secureLogin,
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$playerName);
		$result = [];
		
		if($success){

			if($this->return_zero_balance_on_error && $resultArr['error']=='17'){
				$result['balance'] =  0;
				$result['exists'] = false;
				return array(true, $result);
			}

			if(! isset($resultArr["balance"])){

				return [false,null];
			}

			$result['balance'] =  $this->QueryBalanceGameAmountToDB($resultArr['balance']);
			$result['exists'] = true;
		}else{

			$result['exists'] = null;
			$this->CI->utils->debug_log('PRAGMATIC PLAY GAME API error in queryPlayerBalance, result is -->',$resultArr);
		}

		return array($success, $result);
	}

    /**
     * PP allow that call multiple times with same params on transfer api
     * @return boolean
     */
    public function getIdempotentTransferCallApiList(){
        return [self::API_depositToGame, self::API_withdrawFromGame];
    }

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_IN;
		return $this->transferCredit($userName, $amount, $type, $transfer_secure_id);
	}

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_OUT;
		return $this->transferCredit($userName, $amount, $type, $transfer_secure_id);
	}

	public function transferCredit($playerName, $amount,$type, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$ext = $this->prefix_for_transaction_id . $transfer_secure_id;
		if(empty($transfer_secure_id)){
			$ext = $gameUsername.date("ymdHis");
		}
		$this->request_id = $ext;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCredit',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'transfer_type'=>$type==self::TRANSFER_IN ? self::API_depositToGame : self::API_withdrawFromGame,
			'type' => $type,
			'external_transaction_id' => $ext,
            'isRetry'=>false,
		);

		$params = array(
			'amount' => $type==self::TRANSFER_IN ? $this->dBtoGameAmount($amount) : '-'.$this->dBtoGameAmount($amount),  # self::TRANSFER_IN positive number self::TRANSFER_OUT negative number
			'externalPlayerId'=> $gameUsername,
			'externalTransactionId' => $ext,
			'secureLogin' => $this->secureLogin
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

        $apiName=$type==self::TRANSFER_IN ? self::API_depositToGame : self::API_withdrawFromGame;
		return $this->callApi( $apiName, $params, $context);
	}

	public function processResultForTransferCredit($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $transfer_type = $this->getVariableFromContext($params, 'transfer_type');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername,$transfer_type);

        $isRetry = $this->getVariableFromContext($params, 'isRetry');

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
        //retry and exist
        if($isRetry && !$success){
            if($type==self::TRANSFER_IN){
                //1 code for deposit
                //{"error":"8","description":"Transaction already exists.","transactionId":0,"balance":0.0}
                if(isset($resultArr['error']) && $resultArr['error']=='8'){
                    $success=true;
                }
            }else if($type==self::TRANSFER_OUT){
                //2 codes for withdrawal
                //{"error":"8","description":"Transaction already exists.","transactionId":0,"balance":0.0}
                //{"error":"1","description":"Insufficient funds available to complete the transaction.","transactionId":0,"balance":0.0}
                if(isset($resultArr['error']) &&
                        ($resultArr['error']=='8' || $resultArr['error']=='1')){
                    $success=true;
                }
            }
        }
		if ($success) {
        	$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
			$error_code = $this->getReasons(@$resultArr['error']);
            if($error_code!=self::REASON_UNKNOWN){
    			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
		}

        return array($success, $result);
	}

	private function getReasons($error){
		switch($error) {
			case '1' :
				$result['reason_id'] = self::REASON_NO_ENOUGH_BALANCE;
				break;
			case '2' :
				$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
				break;
			case '4' :
			case '5' :
				$result['reason_id'] = self::REASON_INVALID_KEY;
				break;
			case '6' :
				$result['reason_id'] = self::REASON_GAME_ACCOUNT_LOCKED;
				break;
			case '7' :
				$result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
				break;
            case '8' :
                $result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
                break;
			case '100' :
			case '120' :
				$result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;
            default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case 3:
            case 'id-id':
                $lang = 'id'; // indo
                break;
			case 4:
            case 'vi-vi':
            case 'vi-vn':
				$lang = 'vi'; // vietnamese
				break;
			case 5:
            case 'ko-kr':
				$lang = 'ko'; // korean
				break;
			case 6:
            case 'th-th':
				$lang = 'th'; // thailand
				break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-br':
                $lang = 'pt'; //portuguese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName,$extra=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$language = $this->getSystemInfo('gameDefaultLanguage', $extra['language']);

		if(isset($extra['language']) && !empty($extra['language']))
        {
            $language = $this->getLauncherLanguage($extra['language']);
		}else{
            $language = $this->getLauncherLanguage($language);
        }

		$platform = $extra['is_mobile']?"MOBILE":"WEB";

		$this->lobby_url = $extra['is_mobile']
				  		 ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
				  		 : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');

		$this->CI->utils->debug_log('extra =============>',$extra);
		if (array_key_exists("extra", $extra)) {

			$t1_lobby_url =  isset($extra['extra']['t1_lobby_url']) ? isset($extra['extra']['t1_lobby_url']) : $this->lobby_url;
			//extra checking for home link
			if(isset($extra['home_link'])){
				$t1_lobby_url = $extra['home_link'];
			}
			$this->lobby_url = $t1_lobby_url;
		}

        $gameMode = isset($extra['game_mode'])?$extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
			if (in_array($extra['game_code'], $this->card_games_list)) {
				$demoUrl = $this->card_demo_url."?gameSymbol=".$extra['game_code']."&lang=".$language."&cur=".$this->getPlayerPragmaticPlayCurrency($playerName)."&lobbyURL=".$this->lobby_url;
			} else {
				$demoUrl = $this->demo_url."?gameSymbol=".$extra['game_code']."&lang=".$language."&cur=".$this->getPlayerPragmaticPlayCurrency($gameUsername)."&lobbyURL=".$this->lobby_url;
			}
			return array("success"=>true,"url"=>$demoUrl);
		}else{

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryForwardGame',
				'language' => $language,
				'playerName' => $playerName,
				'gameUsername' => $gameUsername
			);

			$params = array(
				'externalPlayerId' => $gameUsername,
				'gameId' => $extra['game_code'],
				'language' => $language,
				'platform' => $platform,
				'secureLogin' => $this->secureLogin
			);

			# if cashierURL param is not empty, add it in params
			if(isset($extra['cashierURL'])){
				$params['cashierURL'] = isset($extra['cashierURL'])?$extra['cashierURL']:$this->cashier_url;
			}

			# if lobbyURL param is not empty, add it in params
			if(isset($extra['lobbyURL']) || isset($extra['home_link'])){
				$params['lobbyURL'] = $this->lobby_url;
			}

			// The code above is working on API that is using gamegateway API
			if ($this->use_extrainfo_lobby_url) {
				$params['lobbyURL'] = $this->lobby_url;
			}

			if($this->force_to_use_home_link) {
				$params['lobbyURL'] = $this->getHomeLink();
			}

			if ($this->use_extrainfo_cashier_url) {
				$params['cashierURL'] = $this->cashier_url;
			}

			ksort($params);
			$param_build = '';
			foreach ($params as $key => $value) {
				$param_build .= "&{$key}={$value}";
			}
			$param_build = trim($param_build, '&');
			$params['hash'] = MD5($param_build.$this->secretKey);
			$this->CI->utils->debug_log('PRAGMATIC PLAY API FORWARD GAME =========================>',$params);

			return $this->callApi(self::API_queryForwardGame, $params, $context);
		}

	}

	public function processResultForQueryForwardGame($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array();
		$result['url'] = '';
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		if($success){
			$result['url'] = $resultArr["gameURL"];
		}

		return array($success, $result);
	}

	public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'externalPlayerId'=> $gameUsername,
			'secureLogin' => $this->secureLogin
		);
		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		return array($success, $resultArr);
	}

	public function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

    	$startDate->modify($this->getDatetimeAdjustSyncOriginal());
		//observer the date format
		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');
		$rtn = array();
		while ($queryDateTimeMax  > $queryDateTimeStart) {

			$startDateParam=new DateTime($queryDateTimeStart);
			if($queryDateTimeEnd>$queryDateTimeMax){
				$endDateParam=new DateTime($queryDateTimeMax);
			}else{
				$endDateParam=new DateTime($queryDateTimeEnd);
			}
			$startDateParam = $startDateParam->format('Y-m-d H:i:s');
			$endDateParam = $endDateParam->format('Y-m-d H:i:s');

			$timepoint = strtotime($startDateParam)*1000;

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $startDateParam,
				'endDate' => $endDateParam
			);
			foreach($this->data_type_for_sync as $dataType){
				$params = array(
					'login' => $this->secureLogin,
					'password' => $this->secretKey,
					'timepoint' => $timepoint,
					'dataType' => $dataType
				);

				#by default settled only
				$gamelogsMethod = self::API_syncNewGameRecordsSettledOnly;
				if($this->enable_unsettle_game_logs){
					$gamelogsMethod = self::API_syncNewGameRecords;
				}

				$rtn[] = $this->callApi($gamelogsMethod, $params, $context);

				$queryDateTimeStart = $endDateParam;
		    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    	}
	    	sleep($this->sleep_interval);
	    	$this->CI->db->_reset_select();
            $this->CI->db->reconnect();
            $this->CI->db->initialize();
		}

		return array("success"=>true,"sync_details" => $rtn);
    }

    private function convertGamelogsToCsvFileAndRead($startDate,$endDate,$csvtext){
        if(!$this->convertGamelogsToCsvFile){
            $arr = explode("\n", $csvtext);
            $gameRecord = array();
            if(!empty($arr)){
                foreach ($arr as &$line) {
                    $row = str_getcsv($line);
                    if(!empty($row) && is_array($row) && count($row) > 1) { // ignore timepoint or string
                        $gameRecord[] = $row;

                    }
                }
            }

            unset($gameRecord[0]);//remove header column
            $gameRecord = array_values($gameRecord);//rebase
            return $gameRecord;
        }

        $this->game_records_path; #game record path

        $file_name = $this->game_records_path . '/' . $startDate . '_to_' . $endDate .rand(10,9999). '.csv';
        $renamed_name = str_replace(array(":"," "),"_", $file_name);
        file_put_contents($renamed_name,$csvtext); #save it to file
        $csv = array_map('str_getcsv', file($renamed_name)); #read file
        unset($csv[0],$csv[1]);

        //delete csv
        unlink($renamed_name);

        return $csv;
    }

	// for new api /DataFeeds/gamerounds/
	public function preProcessNewGameRecords(&$gameRecords,$extra){
		$preResult = array();
		foreach($gameRecords as $index => $record) {

            $playerId = null;
            if(isset($record['1'])) {
                if(isset($this->player_id_cache[$record['1']])) {
                    $playerId = $this->player_id_cache[$record['1']];
                }
                else {
                    $playerId =  $this->getPlayerIdByGameUsername($record['1']);
                    $this->player_id_cache[$record['1']] = $playerId;
                }
            }

			$preResult[$index]['playerid'] 		   = isset($record['0']) ? $record['0'] : NULL;
			$preResult[$index]['extplayerid'] 	   = isset($record['1']) ? $record['1'] : NULL;
			$preResult[$index]['gameid'] 		   = isset($record['2']) ? $record['2'] : NULL;
			$preResult[$index]['playsessionid']    = isset($record['3']) ? $record['3'] : NULL;
			$preResult[$index]['parent_session_id']= isset($record['4']) ? $record['4'] : NULL;
			$preResult[$index]['start_date']       = isset($record['5']) && $record['5'] != "null" ? $this->gameTimeToServerTime($record['5']) : NULL;

			# use start date if end date is empty (meaning round not yet finished)
			$preResult[$index]['end_date']         = isset($record['6']) && $record['6'] != "null" ? $this->gameTimeToServerTime($record['6']) : $preResult[$index]['start_date'];

			$preResult[$index]['status']           = isset($record['7']) ? $record['7'] : NULL;    # status (I - Inprogress , C - Complete)
			$preResult[$index]['type_game_round']  = isset($record['8']) ? $record['8'] : NULL;	   # type ( R - game round, F - free spin)
			$preResult[$index]['bet']              = isset($record['9']) ? $record['9'] : NULL;
			$preResult[$index]['win']              = isset($record['10']) ? $record['10'] : NULL;
			$preResult[$index]['currency'] 		   = isset($record['11']) ? $record['11'] : NULL;
			$preResult[$index]['jackpot'] 		   = isset($record['12']) ? $record['12'] : NULL;

            $preResult[$index]['sbeplayerid'] 	   = $playerId;

			// set null for old api  ===> self::API_syncGameRecords => '/DataFeeds/transactions/',
			$preResult[$index]['timestamp'] 	   = NULL;
			$preResult[$index]['referenceid'] 	   = NULL;
			$preResult[$index]['type'] 			   = NULL;
			$preResult[$index]['amount'] 		   = NULL;

			$preResult[$index]['last_sync_time']	 = $this->CI->utils->getNowForMysql();

			//extra info from SBE
			$preResult[$index]['username'] 		     = isset($record['1']) ? $record['1'] : NULL;

			$preResult[$index]['external_uniqueid']  = $preResult[$index]['playsessionid'];
			$preResult[$index]['related_uniqueid']   = $preResult[$index]['playsessionid'];
			$preResult[$index]['response_result_id'] = $extra['responseResultId'];
		}
		$gameRecords = $preResult;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('original_game_logs_model','external_system'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$csvtext = $params['resultText'];
		$gameRecords = $this->convertGamelogsToCsvFileAndRead($startDate,$endDate,$csvtext);

		$result = array(
			'data_count'=> 0
		);
		$dataCount = 0;
		if(!empty($gameRecords)){
			$extra = ['responseResultId'=>$responseResultId];
			$this->preProcessNewGameRecords($gameRecords,$extra);

			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                $this->getMd5FieldsForOriginal(),
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}

		return array(true, $result);
	}

	public function syncMergeToGameLogs($token) {
		# by default false only sync settled
		$enabled_game_logs_unsettle = false;
		if($this->enable_unsettle_game_logs){
			$enabled_game_logs_unsettle = true;
		}
        $this->unknownGame = $this->getUnknownGame($this->getPlatformCode());

        return $this->commonSyncMergeToGameLogs($token,
	        $this,
	        [$this, 'queryOriginalGameLogs'],
	        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
	        [$this, 'preprocessOriginalRowForGameLogs'],
	        $enabled_game_logs_unsettle);
	}

	 private function getGameRecordsStatus($status) {
        if(!$this->enable_unsettle_game_logs){
            return Game_logs::STATUS_SETTLED;
        }

        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);

        switch ($status) {
            case 'i':
                $status = Game_logs::STATUS_PENDING;
                break;
            case 'v': 
                $status = Game_logs::STATUS_VOID;
                break;
            default:
                $status = Game_logs::STATUS_SETTLED;
                break;
        }

        return $status;
    }

	public function preprocessOriginalRowForGameLogs(array &$row){
		if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

		$row['status'] = $this->getGameRecordsStatus($row['status']);
		$bet_details = [
			'roundId' => $row['round_id'],
			'table_identifier' => $row['table_name']
		];
		$row['bet_details'] = $bet_details;
	}


	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		$extra = [
            'table' => $row['round_id']
		];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

    	$calc_result = $row['win'] - $row['bet'];
    	$bet_amount =  $this->gameAmountToDBGameLogsTruncateNumber($row['bet']);
    	$result_amount = $this->gameAmountToDBGameLogsTruncateNumber($calc_result);
    	$start_date = $row['start_date'];
    	$end_date = $row['end_date'];

        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $start_date,
                'end_at' => $end_date,
                'bet_at' => $start_date,
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['id'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];

        return $data;
    }

    /**
     * [prepareGameLogsBeforeRecalculating set related_uniqueid as key of the array]
     * @param  [array] &$rows [unprocessed game logs]
     * @return [array]        [returns an array with key is equal to related_uniqueid]
     */
    private function prepareGameLogsBeforeRecalculating(&$rows){
        $data = [];
        foreach ($rows as $key => $row) {
            if ($row['type']==self::GAME_BET) {
                $data[$row['related_uniqueid']][$row['type']][$key] = $row;
            }else{
                $data[$row['related_uniqueid']][$row['type']] = $row;
            }
        }
        $rows = $data;
        unset($data);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $row['game_code']);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId' => $playerId,
			'external_transaction_id' => $transactionId,
        );

        $params = array(
            'externalTransactionId' =>$transactionId,
            'secureLogin' => $this->secureLogin
        );

        $params['hash'] = MD5(http_build_query($params).$this->secretKey);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

        if(isset($resultArr['error']) && $resultArr['error'] == "0"){
            if($resultArr['status'] == 'Not found') {
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
            }else{
                $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            }
        }else{
			if($resultArr['status'] == 'Not found') {
				$result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
			} else {
				$error_code = @$resultArr['error'];
				switch($error_code) {
					case '2' :
						$result['reason_id'] = self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
						break;
				}
			}
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
  		$sqlTime = ' pgl.end_date >= ? AND pgl.end_date <= ?';

        if($use_bet_time){
           $sqlTime = ' pgl.start_date >= ? AND pgl.start_date <= ?';
        }
        $sqlStatus='';
        if(!$this->enable_unsettle_game_logs){
            $sqlStatus=' and pgl.status="C"';
        }
        $sqlFreeSpin='';
        if(!$this->merge_free_spin_data) {
            $sqlFreeSpin =' AND pgl.type_game_round = "R" ';
        }

        $sql = <<<EOD
			SELECT
				pgl.id as id,
				pgl.sbeplayerid,
				pgl.username,
				pgl.related_uniqueid as external_uniqueid,
				pgl.timestamp AS game_date,
				pgl.gameid AS game_code,
				pgl.response_result_id,
				pgl.amount,
				pgl.type,
				pgl.md5_sum,
				pgl.start_date,
				pgl.end_date,
				pgl.status,
				pgl.type_game_round,
				pgl.bet,
				pgl.win,
				pgl.jackpot,
				pgl.after_balance,
				pgl.last_sync_time as updated_at,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_type_id,
				gd.english_name as table_name,
				pgl.playsessionid as round_id
			FROM $this->original_table as pgl
			left JOIN game_description as gd ON pgl.gameid = gd.game_code and gd.game_platform_id=?
			JOIN game_provider_auth ON pgl.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
			WHERE
			{$sqlTime}
			{$sqlFreeSpin}
            {$sqlStatus}
EOD;

		$params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function login($username, $password = null) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*public function getGameTimeToServerTime() {
		// return '+8 hours';
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*public function getServerTimeToGameTime() {
		// return '-8 hours';
	}*/

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function getGameProviderGameList() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processGetGameProviderGameList',

		);

		$params = array(
			'secureLogin' => $this->secureLogin,
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
	}

	public function processGetGameProviderGameList($params) {
      	$resultJson = $this->getResultJsonFromParams($params);
		$success = isset($resultJson['gameList']) ? true:false;

		if ( $success && $resultJson['gameList'] > 0 ) {
			$list = [];

			$gameTypeList = array(
				'Video Slots' => 'slots',
				'Classic Slots' => 'slots',
				'Blackjack' => 'table_games',
				'Video Poker' => 'video_poker',
				'Keno' => 'table_games',
				'Roulette' => 'table_games',
				'Baccarat' => 'table_games',
				'Racing games' => 'others',
			);

			$this->CI->load->model(['game_description_model','game_type_model']);
			$dbGameTypeList = $this->getDBGametypeList();

			foreach ($resultJson['gameList'] as $key => $gameDetail) {

				$gameTypeCode = isset( $gameTypeList[$gameDetail['typeDescription']]) ? $gameTypeList[$gameDetail['typeDescription']] : 'others';
				$gameTypeId = $dbGameTypeList[$gameTypeCode]['id'];

				$lang_arr = [
					self::INT_LANG_ENGLISH 		=> $gameDetail['gameName'],
					self::INT_LANG_CHINESE 		=> $gameDetail['gameName'],
					self::INT_LANG_INDONESIAN   => $gameDetail['gameName'],
					self::INT_LANG_VIETNAMESE   => $gameDetail['gameName'],
					self::INT_LANG_KOREAN 		=> $gameDetail['gameName']
				];

				$list[$key] = [
					'game_platform_id' 	 => $this->getPlatformCode(),
					'game_type_id' 	  	 => $gameTypeId,
					'game_code' 	 	 => $gameDetail['gameID'],
					'attributes' 	 	 => '{"game_launch_code":"'. $gameDetail['gameID'] .'"}',
					'english_name' 		 => $gameDetail['gameName'],
					'external_game_id' 	 => $gameDetail['gameID'],
					'enabled_freespin' 	 => Game_description_model::DB_FALSE,
					'sub_game_provider'  => null,
					'enabled_on_android' => $this->checkGameAttribute('html5',$gameDetail['technology']),
					'enabled_on_ios' 	 => $this->checkGameAttribute('html5',$gameDetail['technology']),
					'status' 			 => Game_description_model::DB_TRUE,
					'flash_enabled' 	 => $this->checkGameAttribute('flash',$gameDetail['technology']),
					'mobile_enabled' 	 => $this->checkGameAttribute('html5',$gameDetail['technology']),
					'html_five_enabled'  => $this->checkGameAttribute('html5',$gameDetail['technology']),
					'game_name' 		 => $this->processLanguagesToJson($lang_arr),
				];
			}

			$result = $this->CI->game_description_model->syncGameDescription($list,null, false, true, null, $this->getGameListAPIConfig());

		}
		return array($success, $result);
	}

	public function checkGameAttribute ($key,$data) {

		if( strpos($data, ',') == false) {
			return ($key == $data) ? Game_description_model::DB_TRUE:Game_description_model::DB_FALSE;
		}else{
			$array = explode(',', $data);
			return in_array($key,$array) ? Game_description_model::DB_TRUE:Game_description_model::DB_FALSE;
		}
	}

    public function testMD5Fields($resultText, $externalUniqueId){

        $gameRecords = str_getcsv($resultText);
        unset($gameRecords[0],$gameRecords[1]);

        $extra = ['response_result_id'=>null];
        $this->preProcessNewGameRecords($gameRecords,$extra);
        //only keep one external uniqueid
        $apiRows=[];
        foreach ($gameRecords as $row) {
            if($row['external_uniqueid']==$externalUniqueId){
                $apiRows[]=$row;
                break;
            }
        }
        unset($gameRecords);
        $originalStrArr=[];
        $uniqueidValues=$this->CI->original_game_logs_model->preprocessRows($apiRows, $this->getMd5FieldsForOriginal(),
            'external_uniqueid', 'md5_sum', self::MD5_FLOAT_AMOUNT_FIELDS, $originalStrArr);

        // $this->CI->utils->debug_log('after process available rows', $gameRecords, $uniqueidValues);

        return ['rows'=>$apiRows, 'uniqueidValues'=>$uniqueidValues, 'originalStrArr'=>$originalStrArr];
    }

    public function getOriginalTable(){
        return $this->original_table;
    }

    public function getMD5Fields(){
        return [
            'md5_fields_for_original'=>$this->getMd5FieldsForOriginal(),
            'md5_float_fields_for_original'=>self::MD5_FLOAT_AMOUNT_FIELDS,
            'md5_fields_for_merge'=>self::MD5_FIELDS_FOR_MERGE,
            'md5_float_fields_for_merge'=>self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE,
        ];
    }

    public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return self::SIMPLE_MD5_FIELDS_FOR_ORIGINAL;
        }

        return self::MD5_FIELDS_FOR_ORIGINAL;
    }


    public function createFreeRound($playerName, $extra = []) {
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $transaction_id = $this->getSecureId('free_round_bonuses', 'transaction_id', true, 'B', 29);
        $currency = isset($extra['currency']) ? $extra['currency'] : $this->currency;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'expired_at' => $extra['expiration_date'],
            'transaction_id' => $transaction_id,
            'currency' => $currency,
            'game_username' => $game_username,
            'free_rounds' => $extra['free_rounds'],
            'extra' => [ 'game_code' => $extra['game_code'] ],
            'player_id' => $extra['player_id'],
        );

        $params = array(
            'bonusCode' => $transaction_id,
            'currency' => $currency,
            'expirationDate' => strtotime($extra['expiration_date']),
            'gameIDList' => $extra['game_code'],
            'playerId' => $game_username,
            'rounds' => $extra['free_rounds'],
            'secureLogin' => $this->secureLogin,
        );

        $params['hash'] = MD5(http_build_query($params).$this->secretKey);

        return $this->callApi(self::API_createFreeRoundBonus, $params, $context);
    }

    public function processResultForCreateFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $free_rounds = $this->getVariableFromContext($params, 'free_rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');


        if ($success){
            $return = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];
            $this->CI->load->model(array('free_round_bonus_model'));

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => $extra,
            ];
            $this->CI->free_round_bonus_model->insertTransaction($data);
        }
        else {
            $return = [
                'message' => $resultArr['description']
            ];
        }
        return array($success, $return);
    }

    public function cancelFreeRound($transaction_id, $extra = []) {
        $currency = isset($extra['currency']) ? $extra['currency'] : $this->currency;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'transaction_id' => $transaction_id,
        );

        $params = array(
            'bonusCode' => $transaction_id,
            'secureLogin' => $this->secureLogin,
        );

        $params['hash'] = MD5(http_build_query($params).$this->secretKey);

        return $this->callApi(self::API_cancelFreeRoundBonus, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        if ($success){
            $return = [
                'transaction_id' => $transaction_id,
            ];

            $this->CI->load->model(array('free_round_bonus_model'));
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());
        }
        else {
            $return = [
                'message' => $resultArr['description']
            ];
        }
        return array($success, $return);
    }

    public function queryFreeRound($playerName, $extra = []) {
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $currency = isset($extra['currency']) ? $extra['currency'] : $this->currency;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
        );

        $params = array(
            'playerId' => $game_username,
            'secureLogin' => $this->secureLogin,
        );

        $params['hash'] = MD5(http_build_query($params).$this->secretKey);

        return $this->callApi(self::API_queryFreeRoundBonus, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if ($success){
            $return = [
                'free_round_list' => $resultArr['bonuses'],
            ];
        }
        else {
            $return = [
                'message' => $resultArr['description']
            ];
        }
        return array($success, $return);
    }
    
    public function queryBetDetailLink($playerUsername, $round_id = null, $extra=null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        );

        $params = array(
            'gameId' => $extra['game_code'],
            'playerId' => $gameUsername,
            'roundId' => $round_id,
            'secureLogin' => $this->secureLogin,
        );

        $params['hash'] = MD5(http_build_query($params).$this->secretKey);

        $this->CI->utils->debug_log('-----------------------PP queryBetDetailLink params ----------------------------',$params);
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result['url'] = ( $success && isset($resultArr['url']) ) ? $resultArr['url'] : null;
        if(!$success && isset($resultArr['error']) != 0){ 
            $result['description'] = $resultArr['description'];
        }
        return array($success, $result);
    }

    public function queryIncompleteGames($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $timepoint = strtotime($this->utils->getNowForMysql())*1000;

        $result = [
            'success' => false,
            'data_count' => 0,
        ];

		if(empty($gameUsername)) {
			return ['success' => $result['success'], 'error' => 'cannot find player name'];
		}

		foreach($this->data_type_for_sync as $dataType) {
            $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryIncompleteGames',
                'playerName' => $playerName,
                'gameUsername' => $gameUsername,
                'dataType' => $dataType,
            ];

            $params = [
                'login' => $this->secureLogin,
                'password' => $this->secretKey,
                'playerId' => $gameUsername,
                'dataType' => $dataType,
                'timepoint' => $timepoint,
            ];

            $callApiResult = $this->callApi(self::API_queryIncompleteGames, $params, $context);

            if($callApiResult['success'] && isset($callApiResult['success']) && !empty($callApiResult['success'])) {
                $result['success'] = $callApiResult['success'];
                $result['data_count'] += isset($callApiResult['data_count']) && !empty($callApiResult['data_count']) ? $callApiResult['data_count'] : 0;
            }else{
                $result['data_count'] += 0;
            }
        }

        return $result;
    }

    public function processResultForQueryIncompleteGames($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$dataType = $this->getVariableFromContext($params, 'dataType');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [
            'data_count' => 0,
            'result' => $resultArr,
        ];

		if($success && !empty($resultArr)) {
            $this->CI->load->model(['game_logs']);
			$gamePlatformId = $this->getPlatformCode();
            $existsIdArr = $this->CI->game_logs->getExistsIdList('pp_incomplete_games', ['playerId' => $resultArr['playerId'], 'game_platform_id' => $gamePlatformId, 'dataType' => $dataType]);

            //$this->CI->utils->info_log('exists id arr', $existsIdArr);

            foreach($resultArr['data'] as $row) {
                $externalUniqueId = $gamePlatformId . '-' . $dataType . '-' . $row['playSessionID'];

                //save it to db
                $data = [
                    'playerId' => $resultArr['playerId'],
                    'gameId' => $row['gameId'],
                    'playSessionID' => $row['playSessionID'],
                    'betAmount' => $row['betAmount'],
                    'game_platform_id' => $gamePlatformId,
                    'dataType' => $dataType,
                    'username_key' => $gamePlatformId . '-' . $resultArr['playerId'],
                    'response_result_id' => $responseResultId,
                    'external_uniqueid' => $externalUniqueId,
                    'updated_at' => $this->utils->getNowForMysql(),
                ];

                $id = $this->CI->game_logs->updateOrInsertRowByUniqueField('pp_incomplete_games', $data, function(&$data, $id) {
                    if(empty($id)) {
                        $data['created_at'] = $this->utils->getNowForMysql();
                        $this->CI->utils->info_log(__METHOD__ . ' insert incomplete round', $data['external_uniqueid']);
                    }else{
                        $this->CI->utils->info_log(__METHOD__ . ' update incomplete round', $data['external_uniqueid']);
                    }
                });

                if(empty($id)) {
                    $this->CI->utils->error_log('update or insert failed', $data, $row);
                    break;
                }else{
                    if(!empty($existsIdArr)) {
                        //remove id
                        $key = array_search($id, $existsIdArr);

                        if(in_array($id, $existsIdArr)) {
                            unset($existsIdArr[$key]);
                        }

                        //$this->CI->utils->info_log('delete by id', $key, count($existsIdArr));
                    }

                    $result['data_count']++;
                }
            }
             // still delete if empty
             $deleteArr = $existsIdArr;
             if(!empty($deleteArr)) {
                 $this->CI->utils->info_log('round completed', 'count', count($deleteArr), 'id', $deleteArr);
                 //batch delete
                 $this->CI->game_logs->runBatchDeleteByIdWithLimit('pp_incomplete_games', $deleteArr);
             }
		}

		return array($success, $result);
    }
}

/*end of file*/