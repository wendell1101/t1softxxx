<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

/**
 * document: Inplay Matrix Sportsbook Integration Specification v2.6.10.pdf
 *
 * API: getMemberBetDetailsByBetDatetimeXML
 *
 * BTBuyBack: means buy back , invalid betting
 *
 * BTStatus: Pending/Accepted/Rejected/
 *
 * Rules: if exists Buy Back , should check BTStatus
 * if not , just check "settled" flag (another field)
 *
 */
class Game_api_ipm extends Abstract_game_api {

	private $api_url;
	private $game_url;
	private $company_id;
	private $secret;
	private $currency;
	private $date_modify;
	private $callbacks = array('validate_token');
	private $set_tie_to_only_real_bet=true;

	public function __construct() {
		parent::__construct();
		// $this->api_url = APPPATH . "libraries/game_platform/wsdl/59/externalapi.xml"; sample directory //download the xml file to use for local testing
		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->secret = $this->getSystemInfo('secret');
		$this->company_id = $this->getSystemInfo('account');
		$this->currency = $this->getSystemInfo('currency');
		$this->date_modify = $this->getSystemInfo('date_modify');
		$this->verbose_debug = $this->getSystemInfo('verbose_debug');
		//minus, keep, cancel
		$this->process_bettrade = $this->getSystemInfo('process_bettrade');
		// $this->minus_bettrade = $this->getSystemInfo('minus_bettrade');
		$this->set_tie_to_only_real_bet= $this->getSystemInfo('set_tie_to_only_real_bet', true);
		$this->check_settled_flag=$this->getSystemInfo('check_settled_flag', false);
        $this->use_local_wsdl = $this->getSystemInfo('use_local_wsdl',false);
	}

	const SUCCESS = 100;
	const INVALID = 101;
	const NO_RECORDS = 104;
    const ERROR = 301;
	const DEFAULT_GAME_TYPE = "sports";

	const STATUS = array(
		self::SUCCESS => 'Success',
		self::INVALID => 'Invalid Member Code',
		self::ERROR => 'Internal System Error',
		self::NO_RECORDS=> 'No Records',
	);

	const URI_MAP = array(
		self::API_createPlayer => 'loginXML',
		self::API_isPlayerExist => 'getMemberBalanceXML',
		self::API_login => 'loginXML',
		self::API_logout => 'logoutXML',
		self::API_depositToGame => 'depositXML',
		self::API_withdrawFromGame => 'withdrawXML',
		self::API_checkFundTransfer => 'getTransferStatusXML',
		self::API_queryPlayerBalance => 'getMemberBalanceXML',
		self::API_batchQueryPlayerBalance => 'getAllMemberBalanceXML',
		// self::API_queryGameRecords => 'getMemberBetDetailsXML',
		self::API_queryGameRecords => 'getMemberBetDetailsByBetDatetimeXML',
	);

	# Don't ignore on refresh
	const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

	# CALLBACK METHODS #############################################################################################################################
	public function callback($method, $params) {
		if (in_array($method, $this->callbacks)) {
			return $this->$method($params);
		} else {
			return show_error('Bad Parameters [method]', 400);
		}
	}

	public function validate_token($params) {

		$token = isset($params['token']) ? $params['token'] : null;
		$statusCode = self::INVALID;
		$memberCode = null;
		$currency = null;

		if ( ! empty($token)) {

			$playerId = $this->getPlayerIdByToken($token);
			if ( ! empty($playerId)) {
				// $this->CI->load->model(['game_provider_auth']);
				// $gameUsername=$this->CI->game_provider_auth->getGameUsernameByPlayerId($playerId, $this->getPlatformCode());
				$gameUsername = $this->getGameUsernameByPlayerId($playerId);

				$this->CI->utils->debug_log('-------------------------IPM callback PLAYER ID : player name: '.$gameUsername,$playerId);

				if ( ! empty($gameUsername)) {
					$memberCode = $gameUsername;
					$currency = $this->currency;
					$statusCode = self::SUCCESS;
				}
			}

		}

		$ipAddress 	= $this->CI->input->ip_address();
		$statusDesc = self::STATUS[$statusCode];

		$response = array(
			'memberCode' => $memberCode,
			'currency' => $currency,
			'ipAddress' => $ipAddress,
			'statusCode' => $statusCode,
			'statusDesc' => $statusDesc,
		);

		$this->CI->utils->debug_log('IPM callback response: ',$response);

		return $response;
	}
	# CALLBACK METHODS #############################################################################################################################



