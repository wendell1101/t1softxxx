<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: 24TECH
	*
	* @category Game_platform
	* @version 5.65
	* @copyright 2013-2022 tot
	* @integrator @andy.php.ph
**/

class Game_api_common_24tech_game extends Abstract_game_api {
	# Fields in games24tech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'ballid',
        'balltime',
        'curpl',
        'isbk',
        'iscancel',
        'currency',
        'isjs',
        'win',
        'lose',
        'result_amount',
        'currency',
        'moneyrate',
        'result',
        'sportid',
        'truewin',
        'tzip',
        'tzmoney',
        'tztype',
        'bet_type',
        'updatetime',
        'username',
        'content',
        'vendorid',
        'validamount',
        'abc',
        'oddstype',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'curpl',
        'win',
        'lose',
        'result_amount',
        'moneyrate',
        'tzmoney',
        'validamount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when oneworks24tech_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	const ORIGINAL_GAMELOGS_TABLE = "the24tech_game_logs";
	const API_confirmTransferCredit = "ConfirmTransferCredit";

	const API_METHOD_MAP = [
		self::API_createPlayer => 'caca',
		self::API_queryPlayerBalance => 'gb',
		self::API_depositToGame => 'ptc',
		self::API_withdrawFromGame => 'ptc',
		self::API_confirmTransferCredit => 'ctc',
		self::API_queryForwardGame => 'tg',
		self::API_syncGameRecords => 'gsbrbvibc',
	];

	const PLATFORMNAME_TYPES = [
		'bbin' => 'bbin',
		'ag' => 'ag',
		'ibc' => 'ibc',
		'btc' => 'btc',
		'bti' => 'bti',
		'png' => 'png',
	]; 

	const THE24TECH_GAME_TYPES = [
		'video' => 1,
		'ibcsports_or_bbin_old_sports' => 2,
		'lottery' => 3,
		'slots' => 4,
		'fish' => 5,
		'bbin_new_sports' => 19,
		'ibc_mobile' => 21,
	]; 

	public function __construct() {
		parent::__construct();
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->api_url = $this->getSystemInfo('url');
		$this->agent = $this->getSystemInfo('agent');
		$this->api_agent_key = $this->getSystemInfo('api_agent_key');
		$this->platformname = $this->getSystemInfo('platformname',self::PLATFORMNAME_TYPES['ibc']);
		$this->gametype = $this->getSystemInfo('game_type',self::THE24TECH_GAME_TYPES['ibcsports_or_bbin_old_sports']);
		$this->get_data_api_url = $this->getSystemInfo('get_data_api_url');
		$this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
        $this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');
	}

	public function getPlatformCode(){
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) 
	{	
		if($apiName == self::API_syncGameRecords){
			$url = $this->get_data_api_url;	
		}else{
			$url = $this->api_url;
		}
		return $url.'?'.http_build_query($params);
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode) 
	{
		$success = false;
		if(@$statusCode == 200 || @$statusCode == 201){
			$success = true;
			$this->CI->utils->debug_log('ONEWORKS24TECH API CALL SUCCESS:', $responseResultId,'result', $resultArr);
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ONEWORKS24TECH got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) 
	{
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$apiType = self::API_createPlayer;

		$context = [
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
			'apiType' => $apiType
		];

		$data = [
				 'agent' => $this->agent,
				 'username' => $gameUsername,
				 'password' => $password,
				 'moneysort' => $this->currency_type,
				 'method' => self::API_METHOD_MAP[$apiType],
				];
		
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);

		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams($apiType,$data,$params);
		return $this->callApi($apiType,$params,$context);
	}

