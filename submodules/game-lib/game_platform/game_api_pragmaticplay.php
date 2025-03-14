<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_pragmaticplay extends Abstract_game_api {

    public $original_logs_table_name;
    public $api_url;
    public $currency;
    public $secureLogin;
    public $secretKey;
    public $lobby_url;
    public $demo_url;
    public $fishing_demo_url;
    public $card_demo_url;
    public $game_records_path;
    public $sync_time_interval;
    public $cashier_url;
    public $use_new_sync_game_records;
    public $use_insert_ignore;
    public $merge_free_spin_data;
    public $convertGamelogsToCsvFile;
    public $enabled_lobby;
    public $extra_info_currency_overwrite_player_currency;
    public $data_type_for_sync;
    public $sleep_interval;
    public $forced_language_on_game_launch;
    public $card_games_list;
    public $request_id;
    public $enabled_query_afterbalance_on_trans_table;
    public $http_method;
    public $language;
	public $use_create_free_round_v2;
	public $api_name;
    public $prefix_for_username;
    public $fix_username_limit;
    public $minimum_user_length;
    public $maximum_user_length;
    public $default_fix_name_length;
    public $filter_game_list_by_game_types;


    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

	const TRANSFER_IN = 'IN';
	const TRANSFER_OUT = 'OUT';
	// const API_TransferCredit ='transfer';

	const API_syncNewGameRecords = 'syncNewGameRecords';
	const API_createFreeRoundBonusV2 = 'createFreeBonusV2';
	const API_addPlayerToFreeRoundBonus = 'addPlayerToFreeRoundBonus';
	const FISHING_GAME = "pp3fish";

	/**
	 * date Type for sync original params
	 *
	 * RNG -Main portfolio games(video slots, classic slots etc.)
	 * LC -Live Casino portfolio
	 * R1 -Card games portfolio
	 * R2 -Fishing games portfolio
	 * VSB -Virtual sports betting portfolio
	 */
	const DT_RNG = 'RNG';
	const DT_LC = 'LC';
	const DT_R1 = 'R1';
	const DT_R2 = 'R2';
	const DT_VSB = 'VSB';

	const API_queryForwardGame2 = 'queryForwardGame2';

	const URI_MAP = array(
		self::API_createPlayer => '/http/CasinoGameAPI/player/account/create/',
		self::API_queryPlayerBalance => '/http/CasinoGameAPI/balance/current/',
		// self::API_TransferCredit => '/http/CasinoGameAPI/balance/transfer/',
		self::API_withdrawFromGame => '/http/CasinoGameAPI/balance/transfer/',
		self::API_depositToGame => '/http/CasinoGameAPI/balance/transfer/',
		self::API_isPlayerExist => '/http/CasinoGameAPI/balance/current/',
		self::API_queryForwardGame => '/http/CasinoGameAPI/game/start/',
		self::API_queryForwardGame2 => '/http/CasinoGameAPI/game/url/',
		self::API_logout => '/http/CasinoGameAPI/game/session/terminate/',
		self::API_syncGameRecords => '/DataFeeds/transactions/',
		self::API_queryTransaction => '/http/CasinoGameAPI/balance/transfer/status/',
		self::API_getGameProviderGamelist => '/http/CasinoGameAPI/getCasinoGames/',
        self::API_syncNewGameRecords => '/DataFeeds/gamerounds/',
        self::API_createFreeRoundBonus => '/http/FreeRoundsBonusAPI/createFRB/',
        self::API_createFreeRoundBonusV2 => '/http/FreeRoundsBonusAPI/v2/bonus/create',
        self::API_cancelFreeRoundBonus => '/http/FreeRoundsBonusAPI/cancelFRB/',
        self::API_queryFreeRoundBonus => '/http/FreeRoundsBonusAPI/getPlayersFRB/',
        self::API_queryIncompleteGames => '/DataFeeds/gamerounds/incomplete/',
        self::API_queryTournamentsWinners => '/http/tournaments/winners/',
		self::API_addPlayerToFreeRoundBonus => '/http/FreeRoundsBonusAPI/v2/players/add/',
        self::API_queryBetDetailLink => '/http/HistoryAPI/OpenHistoryExtended/',
        self::API_cancelGameRound => '/http/CasinoGameAPI/cancelRound',
	);

    const GAME_WIN = 'W';
	const GAME_BET = 'B';
	const GAME_REFUND = 'R';

    const MD5_FIELDS_FOR_ORIGINAL =[
    	'playerID',
		'extPlayerID',
		'gameID',
		'playSessionID',
		'timestamp',
		'referenceID',
		'type',
		'amount',
		// include new sync field
		'bet',
		'win',
		'start_date',
		'end_date',
		'status',
		'type_game_round'
	];
	const MD5_FLOAT_AMOUNT_FIELDS=[
		'amount',
		// include new sync field
		'bet',
		'win'
	];

	# Fields in game_logs we want to detect changes for merge, and when pragmaticplay_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=['game_code', 'amount', 'game_date','type','UserName'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['amount'];

    private $player_id_cache = [];

	public function __construct() {
        parent::__construct();
        $this->original_logs_table_name = 'pragmaticplay_game_logs';
		$this->api_url = $this->getSystemInfo('url');
        $this->language = $this->getSystemInfo('language');
		$this->currency = $this->getSystemInfo('currency');
		$this->secureLogin = $this->getSystemInfo('secureLogin');
		$this->secretKey = $this->getSystemInfo('secretKey');
		$this->lobby_url = $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url');
		$this->demo_url = $this->getSystemInfo('demo_url');
		$this->fishing_demo_url = $this->getSystemInfo('fishing_demo_url');
		$this->card_demo_url = $this->getSystemInfo('card_demo_url');
		$this->game_records_path = $this->getSystemInfo('game_records_path');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->cashier_url = $this->getSystemInfo('cashier_url');
		$this->use_new_sync_game_records = $this->getSystemInfo('use_new_sync_game_records', false); // set to true if apply to all clients
        $this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore', true);

		// applicable only for new sync
		$this->merge_free_spin_data = $this->getSystemInfo('merge_free_spin_data', false);
		$this->convertGamelogsToCsvFile = $this->getSystemInfo('convertGamelogsToCsvFile', true);
		$this->enabled_lobby = $this->getSystemInfo('enabled_lobby', true);
		$this->extra_info_currency_overwrite_player_currency=$this->getSystemInfo('extra_info_currency_overwrite_player_currency', false);
		$this->data_type_for_sync = $this->getSystemInfo('data_type_for_sync',['RNG']);
		$this->sleep_interval = $this->getSystemInfo('sleep_interval', '60');

		$this->forced_language_on_game_launch = $this->getSystemInfo('forced_language_on_game_launch', false);
		$this->card_games_list = $this->getSystemInfo('card_games_list', ['mpnn','qzpj','sang','qznn','black','brnn','erba','gflower','holdem','ksznn','lznn','ddz']);
		$this->request_id = $this->getPlatformCode().$this->CI->utils->getTimestampNow();
		$this->enabled_query_afterbalance_on_trans_table = $this->getSystemInfo('enabled_query_afterbalance_on_trans_table', false);

		$this->use_create_free_round_v2 = $this->getSystemInfo('use_create_free_round_v2',false);
		$this->http_method = self::HTTP_METHOD_POST;

        // modify game username
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 50);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);


        $this->enable_data_validation_per_currency = $this->getSystemInfo('enable_data_validation_per_currency', false);
        $this->filter_game_list_by_game_types = $this->getSystemInfo('filter_game_list_by_game_types', []); // vs, bj, cs, rl, bn, bc, lg, sc, empty[all]
	}

	public function getPlatformCode() {
		return PRAGMATICPLAY_API;
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
		$this->api_name = $apiName;
		$apiUri = self::URI_MAP[$apiName];
		$req_params = http_build_query($params);
        $url = $this->api_url . $apiUri;

		if ($this->http_method == self::HTTP_METHOD_GET) {
			$url .= "?" . $req_params;
		}

		if($apiName == self::API_createFreeRoundBonusV2 || $apiName == self::API_addPlayerToFreeRoundBonus){
			return $this->api_url;
		}

		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if ($this->http_method == self::HTTP_METHOD_POST) {
			$postData = http_build_query($params);
			if($this->api_name == self::API_createFreeRoundBonusV2 || $this->api_name == self::API_addPlayerToFreeRoundBonus){
				$header = [
					'Content-Type: application/json',
				];
				$postData = json_encode($params);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			}
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		}
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$success = false;
		if(isset($resultArr['error'])&&$resultArr['error']=='0'||$resultArr['error']=='17'){
			$success = true;
		}

        //for transfer deposit only
        if(isset($resultArr['error']) && $resultArr['error'] == '1' && $apiName == self::API_depositToGame){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PRAGMATICPLAY_API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function isPlayerExist($userName){
        $this->http_method = self::HTTP_METHOD_POST;
       	$playerName = $this->getGameUsernameByPlayerUsername($userName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'externalPlayerId' => empty($playerName)?$userName:$playerName,
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

    private function getPlayerPragmaticPlayCurrency($username){
        if($this->extra_info_currency_overwrite_player_currency){
            return $this->currency;
        }

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

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $this->http_method = self::HTTP_METHOD_POST;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'currency' => $this->getPlayerPragmaticPlayCurrency($playerName),
			'externalPlayerId' => $playerName,
			'secureLogin' => $this->secureLogin,
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		if ($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
		return array($success, $resultArr);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function QueryBalanceGameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

	public function queryPlayerBalance($userName) {
        if ($this->isSeamLessGame()) {
            return parent::queryPlayerBalance($userName);
       } else {
            $this->http_method = self::HTTP_METHOD_POST;
            $playerName = $this->getGameUsernameByPlayerUsername($userName);
            $this->request_id .= "|" . $userName;
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryPlayerBalance',
                'playerName' => $playerName,
                'sbe_username' => $userName,
            );

            $params = array(
                'externalPlayerId' => $playerName,
                'secureLogin' => $this->secureLogin,
            );

            $params['hash'] = MD5(http_build_query($params).$this->secretKey);

            return $this->callApi(self::API_queryPlayerBalance, $params, $context);
       }
	}

	public function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array();
		$result['balance'] = $this->QueryBalanceGameAmountToDB($resultArr['balance']);

		$success = true;
		if($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
			$this->CI->utils->debug_log('PRAGMATIC PLAY GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
		}else{
			$this->CI->utils->debug_log('PRAGMATIC PLAY GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
		}
		$result['exists'] = true;
		return array($success, $result);

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
        $this->http_method = self::HTTP_METHOD_POST;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$ext = $gameUsername.date("ymdHis");
		if(!empty($transfer_secure_id)){
			$ext = $this->prefix_for_transaction_id . $transfer_secure_id;
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
		);

		$params = array(
			'amount' => $type==self::TRANSFER_IN ? $this->dBtoGameAmount($amount) : '-'.$this->dBtoGameAmount($amount),  # self::TRANSFER_IN positive number self::TRANSFER_OUT negative number
			'externalPlayerId'=> $gameUsername,
			'externalTransactionId' => $ext,
			'secureLogin' => $this->secureLogin
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		if ($type == self::TRANSFER_IN) {
			return $this->callApi(self::API_depositToGame, $params, $context);
		}
		return $this->callApi(self::API_withdrawFromGame, $params, $context);

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
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {

            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            if ($playerId) {
                // $playerBalance = $this->queryPlayerBalance($playerName);
                // $afterBalance = 0;

				// if(isset($resultArr['balance'])) {
				// 	$resultArr['balance'] = $this->gameAmountToDB($resultArr['balance']);
				// }

                // if($type == self::TRANSFER_IN){ // Deposit
                // 	if ($playerBalance && $playerBalance['success']) {
	               //      $afterBalance = $playerBalance['balance'];
	               //  } else {
	               //      //IF GET PLAYER BALANCE FAILED
	               //      $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	               //      $afterBalance = $rlt->totalBalanceAmount;
	               //      $this->CI->utils->debug_log('============= PRAGMATIC PLAY AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	               //  }
	               //  // $responseResultId = $result['response_result_id'];
	               //  // Deposit
	               //  $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
	               //      $this->transTypeMainWalletToSubWallet());
                // }else{ // Withdraw
                // 	if ($playerBalance && $playerBalance['success']) {
	               //      $afterBalance = $playerBalance['balance'];
	               //      $this->CI->utils->debug_log('============= PRAGMATIC PLAY AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
	               //  } else {
	               //      //IF GET PLAYER BALANCE FAILED
	               //      $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	               //      $afterBalance = $rlt->totalBalanceAmount;
	               //      $this->CI->utils->debug_log('============= PRAGMATIC PLAY AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	               //  }
	               //  // $responseResultId = $result['response_result_id'];
	               //  // Withdraw
	               //  $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
	               //      $this->transTypeSubWalletToMainWallet());
                // }
            	$result['didnot_insert_game_logs']=true;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            } else {
				$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$gameUsername.' getPlayerIdInGameProviderAuth');
            }
        } else {
			$error_code = @$resultArr['error'];
			if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
                $result['reason_id']= $this->getReason($error_code);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
		}

		#on timeout and verify_transfer_using_query_transaction is true, verify transaction status
		if($this->verify_transfer_using_query_transaction && self::COMMON_TRANSACTION_STATUS_UNKNOWN === $result['transfer_status']){
			$query_transaction_extra['playerName'] = $playerName;
			$query_transaction_extra['playerId'] = null; #unnecessary param
			$query_transaction_result = $this->queryTransaction($external_transaction_id, $query_transaction_extra);

			if(self::COMMON_TRANSACTION_STATUS_APPROVED === $query_transaction_result['status']){
				$success = true;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
				$result['didnot_insert_game_logs']=true;
			}elseif(self::COMMON_TRANSACTION_STATUS_DECLINED === $query_transaction_result['status']){
				$success = false;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
			}else{
				#do nothing
			}
		}

		return array($success, $result);
	}

	private function getReason($error_code){
		switch($error_code) {
			case '1' :
				return self::REASON_NO_ENOUGH_BALANCE;
			case '2' :
				return self::REASON_NOT_FOUND_PLAYER;
			case '4' :
			case '5' :
				return self::REASON_INVALID_KEY;
			case '6' :
				return self::REASON_GAME_ACCOUNT_LOCKED;
			case '7' :
				return self::REASON_INCOMPLETE_INFORMATION;
			case '100' :
			case '120' :
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
		}
	}

	public function getLauncherLanguage($language){
        $lang='';
        $language = strtolower($language);
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
			case 'en-us':
			case 'en-US':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indo
                break;
			case Language_function::INT_LANG_VIETNAMESE:
			case 'vi':
			case 'vi-vn':
            case 'vi-vi':
				$lang = 'vi'; // vietnamese
				break;
			case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-kr':
				$lang = 'ko'; // korean
				break;
			case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-th':
				$lang = 'th'; // thailand
				break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-HI':
            case 'hi_HI':
                $lang = 'hi'; //hindi
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
			case 'pt-br':
			case 'pt-BR':
			case 'pt-pt':
                $lang = 'pt'; //portuguese
                break;
            case Language_function::INT_LANG_SPANISH:
            case 'es':
            case 'es-ES':
            case 'es_ES':
            case 'es-es':
            case 'es_es':
                $lang = 'es'; //spanish
                break;
            case Language_function::INT_LANG_KAZAKH:
            case 'kk':
            case 'kk-KZ':
            case 'kk_KZ':
                $lang = 'kk'; //kazakh
                break;
			case Language_function::INT_LANG_JAPANESE:
			case 'ja':
					$lang = 'ja'; //japanese
					break;
			case Language_function::INT_LANG_FILIPINO:
            case 'ph':
            case 'fil-ph':
                $lang = 'tl'; //ph
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName,$extra=null) {
        $this->http_method = self::HTTP_METHOD_POST;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$player_id = $this->getPlayerIdByGameUsername($gameUsername);
        $language = $this->getLauncherLanguage(!empty($this->language) ? $this->language : $extra['language']);

		if ($this->forced_language_on_game_launch !== false) {
			/*
				Possible values for languange in extra info 'en', 'zh', 'id', 'vi', 'ko', 'th'
			 */

			$language = $this->forced_language_on_game_launch;
		}


		$platform = $extra['is_mobile']?"MOBILE":"WEB";

		$this->lobby_url = $extra['is_mobile']
				  		 ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
				  		 : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');

		$this->CI->utils->debug_log('extra =============>',$extra);
		if (array_key_exists("extra", $extra)) {
            if(isset($extra['extra']['t1_lobby_url'])) {
                $this->lobby_url = $extra['extra']['t1_lobby_url'];
            }
		}
        
        $gameMode = isset($extra['game_mode'])?$extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
			if (in_array($extra['game_code'], $this->card_games_list)) {
				$demoUrl = $this->card_demo_url."?gameSymbol=".$extra['game_code']."&lang=".$language."&cur=".$this->getPlayerPragmaticPlayCurrency($playerName)."&lobbyURL=".$this->lobby_url;
			} else if ($extra['game_code'] == self::FISHING_GAME) {
				$demoUrl = $this->fishing_demo_url;
			} else {
				$demoUrl = $this->demo_url."?gameSymbol=".$extra['game_code']."&lang=".$language."&cur=".$this->getPlayerPragmaticPlayCurrency($playerName)."&lobbyURL=".$this->lobby_url;
			}
			return array("success"=>true,"url"=>$demoUrl);
		}else{

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryForwardGame',
				'language' => $language,
				'playerName' => $playerName,
				'playerId' => $player_id
			);

			$params = array(
				'cashierURL' => isset($this->cashier_url)?$this->cashier_url:$extra['cashierURL'],
				'externalPlayerId' => $gameUsername,
				'gameId' => $extra['game_code'],
				'language' => $language,
				'lobbyURL' => $this->enabled_lobby ? $this->lobby_url : "",
				'platform' => $platform,
				'secureLogin' => $this->secureLogin
			);

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

	public function logout($userName, $password = null) {
        $this->http_method = self::HTTP_METHOD_POST;
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName
		);

		$params = array(
			'externalPlayerId'=> !empty($playerName)?$playerName:$userName,
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
        $this->http_method = self::HTTP_METHOD_GET;
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	// $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());
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

				if ($this->use_new_sync_game_records) {
					$api = self::API_syncNewGameRecords;
				} else {
					$api = self::API_syncGameRecords;
				}

				$rtn[] = $this->callApi($api, $params, $context);

				$queryDateTimeStart = $endDateParam;
				$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

			}
			sleep($this->sleep_interval);
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

	public function preProcessGameRecords(&$gameRecords,$extra){
		$preResult = array();


		foreach($gameRecords as $index => $record) {

			
			$this->CI->utils->debug_log('PP - preProcessGameRecords: ', [
				'record' => $record,
				'record[8]' => $record[8],
				'substr($record[8], 0, 3)' => substr($record[8], 0, 3),
				'condition: isset($record[8]) && $this->currency != substr($record[8], 0, 3)' => isset($record[8]) && $this->currency != substr($record[8], 0, 3)
			]);

			if (isset($record[8]) && $this->currency != substr($record[8], 0, 3)) break;

			//Data from Pragmatic Play API
			$preResult[$index]['playerID'] 		   = isset($record['0']) ? $record['0'] : NULL;
			$preResult[$index]['extPlayerID'] 	   = isset($record['1']) ? $record['1'] : NULL;
			$preResult[$index]['gameID'] 			   = isset($record['2']) ? $record['2'] : NULL;
			$preResult[$index]['playSessionID'] 	   = isset($record['3']) ? $record['3'] : NULL;
			$preResult[$index]['timestamp'] 		   = isset($record['4']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $record['4']/1000)) : NULL;
			$preResult[$index]['referenceID'] 	   = isset($record['5']) ? $record['5'] : NULL;
			$preResult[$index]['type'] 			   = isset($record['6']) ? $record['6'] : NULL;
			$preResult[$index]['amount'] 			   = isset($record['7']) ? $record['7'] : NULL;
			$preResult[$index]['currency'] 		   = isset($record['8']) ? $record['8'] : NULL;

			//extra info from SBE
			$preResult[$index]['Username'] 		   = isset($record['1']) ? $record['1'] : NULL;
			$preResult[$index]['SbePlayerId'] 	   = NULL;
			$preResult[$index]['related_uniqueid']   = $record['0'].$record['2'].$record['3'];
			if ($record['2'] == self::FISHING_GAME) {
				$preResult[$index]['external_uniqueid']  = $record['0'].$record['2'].$record['3'].$record['5']; //playerid + gameid + playsessionid+referenceID
			} else {
				$preResult[$index]['external_uniqueid']  = $record['0'].$record['2'].$record['3'].$record['6']; //playerid + gameid + playsessionid+type
			}

			$preResult[$index]['response_result_id'] = $extra['responseResultId'];

			// new sync(data) set to null included in md5
			$preResult[$index]['start_date']       = NULL;
			$preResult[$index]['end_date']         = NULL;
			$preResult[$index]['bet']              = NULL;
			$preResult[$index]['win']              = NULL;
			$preResult[$index]['status']           = NULL;
			$preResult[$index]['type_game_round']  = NULL;

			$preResult[$index]['last_sync_time']	 = $this->CI->utils->getNowForMysql();
		}
		$gameRecords = $preResult;
	}

	 public function getGameTimeToServerTimeNewSync() {
        return $this->getSystemInfo('gameTimeToServerTimeNewSync','+8 hours');
    }

    public function gameTimeToServerTimeNewSync($dateTimeStr) {
        if (is_object($dateTimeStr) && $dateTimeStr instanceof DateTime) {
            $dateTimeStr = $dateTimeStr->format('Y-m-d H:i:s');
        }
        $modify = $this->getGameTimeToServerTimeNewSync();
        return $this->utils->modifyDateTime($dateTimeStr, $modify);
    }

	// for new api /DataFeeds/gamerounds/
	public function preProcessNewGameRecords(&$gameRecords,$extra){
        $preResult = array();
		foreach($gameRecords as $index => $record) {

			$this->CI->utils->debug_log('PP - preProcessNewGameRecords: ', [
				'record' => $record,
				'record[11]' => $record[11],
				'substr($record[11], 0, 3)' => substr($record[11], 0, 3),
				'condition: isset($record[11]) && $this->currency != substr($record[11], 0, 3)' => isset($record[11]) && $this->currency != substr($record[11], 0, 3)
			]);

			if($this->enable_data_validation_per_currency){

				$recordCurrency = trim( $record[11]);

				if (isset($record[11]) && $this->currency != $recordCurrency ) break;
			}


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

			$preResult[$index]['playerID'] 		   = isset($record['0']) ? $record['0'] : NULL;
			$preResult[$index]['extPlayerID'] 	   = isset($record['1']) ? $record['1'] : NULL;
			$preResult[$index]['gameID'] 		   = isset($record['2']) ? $record['2'] : NULL;
			$preResult[$index]['playSessionID']    = isset($record['3']) ? $record['3'] : NULL;
			$preResult[$index]['parent_session_id']= isset($record['4']) ? $record['4'] : NULL;
			$preResult[$index]['start_date']       = isset($record['5']) && $record['5'] != "null" ? $this->gameTimeToServerTimeNewSync($record['5']) : NULL;

			# use start date if end date is empty (meaning round not yet finished)
			$preResult[$index]['end_date']         = isset($record['6']) && $record['6'] != "null" ? $this->gameTimeToServerTimeNewSync($record['6']) : $preResult[$index]['start_date'];

			$preResult[$index]['status']           = isset($record['7']) ? $record['7'] : NULL;    # status (I - Inprogress , C - Complete)
			$preResult[$index]['type_game_round']  = isset($record['8']) ? $record['8'] : NULL;	   # type ( R - game round, F - free spin)
			$preResult[$index]['bet']              = isset($record['9']) ? $record['9'] : NULL;
			$preResult[$index]['win']              = isset($record['10']) ? $record['10'] : NULL;
			$preResult[$index]['currency'] 		   = isset($record['11']) ? $record['11'] : NULL;
            $preResult[$index]['jackpot'] 		   = isset($record['12']) ? $record['12'] : NULL;

            $preResult[$index]['SbePlayerId'] 	   = $playerId;

			// set null for old api  ===> self::API_syncGameRecords => '/DataFeeds/transactions/',
			$preResult[$index]['timestamp'] 	   = NULL;
			$preResult[$index]['referenceID'] 	   = NULL;
			$preResult[$index]['type'] 			   = NULL;
			$preResult[$index]['amount'] 		   = NULL;

			$preResult[$index]['last_sync_time']	 = $this->CI->utils->getNowForMysql();

			//extra info from SBE
			$preResult[$index]['Username'] 		     = isset($record['1']) ? $record['1'] : NULL;

			$preResult[$index]['external_uniqueid']  = $preResult[$index]['playSessionID'];
			$preResult[$index]['related_uniqueid']   = $preResult[$index]['playSessionID'];
			$preResult[$index]['response_result_id'] = $extra['responseResultId'];
		}
		$gameRecords = array_values( $preResult );
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_logs_table_name, $record);
                } else {
                    unset($record['id']);
                    if ($this->use_insert_ignore) {
                        $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->original_logs_table_name, $record);
                    } else {
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_logs_table_name, $record);
                    }
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('pragmaticplay_game_logs','original_game_logs_model','external_system'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$csvtext = $params['resultText'];
		$gameRecords = $this->convertGamelogsToCsvFileAndRead($startDate,$endDate,$csvtext);

		$result = array(
			'data_count'=> 0
		);
		$dataCount = 0;

		$this->CI->utils->debug_log('PP - processResultForSyncOriginalGameLogs: ', [
			'$gameRecords' => $gameRecords,
			'$this->currency' => $this->currency,
		]);

		if(!empty($gameRecords)){
			$extra = ['responseResultId'=>$responseResultId];

			if ($this->use_new_sync_game_records) {
				$this->preProcessNewGameRecords($gameRecords,$extra);
			} else {
				$this->preProcessGameRecords($gameRecords,$extra);
			}

			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_logs_table_name,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
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
		//old
  //       $availableRows = $this->CI->pragmaticplay_game_logs->getAvailableRows($gameRecords);

		// if (!empty($availableRows)) {
		// 	foreach ($availableRows as $record) {
		// 		$insertRecord = array();
		// 		$playerID = $this->getPlayerIdInGameProviderAuth($record['1']);

		// 		//Data from Pragmatic Play API
		// 		$insertRecord['playerID'] = isset($record['0']) ? $record['0'] : NULL;
		// 		$insertRecord['extPlayerID'] = isset($record['1']) ? $record['1'] : NULL;
		// 		$insertRecord['gameID'] = isset($record['2']) ? $record['2'] : NULL;
		// 		$insertRecord['playSessionID'] = isset($record['3']) ? $record['3'] : NULL;
		// 		$insertRecord['timestamp'] = isset($record['4']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $record['4']/1000)) : NULL;
		// 		$insertRecord['referenceID'] = isset($record['5']) ? $record['5'] : NULL;
		// 		$insertRecord['type'] = isset($record['6']) ? $record['6'] : NULL;
		// 		$insertRecord['amount'] = isset($record['7']) ? $record['7'] : NULL;
		// 		$insertRecord['currency'] = isset($record['8']) ? $record['8'] : NULL;

		// 		//extra info from SBE
		// 		$insertRecord['Username'] = isset($record['1']) ? $record['1'] : NULL;
		// 		$insertRecord['SbePlayerId'] = $playerID;
		// 		$insertRecord['related_uniqueid'] = $insertRecord['playerID'].$insertRecord['gameID'].$insertRecord['playSessionID'];
		// 		$insertRecord['external_uniqueid'] = $insertRecord['playerID'].$insertRecord['gameID'].$insertRecord['playSessionID'].$insertRecord['type']; //playerid + gameid + playsessionid+type
		// 		$insertRecord['response_result_id'] = $responseResultId;
		// 		//insert data to Pragmatic Play gamelogs table database
		// 		$this->CI->pragmaticplay_game_logs->insertGameLogs($insertRecord);
		// 		$dataCount++;
		// 	}
		// }
		// $result['data_count'] = $dataCount;

		return array(true, $result);
	}

	public function syncMergeToGameLogs($token) {

		if ($this->use_new_sync_game_records) {
			$enabled_game_logs_unsettle = true;
		} else {
			$enabled_game_logs_unsettle = false;    # game provider response for this function  /req/settles is only settle and dont provide game status on there response
		}

        return $this->commonSyncMergeToGameLogs($token,
	        $this,
	        [$this, 'queryOriginalGameLogs'],
	        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
	        [$this, 'preprocessOriginalRowForGameLogs'],
	        $enabled_game_logs_unsettle);
		//old
		// $this->CI->load->model(array('game_logs', 'player_model', 'pragmaticplay_game_logs'));

		// $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		// $dateTimeFrom->modify($this->getDatetimeAdjust());
		// $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		// //observer the date format
		// $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		// $endDate = $dateTimeTo->format('Y-m-d H:i:s');

		// $rlt = array('success' => true);

		// $result = $this->CI->pragmaticplay_game_logs->getGameLogStatistics($startDate, $endDate);
		// $cnt = 0;
		// if (!empty($result)) {
  //           $this->processedGameRecordsBeforeMerging($result);
		// 	$unknownGame = $this->getUnknownGame();
		// 	foreach ($result as $row) {
  //               $realbet = (float)$row['bet_amount'];
  //               // $result_amount = (float)$row['result_amount'] - (float)$row['bet_amount'];
  //               $result_amount = (float)$row['result_amount'];
		// 		$cnt++;

		// 		$game_description_id = $row['game_description_id'];
		// 		$game_type_id = $row['game_type_id'];

		// 		if (empty($game_description_id)) {
  //                   list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
		// 		}

		// 		$extra = array(
		// 				'trans_amount' => $this->gameAmountToDB($realbet),
		// 				'table' => $row['external_uniqueid'],
		// 				'sync_index' => $row['id'],
		// 			);

		// 		$this->syncGameLogs(
		// 			$game_type_id,
		// 			$game_description_id,
		// 			$row['game_code'],
		// 			$row['game_type'],
		// 			$row['game'],
		// 			$row['SbePlayerId'],
		// 			$row['UserName'],
		// 			$this->gameAmountToDB($realbet),
		// 			$this->gameAmountToDB($result_amount),
		// 			null, # win_amount
		// 			null, # loss_amount
		// 			null, # after_balance
		// 			0, # has_both_side
		// 			$row['external_uniqueid'],
		// 			$row['game_date'], //start
		// 			$row['game_date'], //end
		// 			$row['external_uniqueid'],
		// 			Game_logs::FLAG_GAME,
  //                   $extra
		// 		);

		// 	}
		// }

		// $this->CI->utils->debug_log('PRAGMATIC PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		// $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		// return $rlt;
	}

	 private function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);

        switch ($status) {
            case 'i':
                $status = Game_logs::STATUS_PENDING;
                break;
            case 'v':
                $status = Game_logs::STATUS_VOID;
                break;
            case 'c':
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

        if ($this->use_new_sync_game_records) {
        	$row['status'] = $this->getGameRecordsStatus($row['status']);
        } else {
	        $row['status'] = Game_logs::STATUS_SETTLED;
		}

		$betDetails = [
			'game ID' => $row['game_code'],
			'roundId' => $row['round_id'],
			'start_at' => $row['start_date'],
			'end_at' => $row['end_date'],
			'game_round_id(R - game round, R - free round)' => $row['type_game_round']
		];

		$row['bet_details'] = $betDetails;
	}


	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if ($this->use_new_sync_game_records) {
        	$calc_result = $row['win'] - $row['bet'];
        	$bet_amount =  $this->gameAmountToDB($row['bet']);
        	$result_amount = $this->gameAmountToDB($calc_result);

        	$start_date = $row['start_date'];
        	$end_date = $row['end_date'];
        } else {
        	$bet_amount = $this->gameAmountToDB($row['bet_amount']);
        	$result_amount = $this->gameAmountToDB($row['result_amount']);

        	$start_date = $row['game_date'];
        	$end_date = $row['game_date'];
        }

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
                'player_username' => $row['UserName']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $this->queryAfterBalanceOnTransTable($row['external_uniqueid'])
            ],
            'date_info' => [
                'start_at' => $start_date,
                'end_at' => $end_date,
                'bet_at' => $start_date,
                'updated_at' => $end_date,
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['id'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];

        return $data;
    }

    /**
     * [processedGameRecordsBeforeMerging
     *   - merge related uniqueids and compute result amount and bet amount
     *   - query related uniqueids in original game logs
     * ]
     * @param  [array] &$rows [unprocessed game logs]
     * @return [array]        [returns prepared game logs]
     */
    private function processedGameRecordsBeforeMerging(&$rows){
        if (!empty($rows)) {
	        $processedGameRecords = $mapExternalRelatedIds = [];
	        $relatedExternalUniqueids = array_unique(array_column($rows, 'external_uniqueid'));

	        $mapOriginalGameLogs = $this->getRelatedUniqueIds($relatedExternalUniqueids);
	        $this->prepareGameLogsBeforeRecalculating($mapOriginalGameLogs);

	        foreach ($rows as $key => $row) {
	            // if ($row['external_uniqueid'] != '14565419bjma384830134002') continue;
	            if (in_array($row['external_uniqueid'], $mapExternalRelatedIds) || $row['type']==self::GAME_WIN) continue;
	            array_push($mapExternalRelatedIds, $row['external_uniqueid']);

	            $betCount = 0;
	            if ($row['type'] == self::GAME_BET) {
	                $processedGameRecords[$key] = $row;

	                #handle multiple bets: $row['type'] = "B"
	                $betCount = count($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_BET]);
	                if (count($betCount > 1)){
	                    $betAmount = array_column($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_BET], 'amount');
	                    $processedGameRecords[$key]['bet_amount'] = array_sum($betAmount);
	                    $processedGameRecords[$key]['result_amount'] = -array_sum($betAmount);
	                }else{
	                    $processedGameRecords[$key]['bet_amount'] = $row['amount'];
	                    $processedGameRecords[$key]['result_amount'] = -$row['amount'];
	                }
	            }

	            #compute bet amount and win amount - override value of 'result_amount' above
	            if (!empty($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_BET]) && !empty($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_WIN])) {
					// print_r($processedGameRecords);exit;
					$winAmount = @array_column($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_WIN], 'amount');
					$refundAmount = @array_column($mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_REFUND], 'amount');

					if(!$winAmount){
						$winAmount = [];
					}

					if(!$refundAmount){
						$refundAmount = [];
					}

					//$winAmountAndBetAmount = $mapOriginalGameLogs[$row['external_uniqueid']][self::GAME_WIN]['amount'];
					$winAmountAndBetAmount = (array_sum($winAmount)+array_sum($refundAmount));
	                if (!empty($betCount) && $betCount > 1) {
	                    $processedGameRecords[$key]['result_amount'] = (array_sum($winAmount)+array_sum($refundAmount)) - array_sum($betAmount);
	                }else{
	                    $processedGameRecords[$key]['result_amount'] = $winAmountAndBetAmount - $row['amount'];
	                }
	            }
	        }

	        $rows = $processedGameRecords;
	        unset($processedGameRecords,$mapOriginalGameLogs,$relatedExternalUniqueids,$mapExternalRelatedIds);
        }
    }

    /**
     * [getRelatedUniqueIds query all related unique ids]
     * @param  [array] $externalUniqueIds [related unique ids]
     * @return [array]                    [returns all realted uniqueids]
     */
    public function getRelatedUniqueIds($externalUniqueIds){
        foreach ($externalUniqueIds as &$row) {
            $row = "'". $row ."'";
        }
        $externalUniqueIds = implode(",", $externalUniqueIds);

        $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
            SELECT * FROM pragmaticplay_game_logs WHERE related_uniqueid in ($externalUniqueIds)
EOD;

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, []);
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
                $data[$row['related_uniqueid']][$row['type']][$key] = $row;
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
        $this->http_method = self::HTTP_METHOD_POST;
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
			if(isset($resultArr['status']) && $resultArr['status'] == 'Not found') {
				$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id']=self::REASON_TRANSACTION_NOT_FOUND;
			}else{
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			}
        }else{
			if($resultArr['status'] == 'Not found') {
				$result['reason_id']=self::REASON_TRANSACTION_NOT_FOUND;
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

    	$sqlFreeSpin = '';
    	if($this->use_new_sync_game_records) {
      		$sqlTime='pgl.end_date >= ? and pgl.end_date <= ?';

			// R - Game Round , F - Free Spin
      		if(!$this->merge_free_spin_data) {
				$sqlFreeSpin=' and pgl.type_game_round = "R" ';
      		}
    	} else {
    		$sqlTime='pgl.timestamp >= ? and pgl.timestamp <= ?';
    	}

        $sql = <<<EOD
SELECT pgl.id as id,
pgl.SbePlayerId,
pgl.UserName,
pgl.related_uniqueid as external_uniqueid,
pgl.timestamp AS game_date,
pgl.gameID AS game_code,
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
pgl.last_sync_time as updated_at,

pgl.playSessionID as round_id,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM {$this->original_logs_table_name} as pgl

left JOIN game_description as gd ON pgl.gameID = gd.game_code and gd.game_platform_id=?
JOIN game_provider_auth ON pgl.UserName = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}
{$sqlFreeSpin}
EOD;
	$params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        // only for old syncing (cause merging slow)
        if (!$this->use_new_sync_game_records) {
	        $this->processedGameRecordsBeforeMerging($result);
        }



        return $result;
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
        $this->http_method = self::HTTP_METHOD_POST;

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

	public function syncOriginalGameLogsByTimestamp($timestamp, $dataType) {
		$this->http_method = self::HTTP_METHOD_GET;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $timestamp,
			'endDate' => $timestamp
		);

		$params = array(
			'login' => $this->secureLogin,
			'password' => $this->secretKey,
			'timepoint' => $timestamp,
			'dataType' => $dataType
		);

		if ($this->use_new_sync_game_records) {
			$api = self::API_syncNewGameRecords;
		} else {
			$api = self::API_syncGameRecords;
		}

        return $this->callApi($api, $params, $context);
    }

    public function createFreeRound($playerName, $extra = []) {
		if($this->use_create_free_round_v2){
			return $this->createFreeRoundV2($playerName, $extra);
		}
        $this->http_method = self::HTTP_METHOD_POST;
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
        $this->http_method = self::HTTP_METHOD_POST;
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

	public function createFreeRoundV2($playerName, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $transaction_id = $this->getSecureId('free_round_bonuses', 'transaction_id', true, 'B', 29);
		$currency = $this->currency;
		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		if(!$game_username){
			return [
				'success' => false,
				'message' => '['.$playerName.'] player not found'
			];
		}

        $context = array(
            'callback_obj' 		=> $this,
            'callback_method' 	=> 'processResultForCreateFreeRoundV2',
            'expired_at' 		=> $extra->expirationDate,
            'transaction_id' 	=> $transaction_id,
            'currency' 			=> $currency,
            'game_username' 	=> $game_username,
            'rounds' 			=> $extra->rounds,
            'extra' 			=> $extra
        );

		$get_params = [
            'secureLogin' 	=> $this->secureLogin,
            'bonusCode'		=> $transaction_id,
			'startDate'		=> time(),
			'expirationDate'=> strtotime($extra->expirationDate),
			'rounds'		=> $extra->rounds,
			'validityDate'	=> strtotime($extra->validityDate),
		];

		ksort($get_params);

        $get_params['hash'] 	= MD5(http_build_query($get_params).$this->secretKey);

		$this->api_url = $this->api_url. self::URI_MAP[self::API_createFreeRoundBonusV2] .'?'. http_build_query($get_params);

		$params['gameList'] = $extra->gameList;

        return $this->callApi(self::API_createFreeRoundBonusV2, $params, $context);
    }

    public function processResultForCreateFreeRoundV2($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $rounds = $this->getVariableFromContext($params, 'rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $game_username = $this->getVariableFromContext($params, 'game_username');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');

		$player_id = $this->getPlayerIdByGameUsername($game_username);

		$this->CI->utils->debug_log('PP-processResultForCreateFreeRoundV2', $resultArr);
        if ($success){
            $return = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];
			
			#if success then use this api to add player on created bonus
			$data = [
				"player_id" 		=> $player_id,
				"currency" 			=> $currency,
				"game_username" 	=> $game_username,
				"rounds" 			=> $rounds,
				"extra" 			=> $extra,
				"transaction_id" 	=> $transaction_id,
				"expired_at" 		=> $expired_at,
			];
			$success_adding_player = $this->addPlayerToFreeRoundBonus($data);
			if(!$success_adding_player['success']){
				$success = false;
				$return  = $success_adding_player['message'];
			}
        }
        else {
            $return = [
                'message' => $resultArr['description']
            ];
        }
        return array($success, $return);
    }

	public function addPlayerToFreeRoundBonus($data){
		$this->http_method = self::HTTP_METHOD_POST;
		$player_id = $data['player_id'];
		$context = array(
            'callback_obj' 		=> $this,
            'callback_method' 	=> 'processResultForaddPlayerToFreeRoundBonus',
            'currency' 			=> $data['currency'],
            'game_username' 	=> $data['game_username'],
			'player_id'			=> $player_id,
            'rounds' 			=> $data['rounds'],
            'extra' 			=> $data['extra'],
			'transaction_id'	=> $data['transaction_id'],
			'expired_at'		=> $data['expired_at'],
        );

		$get_params = [
            'secureLogin' 	=> $this->secureLogin,
            'bonusCode'		=> $data['transaction_id']
		];

		ksort($get_params);

        $get_params['hash'] = MD5(http_build_query($get_params).$this->secretKey);

		$this->api_url = $this->getSystemInfo('url') . self::URI_MAP[self::API_addPlayerToFreeRoundBonus] .'?'. http_build_query($get_params);

		$params['playerList'] = [$data['game_username']];

        return $this->callApi(self::API_addPlayerToFreeRoundBonus, $params, $context);
	}

	public function processResultForaddPlayerToFreeRoundBonus($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $rounds = $this->getVariableFromContext($params, 'rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $game_username = $this->getVariableFromContext($params, 'game_username');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');
		$player_id = $this->getVariableFromContext($params, 'player_id');
		$this->CI->utils->debug_log('PP-processResultForaddPlayerToFreeRoundBonus', $resultArr);
		$result = [
			'success'=> false,
			'message'=> isset($resultArr['description']) ? $resultArr['description'] : '',
		];
		if($success){
			$result = [
				'success' => true,
			];

			$this->CI->load->model(array('free_round_bonus_model'));
			$data = [
				'player_id' => $player_id,
				'game_platform_id' => $this->getPlatformCode(),
				'free_rounds' => $rounds,
				'transaction_id' => $transaction_id,
				'currency' => $currency,
				'expired_at' => $expired_at,
				'extra' => $extra,
				'raw_data' => json_encode($extra),
			];
			$this->CI->free_round_bonus_model->insertTransaction($data);
		}

        return $result;
	}

    public function queryFreeRound($playerName, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
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

    public function queryIncompleteGames($playerName) {
    	$this->http_method = self::HTTP_METHOD_GET;
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

    private function queryAfterBalanceOnTransTable($uniqueid){
    	if(!$this->enabled_query_afterbalance_on_trans_table){
    		return null;
    	}
    	$table = isset($this->transaction_table_name) ? $this->transaction_table_name : null;
    	if($table == null){
    		return null;
    	}

    	$this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
select after_balance from {$table}  where  id=(
   select max(id) from {$table}  where round_id="{$uniqueid}")
EOD;

        $this->CI->utils->debug_log('queryTransactionsForUpdate sql', $sql, []);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, []);
        $afterBalance = null;
        if(isset($result['after_balance'])){
        	$afterBalance = $result['after_balance'];
        }
        return $afterBalance;
    }

    public function syncTournamentsWinners($token = false) {
        $this->http_method = self::HTTP_METHOD_GET;
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		#observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');

		$rlt = array();

		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+24 hours', function($startDate, $endDate) use(&$rlt) {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncTournamentsWinners',
				'startDate' => $startDate,
				'endDate' => $endDate,
			);
			$params = array(
				'login' 	=> $this->secureLogin,
				'password' 	=> $this->secretKey,
				'startDate' => $startDate,
				'endDate'	=> $endDate,
			);

			$rlt[] = $this->callApi(self::API_queryTournamentsWinners, $params, $context);
			return true;
		});
		return array('success' => true, $rlt);
    }

    public function processResultForSyncTournamentsWinners($params){
    	$this->CI->load->model(array('original_game_logs_model'));
    	$resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
        	"data_count_insert" => 0,
        	"data_count_update" => 0
        );
        if($success){
        	$tournaments_winners_records = $this->preProcessTournamentsWinnersRecords($resultArr, $responseResultId);
        	if(!empty($tournaments_winners_records)){
        		list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                'game_tournaments_winners',
	                $tournaments_winners_records,
	                'external_unique_id',
	                'external_unique_id',
	                ['position'],
	                'md5_sum',
	                'id',
	                ['prizeAmount','prizeCoins']
	            );
        	}

			if (!empty($insertRows)) {
				$result['data_count_insert'] += $this->updateOrInsertTournamentData($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count_update'] += $this->updateOrInsertTournamentData($updateRows, 'update');
			}
			unset($updateRows);
        }
		return array(true, $result);
    }

    public function preProcessTournamentsWinnersRecords($resultArr,$responseResultId){
    	$tournaments_winners_records = [];
    	$tournaments = isset($resultArr['tournaments']) ? $resultArr['tournaments'] : [];
    	if(!empty($tournaments)){
    		foreach ($tournaments as $keyt => $tournament) {
    			$winners = isset($tournament['winners']) ? $tournament['winners'] : [];
    			if(!empty($winners)){
    				foreach ($winners as $keyw => $winner) {
    					if(isset($winner['playerID'])){
    						$player_id = $this->getPlayerIdByGameUsername($winner['playerID']);
    						$player_username = $this->getPlayerUsernameByGameUsername($winner['playerID']);
    						if($player_id && $player_username){
    							$tournaments_winners_records[] = array(
		    						"game_platform_id" => $this->getPlatformCode(),
		    						#tournament data
		    						"tournament_id" => $tournament['tournamentID'],
		    						"tournament_name" => $tournament['name'],
		    						"start_at" => $tournament['dateOpened'],
		    						"end_at" => $tournament['dateClosed'],
		    						#winners data
		    						"player_id" => $player_id,
		    						"player_username" => $player_username,
		    						"position" => $winner['position'],
		    						"score" => $winner['score'],
		    						"prize_amount" => $winner['prizeAmount'],
		    						"currency" => $winner['prizeCurrency'],
		    						#sbe
		    						"response_result_id" => $responseResultId,
		    						"external_unique_id" => $tournament['tournamentID'] . "-" . $winner['playerID']
		    					);
    						}
    					}
    				}
    			}
    		}
    	}
    	return $tournaments_winners_records;
	}

	private function updateOrInsertTournamentData($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('game_tournaments_winners', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('game_tournaments_winners', $record); 
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function queryBetDetailLink($playerUsername, $external_uniq_id = null, $extra=[])
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		//get game code and round using external unique id
		$this->CI->load->model(array('game_logs'));
		$gl = $this->CI->game_logs->queryGameLogsData($this->getPlatformCode(), $external_uniq_id);


        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        );

		$gameId = isset($gl['external_game_id'])?$gl['external_game_id']:null;
		$roundId = isset($gl['round_id'])?$gl['round_id']:null;

		$this->bet_details_test_game_id = $this->getSystemInfo('bet_details_test_game_id');
		$this->bet_details_test_round_id = $this->getSystemInfo('bet_details_test_round_id');

		if(!empty($this->bet_details_test_game_id)){
			$gameId =$this->bet_details_test_game_id;
		}

		if(!empty($this->bet_details_test_round_id)){
			$roundId =$this->bet_details_test_round_id;
		}

        $params = array(
            'gameId' => $gameId,
            'playerId' => $gameUsername,
            'roundId' => $roundId,
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

    public function queryGameListFromGameProvider($extra = NULL) {

        $this->http_method = self::HTTP_METHOD_POST;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryGameListFromGameProvider',

		);

		$params = array(
			'secureLogin' => $this->secureLogin,
		);

		$params['hash'] = MD5(http_build_query($params).$this->secretKey);

		return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		//$resultJson = $this->getResultJsonFromParams($params);
		$success = isset($resultArr['gameList']) ? true:false;
		$result = [];

        $this->CI->utils->debug_log('---------- PP processResultForQueryGameListFromGameProvider response ----------', $resultArr);

        if ($success) {
            if (!empty($resultArr['gameList'])) {
                if (!empty($this->filter_game_list_by_game_types)) {
                    $filteredGameList = [];

                    foreach ($resultArr['gameList'] as $game) {
                        if (in_array($game['gameTypeID'], $this->filter_game_list_by_game_types)) {
                            array_push($filteredGameList, $game);
                        }
                    }

                    if (!empty($filteredGameList)) {
                        $resultArr['gameList'] = $filteredGameList;
                    }
                }
            }

            $result['games'] = $resultArr;
        }
        return array($success, $result);
    }

    public function cancelGameRound($params) {
        $this->http_method = self::HTTP_METHOD_POST;

        $result = [
            'success' => false,
            'message' => '',
        ];

        $rules = [
            'game_username' => ['required'],
            'round_id' => ['required'],
            'game_code' => ['required'],
        ];

        $validation = $this->utils->validateRequestParams($params, $rules);

        if (!$validation->is_valid) {
            $result['message'] = $validation->message;
            return $result;
        }

        $game_username = $params['game_username'];
        $round_id = $params['round_id'];
        $game_code = $params['game_code'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelGameRound',
            'game_username' => $game_username,
        ];

        $params = [
            'externalPlayerId' => $game_username,
            'gameId' => $game_code,
            'roundId' => $round_id,
            'secureLogin' => $this->secureLogin,
        ];

        $params['hash'] = MD5(http_build_query($params) . $this->secretKey);

        return $this->callApi(self::API_cancelGameRound, $params, $context);
    }

    public function processResultForCancelGameRound($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = [
            'result' => !empty($resultArr) ? $resultArr : [],
        ];

        if (isset($resultArr['error']) && $resultArr['error'] == 17) {
            $success = false;
        }

        return array($success, $result);
    }
}

/*end of file*/