	# API HELPER METHODS ########################################################################################################################################
	public function encrypt($data) {
	    $key = md5(utf8_encode($this->secret), true);
	    $key .= substr($key, 0, 8);
	    $blockSize = @mcrypt_get_block_size('tripledes', 'ecb');
	    $len = strlen($data);
	    $pad = $blockSize - ($len % $blockSize);
	    $data .= str_repeat(chr($pad), $pad);
	    $encData = @mcrypt_encrypt('tripledes', $key, $data, 'ecb');
	    return base64_encode($encData);
	}

	public function decrypt($data) {
	    $key = md5(utf8_encode($this->secret), true);
	    $key .= substr($key, 0, 8);
	    $data = base64_decode($data);
	    $data = @mcrypt_decrypt('tripledes', $key, $data, 'ecb');
	    $block = @mcrypt_get_block_size('tripledes', 'ecb');
	    $len = strlen($data);
	    $pad = ord($data[$len-1]);
	    return substr($data, 0, strlen($data) - $pad);
	}
	# API HELPER METHODS ########################################################################################################################################



	# ABSTRACT HELPER ########################################################################################################################################
	function getPlatformCode() {
		return SPORTSBOOK_API;
	}

	function getCallType($apiName, $params) {
		return self::CALL_TYPE_SOAP;
	}


	/**
	 * overview : make soap options
	 *
	 * @param $options
	 * @return mixed
	 */
	protected function makeSoapOptions($options) {
		//overwrite in sub-class
		if($this->use_local_wsdl){
			$options['ignore_ssl_verify'] = $this->getSystemInfo('ignore_ssl_verify', true);
		}
		return $options;
	}