	public function processResultForCreatePlayer($params)
	{	
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->convertXmlToArray($resultXml);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$apiType = $this->getVariableFromContext($params, 'apiType');

		$resultDetails = [
						  'success' => $resultArr ? true : false,
						  'api_type' => $apiType,
						  'game_username' => $gameUsername
						 ];
		$success = $this->processResultBoolean($responseResultId,$resultDetails,$statusCode);

		$result = ['player' => $gameUsername];
		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}
		return array($success, $result);
	}

	protected function convertXmlToArray($resultXml)
	{
		$result = json_decode(json_encode($resultXml), TRUE);
		return $result;
	}

	public function queryPlayerBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		$apiType = self::API_queryPlayerBalance;

		$context = [
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername,
			'apiType' => $apiType
		];

		$data = [
				 'agent' => $this->agent,
				 'username' => $gameUsername,
				 'password' => $password,
				 'method' => self::API_METHOD_MAP[$apiType],
				 'platformname' => $this->platformname,
				];
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams($apiType,$data,$params);
		return $this->callApi($apiType,$params,$context);
	}

	public function processResultForQueryPlayerBalance($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->convertXmlToArray($resultXml);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$apiType = $this->getVariableFromContext($params, 'apiType');

		$resultDetails = [
						  'success' => $resultArr ? true : false,
						  'api_type' => $apiType,
						  'game_username' => $gameUsername,
						  'raw_result' => $resultArr
						 ];
		$success = $this->processResultBoolean($responseResultId,$resultDetails,$statusCode);
		$result = [];
		if($success){
			if(isset($resultArr[0]) && !empty($resultArr[0])){
				$result['balance'] = $resultArr[0];	
			}else{
				$result['balance'] = 0;
			}
		}
		return array($success, $result);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) 
	{
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		$external_transaction_id = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
		$billno = $external_transaction_id;
		$transferType = "in";

		$apiType = self::API_depositToGame;
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForFundTransfer',
            'gameUsername' => $gameUsername,
            'apiType' => $apiType,
            'password' => $password,
            'billno' => $billno,
            'transferType' => $transferType
        ];

		$data = [
				 'agent' => $this->agent,
				 'username' => $gameUsername,
				 'password' => $password,
				 'billno' => $billno,
				 'type' => $transferType,
				 'credit' => $amount,
				 'platformname' => $this->platformname,
				 'method' => self::API_METHOD_MAP[$apiType],
				];
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams($apiType,$data,$params);
		return $this->callApi($apiType,$params,$context);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		$external_transaction_id = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
		$billno = $external_transaction_id;
		$transferType = "out";

		$apiType = self::API_withdrawFromGame;
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForFundTransfer',
            'gameUsername' => $gameUsername,
            'apiType' => $apiType,
            'password' => $password,
            'billno' => $billno,
            'transferType' => $transferType
        ];

		$data = [
				 'agent' => $this->agent,
				 'username' => $gameUsername,
				 'password' => $password,
				 'billno' => $billno,
				 'type' => $transferType,
				 'credit' => $amount,
				 'platformname' => $this->platformname,
				 'method' => self::API_METHOD_MAP[$apiType],
				];
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams($apiType,$data,$params);
		return $this->callApi($apiType,$params,$context);
	}

	public function processResultForFundTransfer($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$password = $this->getVariableFromContext($params, 'password');
		$billno = $this->getVariableFromContext($params, 'billno');
		$transferType = $this->getVariableFromContext($params, 'transferType');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->convertXmlToArray($resultXml);
		$apiType = $this->getVariableFromContext($params, 'apiType');
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$result = [
					'response_result_id' => $responseResultId,
					'external_transaction_id' => $billno,
					'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
					'reason_id' => self::REASON_UNKNOWN
				  ];
		
		$prepareTransferResultDetails = [
						  'success' => $resultArr ? true : false,
						  'api_type' => $apiType,
						  'game_username' => $gameUsername,
						  'transfer_type' => $transferType
						 ];
		$prepareTransferResultSuccess = $this->processResultBoolean($responseResultId,
																	$prepareTransferResultDetails,
																	$statusCode);

		$fundTransferSuccess = false;
		if($prepareTransferResultSuccess)
		{
			$playerInfo = ['gameUsername'=>$gameUsername,'password'=>$password];
			$confirmTransferResultSuccess = $this->confirmTransferCredit($playerInfo,$billno,$transferType);
			if($confirmTransferResultSuccess)
			{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
				$result['didnot_insert_game_logs'] = true;
				$fundTransferSuccess = true;
				return [$fundTransferSuccess,$result];
			}
		}
        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		$result['reason_id'] = $this->getReasons($statusCode);
        return [$fundTransferSuccess,$result];
	}

	public function confirmTransferCredit($playerInfo,$billNo,$transferType)
	{
		$apiType = self::API_confirmTransferCredit;
		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmTransfer',
            'gameUsername' => $playerInfo['gameUsername'],
            'apiType' => $apiType,
            'transferType' => $transferType,
        ];

		$data = [
				 'agent' => $this->agent,
				 'username' => $playerInfo['gameUsername'],
				 'password' => $playerInfo['password'],
				 'billno' => $billNo,
				 'type' => $transferType,
				 'method' => self::API_METHOD_MAP[$apiType],
				];
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams($apiType,$data,$params);
		return $this->callApi($apiType,$params,$context);
	}

	public function processResultForConfirmTransfer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->convertXmlToArray($resultXml);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$apiType = $this->getVariableFromContext($params, 'apiType');
		$transferType = $this->getVariableFromContext($params, 'transferType');

		$confirmTransferResultDetails = [
						  'success' => $resultArr ? true : false,
						  'api_type' => $apiType,
						  'game_username' => $gameUsername,
						  'transfer_type' => $transferType
						 ];
		return $this->processResultBoolean($responseResultId,$confirmTransferResultDetails,$statusCode);
	}

	public function queryForwardGame($playerName, $extra = null){
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token = false){
		return $this->unimplemented();
	}

    protected function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
    return $dataCount;
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=false;
        // $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

	public function blockPlayer($playerName) 
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) 
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
    }

	public function isPlayerExist($playerName){
		return array(true, ['success' => true, 'exists' => true]);
    }

    private function generateSerialNo()
    {
		$dt = new DateTime($this->utils->getNowForMysql());
		return $dt->format('YmdHis').random_string('numeric', 6);
	}

	protected function processPlayerLanguageForParams($lang)
	{
		switch ($lang) {
			case "Chinese": case "zh": case "zh-cn": return "zh"; break;
			case "English": case "en": case "en-us": return "en"; break;
			case "Japanese": case "jp": case "jp-jp": return "jp"; break;
			case "Korean": case "kr": case "kr-kr": return "kr"; break;
			case "Thai": case "th": case "th-th": return "th"; break;
			case "Vietnamese": case "vn": case "vn-vn": return "vn"; break;
			case "Indonesian": case "id": case "id-id": return "id"; break;
			default:
				return "zh";
				break;
		}
	}

	protected function getGameDescriptionInfo($row, $unknownGame) 
	{
		$game_description_id = null;
		$game_name = str_replace("알수없음",$row['game_code'],
					 str_replace("不明",$row['game_code'],
					 str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case 400:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 401:
				return self::REASON_INVALID_KEY;
				break;
			case 404:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 409:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 500:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	protected function encodeParamsToBase64Utf8($params)
    {
    	$encoded_params = base64_encode(utf8_encode(http_build_query($params,null,"$")));
		$result = md5($encoded_params.$this->api_agent_key);
		return [$encoded_params,$result];
    }

    protected function debugLogParams($apiCall,$rawParams,$encodedParams)
    {
		$this->CI->utils->debug_log('24TECH ('.$this->platformname.') API CALL ('.$apiCall.') ===> RAW PARAMS: ', $rawParams, ' ENCODED PARAMS: ', $encodedParams);
	}

    public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = null, $extra = null) 
	{
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
    	return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
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

	public function updatePlayerInfo($playerName, $infos=null) {
		return $this->returnUnimplemented();
	}
}
/*end of file*/