	/**
	 * overview : generate url
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {
		if($this->use_local_wsdl){
			return realpath(dirname(__FILE__)).'/wsdl/58/'.$this->getSystemInfo('wsdl_filename', 'ipm_prod.xml');
		}else{
			return $this->api_url;
		}
	}

	function generateSoapMethod($apiName, $params) {
		return array(self::URI_MAP[$apiName], $params);
	}

	function processResultBoolean($responseResultId, $resultObj, $playerName = null, $check_exists=false, &$exists=false) {

		$success = ! empty($resultObj) && $resultObj['statusCode'] == self::SUCCESS;

		if( isset($resultObj['statusCode']) && $resultObj['statusCode']==self::NO_RECORDS){
			//just no records
			$success=true;
		}

		if($check_exists){

			if( isset($resultObj['statusCode']) && $resultObj['statusCode']==self::INVALID){
				$exists=false;
				$success=true;
			}elseif($success){
				$exists=true;
				$success=true;
			}
		}

		if ( ! $success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('IPM got error', $responseResultId, 'playerName', $playerName, 'result', $resultObj);
		}

		return $success;
	}

	public function getGameTimeToServerTime() {
		return '+12 hours';
	}

	public function getServerTimeToGameTime() {
		return '-12 hours';
	}

	function gameAmountToDB($amount) {
		return round(floatval($amount), 2);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$resultCreatePlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		if($resultCreatePlayer){
			$game_username = $this->getGameUsernameByPlayerUsername($playerName);
			$token = $this->getPlayerTokenByUsername($playerName);

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForCreatePlayer',
				'playerName' => $playerName,
			);

			$timeStamp = $this->encrypt(date('r'));

			$params = array(
				'token' => $token,
				'timeStamp' => $timeStamp,
			);

			return $this->callApi(self::API_createPlayer, $params, $context);
		}
	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

		return array($success, $resultObj);
	}

	public function isPlayerExist($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'memberCode' => $game_username,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$exists=false;
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName, true, $exists);

		return array( $success, array('exists' => $exists));
	}

	public function login($playerName, $password = NULL) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);
		$token = $this->getPlayerTokenByUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'token' => $token,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'token' => $token,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$token = $this->getVariableFromContext($params, 'token');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

		$resultObj['token'] = $token;
		return array($success, $resultObj);
	}

	public function logout($playerName, $password = NULL) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'membercode' => $game_username,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

		return array($success, $resultObj);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);
		$transferId = 'DEP' . date('YmdHis') . random_string('numeric', 8);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'game_username' => $game_username,
			'playerName' => $playerName,
			'transferId' => $transferId,
			'amount' => $amount,
			'external_transaction_id' => $transferId,
		);

		$token = $this->getPlayerTokenByUsername($playerName);
		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'memberCode' => $game_username,
			'amount' => $amount,
			'currencyCode' => $this->currency,
			'transferId' => $transferId,
			'token' => $token,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$transferId = $this->getVariableFromContext($params, 'transferId');
		$amount = $this->getVariableFromContext($params, 'amount');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id
        );

		if ($success) {
            $result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
			$error_code = @$resultObj['statusCode'];
			$result['reason_id'] = $this->getReasons($error_code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	private function getReasons($error_code){
        switch ($error_code) {
            case 101:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 102:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 103:
                return self::REASON_GAME_ACCOUNT_LOCKED;
                break;
            case 104:
                return self::REASON_CURRENCY_ERROR;
                break;
            case 105:
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case 106:
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case 201:
            case 202:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 301:
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);
		$transferId = 'WIT' . date('YmdHis') . random_string('numeric', 8);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'game_username' => $game_username,
			'playerName' => $playerName,
			'transferId' => $transferId,
			'amount' => $amount,
			'external_transaction_id' => $transferId,
		);

		$token = $this->getPlayerTokenByUsername($playerName);
		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'memberCode' => $game_username,
			'amount' => $amount,
			'currencyCode' => $this->currency,
			'transferId' => $transferId,
			'token' => $token,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$transferId = $this->getVariableFromContext($params, 'transferId');
		$amount = $this->getVariableFromContext($params, 'amount');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id
        );

		if ($success) {
            $result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
			$error_code = @$resultObj['statusCode'];
			$result['reason_id'] = $this->getReasons($error_code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function checkFundTransfer($playerName, $externaltransactionid) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckFundTransfer',
			'playerName' => $playerName,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'transferId' => $externaltransactionid,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_checkFundTransfer, $params, $context);
	}

	public function processResultForCheckFundTransfer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

		// $resultObj['transferStatus']

		return array($success, $resultObj);
	}

	public function queryPlayerBalance($playerName) {

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'memberCode' => $game_username,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);

		$result['balance'] = isset($resultObj['amount']) ? $resultObj['amount'] : 0;

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra) {

		$game_url = $this->game_url;
		if (isset($extra['extra']['mobile']) && $extra['extra']['mobile']) {
			$game_url .= '/iphone.aspx';
		}

		$data['success'] = false;

		$loginResult = $this->login($playerName);

		if ($loginResult['success'] && isset($loginResult['token'])) {

			$token = $loginResult['token'];
			$timeStamp = $this->encrypt(date('r'));

			$data['success'] = true;
			$game_url .= '?' . http_build_query(array(
				'timeStamp' => $timeStamp,
				'token' => $token,
				'LanguageCode' => isset($extra['language']) ? $extra['language'] : 'EN',
			));

		}

		$data['url'] = $game_url;

		return $data;
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBatchQueryPlayerBalance',
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_batchQueryPlayerBalance, $params, $context);
	}

	public function processResultForBatchQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$success = $this->processResultBoolean($responseResultId, $resultObj);

		$result = isset($resultObj['dataSet']) ? json_decode(json_encode(simplexml_load_string($resultObj['dataSet'])),true) : null;

		if (isset($result['Table']) && ! empty($result['Table'])) {

			$players = $result['Table'];

			$prefix = $this->company_id . '_';
			$prefix_length = strlen($prefix);

			$self = $this;
			foreach ($players as $player) {
				$playerName = $player['memberCode'];
				if (substr($playerName, 0, $prefix_length) == $prefix) {
					$playerName = substr($playerName, $prefix_length);
					$playerId = $this->getPlayerIdFromUsername($playerName);
					if ( ! empty($playerId)) {
						$balance = $player['balanceCredit'];
						// $this->CI->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $balance . $playerId, function () use ($self, $playerId, $balance) {
//							$self->updatePlayerSubwalletBalance($playerId, floatval($balance));
						// });
					}
				}
			}

		}

		return array('success' => true);
	}

	function isInvalidRow($row) {
		// return $row['settled'] != 1; # TODO: CHECKE
		return false;
	}

	public function syncOriginalGameLogs($token) {

		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());
		//always from -1 day
		$startDateStr = $this->serverTimeToGameTime($startDate);
		$endDateStr = $this->serverTimeToGameTime($endDate);

		// use only for this resync_ipm_unsettle_game_records function
		// from unsettled game logs, will recheck if game was already settled, then update, if not do nothing
		$unique_ids = clone parent::getValueFromSyncInfo($token, 'external_ids');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDateStr' => $startDateStr,
			'endDateStr' => $endDateStr,
			'unique_ids' => $unique_ids,
		);

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'timeStamp' => $timeStamp,
			'startDate' => $startDateStr,
			'endDate' 	=> $endDateStr,
			// 'isSettled' => '1',
			'lastUpdated' => '',
		);

		$this->CI->utils->debug_log('try load game logs syncOriginalGameLogs', $startDateStr, $endDateStr, $params);

		return $this->callApi(self::API_queryGameRecords, $params, $context);
	}

	const GAME_HISTORY_FIELDS=array(
	    'betId',
	    'betTime',
	    'memberCode',
	    'matchDateTime',
	    'sportsName',
	    'matchID',
	    'leagueName',
	    'homeTeam',
	    'awayTeam',
	    'favouriteTeamFlag',
	    'betType',
	    'selection',
	    'handicap',
	    'oddsType',
	    'odds',
	    'currency',
	    'betAmt',
	    'result',
	    'HTHomeScore',
	    'HTAwayScore',
	    'FTHomeScore',
	    'FTAwayScore',
	    'BetHomeScore',
	    'BetAwayScore',
	    'settled',
	    'betCancelled',
	    'bettingMethod',
	    'BTStatus',
	    'BTComission',
	    'uniqueid',
	    'external_uniqueid',
	    'gameshortcode',
	    'response_result_id',
	    'BTBuyBack',
	    'ParlayBetDetails',
	);

	public function filterFields($row){
		$rlt=[];
		if(!empty($row) && is_array($row)){
			foreach (self::GAME_HISTORY_FIELDS as $fldName) {
				if(array_key_exists($fldName, $row)){
					$rlt[$fldName]=$row[$fldName];
				}
			}
		}

		return $rlt;
	}

	public function processResultForSyncOriginalGameLogs($params) {

		$this->CI->load->model('ipm_game_logs');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$success = $this->processResultBoolean($responseResultId, $resultObj);

		if($this->verbose_debug){
			$this->CI->utils->debug_log('resultObj', $resultObj);
		}

		$unsettled_unique_ids = $this->getVariableFromContext($params, 'unique_ids');
		$bet_dates_with_settled = [];
		$game_result = [];
		if ($success) {

			$result = isset($resultObj['dataSet']) ? json_decode(json_encode(simplexml_load_string($resultObj['dataSet'])),true) : null;
			$gameRecords = isset($result['MemberBetDetails']) ? $result['MemberBetDetails'] : array();

			if($this->verbose_debug){
				$this->CI->utils->debug_log('get gameRecords', count($gameRecords));
			}

			if (!empty($gameRecords) && is_array($gameRecords)) {

				$is_single=array_key_exists('betId', $gameRecords);
				$this->CI->utils->debug_log('is_single', $is_single);

				if($is_single){
					$gameRecords=[$gameRecords];
				}

				$prefix = $this->company_id . '_';
				$prefix_length = strlen($prefix);
				$controller=$this;

				array_walk($gameRecords, function(&$gameRecord) use ($controller, $responseResultId, $prefix, $prefix_length,
						$result, $unsettled_unique_ids, &$bet_dates_with_settled) {

					if(!empty($gameRecord)){

						foreach ($gameRecord as &$value) {
							if (is_array($value)) {
								$value = json_encode($value);
							}
						}
					}

					$gameRecord=$controller->filterFields($gameRecord);

					// use for new sync
					if(!empty($unsettled_unique_ids)) {
						//$gameRecord['betId'] = '1901011236271723';
						if(in_array($gameRecord['betId'], (array) $unsettled_unique_ids)) { // check first if game_logs_unsettle record is exist in api
							// filter check if game was already done
							$status = $this->getGameStatus($gameRecord);
							if ($status == Game_logs::STATUS_SETTLED) { // make sure game was already done
								$date = new \DateTime($gameRecord['betTime']);
								$bet_date = $date->format('Y-m-d');
								if(!in_array($bet_date, $bet_dates_with_settled)) {
									array_push($bet_dates_with_settled, $bet_date);
								}
							}
						} else {
							$this->CI->utils->debug_log('IPM debug ignore checking', $gameRecord['betId']);
						}
					}

					if($controller->verbose_debug){
						$controller->CI->utils->debug_log('get gameRecord', $gameRecord);
					}

					$playerName = $gameRecord['memberCode'];
					if (substr($playerName, 0, $prefix_length) == $prefix) {
						$playerName = substr($playerName, $prefix_length);
						$gameRecord['memberCode'] = $playerName;
					}

					$gameshortcode = $gameRecord['sportsName'].$gameRecord['betType'];
					$uniqueId = $gameRecord['betId'];
					$external_uniqueid = $uniqueId;

					$gameRecord['betAmt'] = (float) str_replace(',', '', $gameRecord['betAmt']);
					$gameRecord['result'] = (float) str_replace(',', '', $gameRecord['result']);
					$gameRecord['betTime'] = $controller->gameTimeToServerTime($gameRecord['betTime']);
					$gameRecord['matchDateTime'] = $controller->gameTimeToServerTime($gameRecord['matchDateTime']);

					$gameRecord['response_result_id'] = $responseResultId;
					$gameRecord['uniqueid'] = $uniqueId;
					$gameRecord['external_uniqueid'] = $external_uniqueid;
					$gameRecord['gameshortcode'] = $gameshortcode;

					if ( ! $controller->isInvalidRow($gameRecord)) {
						$controller->CI->ipm_game_logs->syncGameLogs($gameRecord);
					}
				});
			}
		}else{
			$this->CI->utils->error_log('processResultForSyncOriginalGameLogs result failed', @$resultObj['statusCode']);
		}

		if(!empty($unsettled_unique_ids)) {
			$game_result['bet_with_settled_dates'] = $bet_dates_with_settled;
		}

		return array($success, $game_result);

	}

	public function isBettradeMode($row){
		$is=false;
		if(!empty($row)){
			if(isset($row->BTBuyBack)){
				$is=true;
			}
		}

		return $is;
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ipm_game_logs', 'game_description_model'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');

		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$cnt 	= 0;
		$rlt 	= array('success' => true);
		$result = $this->CI->ipm_game_logs->getGameLogStatistics($dateTimeFrom, $dateTimeTo);
		$unknownGame = $this->getUnknownGame();

		if(!empty($result)){

			foreach ($result as $ipm_data) {
				$player_id = $this->getPlayerIdInGameProviderAuth($ipm_data->username);

				if ($player_id) {

					$cnt++;

					$bet_amount = $this->gameAmountToDB($ipm_data->bet);
					$result_amount = $this->gameAmountToDB($ipm_data->result);
					$has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
					// $has_both_side=false;

					# TODO: checkUnknownGameType
					$game_type_id = $ipm_data->game_type_id;
					$game_description_id = $ipm_data->game_description_id;

					if (empty($game_description_id)) {
                        list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($ipm_data,$unknownGame);
					}

					$odd='';
					if(isset($ipm_data->oddsType) && isset($ipm_data->odds)){
						$odd= $ipm_data->oddsType.' - '.$ipm_data->odds;
					}

					$status=Game_logs::STATUS_SETTLED;
					$isBettradeMode=$this->isBettradeMode($ipm_data);
					if($isBettradeMode){
						// $status=Game_logs::STATUS_SETTLED;
						if($ipm_data->BTStatus=='Pending'){
							// $status=Game_logs::STATUS_SETTLED;
							$status=Game_logs::STATUS_PENDING;
						}elseif($ipm_data->BTStatus=='Accepted'){
							$status=Game_logs::STATUS_ACCEPTED;
						}elseif($ipm_data->BTStatus=='Rejected'){
							$status=Game_logs::STATUS_REJECTED;
						}
						//minus, keep, cancel
						if($ipm_data->BTBuyBack>0){

							if($this->process_bettrade=='minus'){
								$bet_amount-=$ipm_data->BTBuyBack;
							}elseif($this->process_bettrade=='cancel'){
								$status=Game_logs::STATUS_CANCELLED;
								// $bet_amount=0;
							}
							//if keep
						}

						$real_bet=$bet_amount;
						//back to 0
						$result_amount=0;

					}else{

						if($ipm_data->settled=='0'){
							$status=Game_logs::STATUS_PENDING;
						}
						if($ipm_data->betCancelled == 1){
							$status=Game_logs::STATUS_CANCELLED;
						}

						$real_bet=$bet_amount;

						if($this->set_tie_to_only_real_bet){
							if($ipm_data->settled=='1' && $this->utils->compareResultFloat($result_amount, '=' , 0)){
								//tie, clear available
								log_message('debug', 'clear tie bet', ['ipm_data'=>$ipm_data] );
								$bet_amount=0;
							}
						}
					}

					if($this->verbose_debug){
						$this->CI->utils->debug_log('betId', $ipm_data->betId,'isBettradeMode', $isBettradeMode, 'status', $status,
							'BTStatus', $ipm_data->BTStatus, 'BTBuyBack', $ipm_data->BTBuyBack);
					}

					$this->syncGameLogs(
						$game_type_id,
						$game_description_id,
						$ipm_data->game,
						$game_type_id,
						$ipm_data->game,
						$player_id,
						$ipm_data->username,
						$bet_amount,
						$result_amount,
						null,
						null,
						null,
						$has_both_side,
						$ipm_data->external_uniqueid,
						$ipm_data->start_at,
						$ipm_data->end_at,
						$ipm_data->response_result_id,
						Game_logs::FLAG_GAME,
						['room' => $odd, 'table'=>$ipm_data->external_uniqueid, 'status'=>$status,
						'trans_amount'=>$real_bet ]
					);

				}
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return array('success' => true);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}
		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}

		$externalGameId = $row->game;
		$extra = array('game_code' => $externalGameId);

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$row->game, self::DEFAULT_GAME_TYPE, $externalGameId, $extra,
			$unknownGame);
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
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

		$timeStamp = $this->encrypt(date('r'));

		$params = array(
			'transferId' => $transactionId,
			'timeStamp' => $timeStamp,
		);

		return $this->callApi(self::API_checkFundTransfer, $params, $context);
	}

	public function processResultForQueryTransaction($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultObj = $this->getResultObjFromParams($params);
		$resultObj = json_decode(json_encode($resultObj), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$success = $this->processResultBoolean($responseResultId, $resultObj, $playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success) {
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_msg = $this->getResultTextFromParams($params);
			$result['reason_id'] = $this->getReasons($error_msg);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function syncLostAndFound($token) {
		//sync csv report to
		//F is CNY
		//from ipm_raw_game_logs to ipm_game_logs
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());
		$startDateStr = $this->serverTimeToGameTime($startDate);
		$endDateStr = $this->serverTimeToGameTime($endDate);

		$this->CI->load->model(['ipm_game_logs']);
		$this->CI->ipm_game_logs->importRaw($startDateStr, $endDateStr);

		$result=['success'=>true, 'message'=>'import successfully'];
		return $result;
	}

	/**
	 * ADD FUNCTION TO CHECK IF BET DATE IS UPDATED(already settled)
	 */

	public function searchAndResyncSettledGamesByBetTime($set_date_limit) {
		$unsettledLogs = $this->reProcessGameLogsInUnsettledByBetTime($set_date_limit);

		$bet_dates = [];
		$external_ids = [];
		if(!empty($unsettledLogs)) {
			foreach($unsettledLogs as $key => $logs) {
				$date = new \DateTime($logs['bet_at']);

				$bet_date = $date->format('Y-m-d');
				if(!in_array($bet_date, $bet_dates)) {
					array_push($bet_dates, $bet_date);
				}

				// get all unique ids in unsettled game logs
				// recheck in game logs original api if this records are already settled
				array_push($external_ids, $logs['external_uniqueid']);
			}
		}

		$dates_to_rebuild = []; // only rebuild those already settled data

		if(!empty($bet_dates)) {
			$adjust_datetime = str_replace("-","+",$this->getDatetimeAdjust());
			foreach($bet_dates as $date) {
				$this->CI->utils->debug_log('IPM sync --> reprocess unsettled bets', $date);

				list($startDate, $endDate) = $this->getDateTimeRange($date);
				$startDate->modify($adjust_datetime);

				$token = random_string('unique');
				$this->saveSyncInfoByToken($token, $startDate, $endDate, null, null, null,['external_ids' => (object) $external_ids]);
				$rlt=$this->syncOriginalGameLogs($token);

				if($rlt['success'] && count($rlt['bet_with_settled_dates']) > 0) {
					if(!in_array($date, $dates_to_rebuild)) {
						array_push($dates_to_rebuild, $date);
					}
					$this->syncMergeToGameLogs($token);
				}
			}
		}
		return $dates_to_rebuild;
	}

	public function reProcessGameLogsInUnsettledByBetTime($set_date_limit) {
		$this->CI->load->model(['game_logs']);
		return $this->CI->game_logs->getUnsettledGameLogsByPlatformId($set_date_limit, $this->getPlatformCode());
	}

	public function getDateTimeRange($date) {
		$from = new \DateTime($date);
		$from->setTime(0, 0, 0);
		$to = new \DateTime($date);
		$to->setTime(23, 59, 59);
		return array($from, $to);
	}

	public function getGameStatus($data) {
		$status=Game_logs::STATUS_SETTLED;
		$data = (object) $data;
		$isBettradeMode=$this->isBettradeMode($data);
		if ($isBettradeMode) {
			if ($data->BTStatus=='Pending') {
				$status=Game_logs::STATUS_PENDING;
			} elseif ($data->BTStatus=='Accepted') {
				$status=Game_logs::STATUS_ACCEPTED;
			} elseif ($data->BTStatus=='Rejected') {
				$status=Game_logs::STATUS_REJECTED;
			}
		} else {
			if ($data->settled=='0') {
				$status=Game_logs::STATUS_PENDING;
			}
			if ($data->betCancelled == 1) {
				$status=Game_logs::STATUS_CANCELLED;
			}
		}
		return $status;
	}
}
/*end of file*/