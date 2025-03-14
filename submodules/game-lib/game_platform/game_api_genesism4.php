<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_genesism4 extends Abstract_game_api
{
	/**
	 * The Request method of Endpoint
	 * 
	 * @var string $requestMethod
	 */
	protected $requestMethod;

	/**
	 * The Limit of how many items in the array to be return in API /m4/spindata/query/partner
	 * 
	 * @var int $pageLimit
	 */
	protected $pageLimit;

	/**
	 * Zero offset index of entire data set array, meaning if we want to fetch record 10, the page startIndex will be 9
	 * 
	 * @var int $pageStartIndex
	 */
	protected $pageStartIndex;

	/**
	 * Original game logs table
	 * 
	 * @var string $originalGameLogsTable
	 */
	protected $originalGameLogsTable;

	const URI_MAP = array(
		self::API_createPlayer		 => '/m4/wallet/transfer',
		self::API_depositToGame 	 => '/m4/wallet/transfer',
		self::API_withdrawFromGame 	 => '/m4/wallet/transfer',
		self::API_queryPlayerBalance 	 => '/m4/wallet/balance/',
		self::API_queryTransaction   => '/m4/wallet_log/query/entry/',
		self::API_syncGameRecords   => '/m4/spindata/query/partner',
	);

	const DEPOSIT = "Deposit";
	const WITHDRAW = "Withdraw";
	const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const HISTORY = 'history';
	const FISHINGID = 'M4-0075;V:1';
	
	/**
	 *  Fields in original game logs  table, we want to detect changes for update in fields
	 * 
	 * @var const MD5_FIELDS_FOR_ORIGINAL
	 */
	const MD5_FIELDS_FOR_ORIGINAL = [
		'partner_data',
		'user_id',
		'game_id',
		'causality',
		'timestamp',
		'currency',
		'total_bet',
		'total_won',
		'balance',
		'merchantcode',
		'device',
		'user_type'
	];

	/**
     * Values of these fields will be rounded when calculating MD5
     * 
     * @param constant MD5_FLOAT_AMOUNT_FIELDS
     */
    const MD5_FLOAT_AMOUNT_FIELDS = [
		'total_bet',
		'total_won',
		'balance'
	];

	/** 
     * Fields in game_logs table, we want to detect changes for merge, and when original game logs.md5_sum table is empty
     * 
     * @param constant MD5_FIELDS_FOR_MERGE
    */
    const MD5_FIELDS_FOR_MERGE = [
		'bet_amount',
		'won_amount',
		'after_balance',
		'user_id',
		'causality',
		'game_date',
		'game_code',
		'partner_data'
	];

	/** 
     * Values of these fields will be rounded when calculating MD5
     * 
     * @param constant MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
    */
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'bet_amount',
		'won_amount',
		'after_balance'
	];

	public function __construct()
	{
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->partnerToken = $this->getSystemInfo('key');
		$this->secret = $this->getSystemInfo('secret');
		$this->slots_game_url = $this->getSystemInfo('slots_game_url');
		$this->fishing_game_url = $this->getSystemInfo('fishing_game_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->history_url = $this->getSystemInfo('history_url');
		$this->return_slot_url = $this->getSystemInfo('return_slot_url');
		$this->promo_secret_key = $this->getSystemInfo('promo_secret_key');
		$this->promo_url = $this->getSystemInfo('promo_url');
		$this->pageLimit = $this->getSystemInfo('pageLimit',5000);
		$this->pageStartIndex = $this->getSystemInfo('pageStartIndex',0);
		$this->requestMethod = self::METHOD_GET;
		$this->originalGameLogsTable = $this->getOriginalTable();
	}

	public function getPlatformCode()
	{
		return GENESISM4_GAME_API;
	}

	public function getOriginalTable(){
        return 'genesism4_game_logs';
    }

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];

		$urlPath  =  $apiUri . '?' . http_build_query($params);

		if($apiName == 'syncGameRecords'){
			$url = $this->history_url . $urlPath;
		}elseif($apiName == 'queryPlayerBalance'){
			$userName = isset($params['userName']) ? $params['userName'] : null;
			$url = $this->api_url . $apiUri . $userName;
		}else{
			$url = $this->api_url . $urlPath;
		}

		return $url;
	}


	public function getHttpHeaders($params)
	{

		return [
			"X-Genesis-PartnerToken" => $this->partnerToken,
			"X-Genesis-Secret" 		 => $this->secret,
			"Content-Type" 			 => "application/json"
		];

	}

	protected function customHttpCall($ch, $params) {
		
		if($this->requestMethod == self::METHOD_POST){
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true ); 
  			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		}
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		return $errCode || intval($statusCode, 10) >= 501;
	}
	

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		if(!empty($resultArr)) {
			$success = array_key_exists('success', $resultArr) ? $resultArr['success'] : true;
		}
		else {
			$success = false;
		}
		if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GENESISM4_GAME_API processResultBoolean error with response result id of: >>>>>>>>', $responseResultId,  'result', $resultArr);
            $success = false;
        }
        return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => 0.1,
            'external_transaction_id'=>$external_transaction_id,
        );
        
        $params = array(
        	"user_id" 					=> $gameUsername,
			"partner_id"				=> $this->partnerToken,
			"credits"					=> $this->dBtoGameAmount(0.1),
			"currency"					=> $this->currency,
			"custom_json"				=> null,
			"action"					=> self::WITHDRAW,
			"external_transaction_id"	=> $external_transaction_id
        );

		$this->utils->debug_log("GENESISM4_GAME_API createPlayer params >>>>>>>>", $params);

		$this->requestMethod = self::METHOD_POST;

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
		if($success){
			#withdraw deposit amount  on create player
			$this->withdrawFromGame($playerName, 0.1, null,false); 
		}
		return array($success, $resultJsonArr);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword)
	{
		return $this->returnUnimplemented();
	}

	public function isPlayerExist($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$result = $this->queryPlayerBalance($playerName);
		if($result['success'] && $result['exists']) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}
		return $result;
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $params = array(
			'userName' => $gameUsername
        );

		$this->utils->debug_log("GENESISM4_GAME_API queryPlayerBalance params >>>>>>>>", $params);

		$this->requestMethod = self::METHOD_GET;
		
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params); 
		$balance = null;
		
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		
        if ($success) {
			$balance = $this->gameAmountToDB($resultArr['internal_balance']);
			
        	if ($playerId = $this->getPlayerIdInGameProviderAuth($gameUsername)){
				$this->CI->utils->debug_log('GENESISM4 GAME API query balance playerId', $playerId, 'gameUsername', $gameUsername, 'balance', $balance);
			} else {
				$this->CI->utils->debug_log('GENESISM4 GAME API cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			}
			$result['exists'] = $success;
			$result['session_token'] = $resultArr['session_token'];

        } else{ 
        	$result['session_token'] = NULL;
			$reason = $resultArr['error']['reason'];
			
        	if (strpos($reason, '(RECIPIENT_FAILURE,404)') !== false){
        		$success = true;
        		$result['exists'] = false;
        	} else {
				$result['exists'] = NULL;
        	}
        }
        $result['balance'] = @floatval($balance);
       	
		return array($success, $result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id'=>$external_transaction_id,
        );
        
        $params = array(
        	"user_id" 					=> $gameUsername,
			"partner_id"				=> $this->partnerToken,
			"credits"					=> $this->dBtoGameAmount($amount),
			"currency"					=> $this->currency,
			"custom_json"				=> null,
			"action"					=> self::WITHDRAW,
			"external_transaction_id"	=> $external_transaction_id,
        );

		$this->utils->debug_log("GENESISM4_GAME_API depositToGame params >>>>>>>>", $params);

		$this->requestMethod = self::METHOD_POST;

        return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params)
	{
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => $external_transaction_id);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        if($success) {
        	//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
			//for sub wallet
			// $afterBalance = @$playerBalance['balance'];
			// if(!empty($afterBalance)){
			// 	$result["currentplayerbalance"] = $afterBalance;
			// }
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result["external_transaction_id"] = $resultArr['transaction_id'];
			$result['didnot_insert_game_logs']=true;
	    }
	    $result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($resultArr);
        return array($success, $result);
    }

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$recordTransaction=true)
	{
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id'=>$external_transaction_id,
            'recordTransaction'=>$recordTransaction,
        );
        
        $params = array(
        	"user_id" 					=> $gameUsername,
			"partner_id"				=> $this->partnerToken,
			"credits"					=> $this->dBtoGameAmount($amount),
			"currency"					=> $this->currency,
			"custom_json"				=> null,
			"action"					=> self::DEPOSIT,
			"external_transaction_id"	=> $external_transaction_id,
        );

		$this->utils->debug_log("GENESISM4_GAME_API withdrawFromGame params >>>>>>>>", $params);

		$this->requestMethod = self::METHOD_POST;

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

	public function processResultForWithdrawFromGame($params)
	{
    	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $recordTransaction = $this->getVariableFromContext($params, 'recordTransaction');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => $external_transaction_id);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        if($success) {
        	//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
			// //for sub wallet
			// $afterBalance = @$playerBalance['balance'];
			// if(!empty($afterBalance)){
			// 	$result["currentplayerbalance"] = $afterBalance;
			// }
			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdraw
			// 	if($recordTransaction){
			// 		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
			// 	}
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result["external_transaction_id"] = $resultArr['transaction_id'];
			$result['didnot_insert_game_logs']=true;
	    }
	    $result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($resultArr);
        return array($success, $result);
    }


	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 2:
            case 'zh-cn':
                $lang = 'zh-hans'; // chinese
                break;
            default:
                $lang = 'en_US'; // default as english
                break;
        }
        return $lang;
    }

	public function queryForwardGame($userName, $extra = null) {
		$token = $this->queryPlayerBalance($userName);
		$random = $this->utils->getTimestampNow();
		
		$success = false;
		$generateUrl = null;;
		if($extra['mode'] == "trial"){
			$token = array('session_token'=>$random);
		}
		if(!empty($token['session_token'])){
			
			$success = true;
			// $game_name = strtoupper(str_replace( ' ', '', $extra['game_name'] )); 
			$game_name = $extra['game_name'];
			$game_url = ($extra['type'] == "slots") ? $this->slots_game_url : $this->fishing_game_url;
			$game_url =str_replace("?", $game_name, $game_url);
			
			$params = array(
				"partner" 	=> $this->partnerToken,
				"session" 	=> $token['session_token'],
				"mode"		=> $extra['mode'] == "trial" ? "play" : "real"
			);

			# OGP ticket: OGP-10464
			# add param for those gamecode start's with ng-
			if(strtolower(substr($game_name,0,3)) == "ng-"){
				$params['gs'] =  "nurgs-rmx";
			}

			$returnurl = $extra['is_mobile'] ? $this->utils->getSystemUrl('m') : $this->utils->getSystemUrl('www').$this->return_slot_url;
			if (isset($extra['extra']['t1_lobby_url'])) {
				$returnurl = $extra['extra']['t1_lobby_url'];
			}
			$language = $this->getLauncherLanguage($extra['language']);
			if($language == "zh-hans"){
				$params['language'] = $language;
			}
			$url_params = "?".http_build_query($params);
			$generateUrl = $game_url.$url_params."&returnurl=".urlencode($returnurl);
			
		}

		
		$this->utils->debug_log(' GENESISM4_GAME_API queryForwardGame URL: >>>>>>>>' . $generateUrl);

        return [
			'url' => $generateUrl,
			'success' => $success
		];
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token,'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token,'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		// to generate micro seconds
		$t = microtime(true);
        $micro = sprintf("%03d",($t - floor($t)) * 1000);

		// we will format the date according to API param
		$startDate = $startDate->format('Y-m-d\TH:i:s');
		$startDate .= '.' . $micro . 'Z';
		$endDate = $endDate->format('Y-m-d\TH:i:s');
		$endDate .= '.' . $micro . 'Z';

		$this->CI->utils->debug_log('GENESISM4_GAME_API start of syncOriginalGameLogs with dates of: >>>>>>>>',$startDate,$endDate);

		// 5000 limit, startIndex default to 0, because indexes start at zero
		$page = 1;
		$while = true;
		while($while){

			$context = [
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords'
			];
			
			$params = [
				'startDate' => $startDate,
				'endDate' => $endDate,
				'limit' => $this->pageLimit,
				'startIndex' => $this->pageStartIndex
			];
	
			$this->CI->utils->debug_log('GENESISM4_GAME_API queryOriginalGameLogs params: >>>>>>>>',$params);
	
			$this->requestMethod = self::METHOD_GET;
	
			$apiResult = $this->callApi(self::API_syncGameRecords,$params,$context);

			$isMaxReturn = isset($apiResult['is_max_return']) ? $apiResult['is_max_return'] : null;

			$this->CI->utils->info_log('GENESISM4_GAME_API queryOriginalGameLogs is_max_return boolean value: >>>>>>>>',$isMaxReturn);


			# we check here if row count from API response is more than 5000, if so, we need to do a pagination
			if(isset($apiResult['is_max_return']) && $apiResult['is_max_return']){

				$this->CI->utils->info_log('GENESISM4_GAME_API syncOriginalGameLogs details value: row_count >>>>>>>>',$apiResult['row_count'],'is_max_return',$apiResult['is_max_return'],'index',$apiResult['index'],'fetch_size',$apiResult['fetch_size']);

				$page++;
				$this->pageStartIndex = (($page-1) * $this->pageLimit);
				continue;
			}

			$while = false;

		}

		return [
			true,
			$apiResult
		];
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('genesism4_game_logs','original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$dataResult =	[
            'data_count' => 0,
            'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'is_max_return' => false,
			'index' => 0,
			'fetch_size' => 0,
			'row_count' => 0
		];

		if($success){
			$apiReturnData = (isset($resultArr['data']) && is_array($resultArr['data'])) ? $resultArr['data'] : [];
			$apiReturnTotalQuerySize = isset($resultArr['total_query_size']) ? $resultArr['total_query_size'] : null;
			$apiReturnStartIndex = isset($resultArr['start_index']) ? $resultArr['start_index'] : null;
			$apiReturnFetchSize = isset($resultArr['fetch_size']) ? $resultArr['fetch_size'] : null;

			if(count($apiReturnData) > 0){

				# check if total_query_size is more than 5000
				if($apiReturnTotalQuerySize > $this->pageLimit && ($apiReturnTotalQuerySize > ($this->pageStartIndex + 1))){

					$dataResult = $this->insertOgl($apiReturnData,$responseResultId);
					
					return [
						true,
						[
							'row_count' => $apiReturnTotalQuerySize,
							'is_max_return' => true,
							'index' => $apiReturnStartIndex,
							'fetch_size' => $apiReturnFetchSize
						]
					];
				}

				$dataResult['is_max_return'] = false;

				$dataResult = $this->insertOgl($apiReturnData,$responseResultId);
			}
		}

		return [
			$success,
			$dataResult
		];
	}

	/** 
	 * Insert Original Game logs Data
	 * 
	 * @param $apiReturnData
	 * @param $responseResultId
	 * 
	 * @return arrray
	*/
	public function insertOgl(&$apiReturnData,$responseResultId){
		$this->processGameRecords($apiReturnData,$responseResultId);

		list($insertRows,$updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
			$this->originalGameLogsTable,
			$apiReturnData,
			'external_uniqueid',
			'external_uniqueid',
			self::MD5_FIELDS_FOR_ORIGINAL,
			'md5_sum',
			'id',
			self::MD5_FLOAT_AMOUNT_FIELDS
		);

		$cntGameRecords = is_array($apiReturnData) ? count($apiReturnData) : null;
		$cntInsertRows = is_array($insertRows) ? count($insertRows) : null;
		$cntUpdateRows = is_array($updateRows) ? count($updateRows) : null;

		$this->CI->utils->debug_log("GENESISM4_GAME_API syncOriginalGameLogs process row count is: >>>>>>>>",$cntGameRecords,"Inserted Rows is:",$cntInsertRows,"Updated Rows is:",$cntUpdateRows);

		$dataResult['data_count'] = $cntGameRecords;

		if(count($apiReturnData) > 0){
			$dataResult["data_count_insert"] = $this->updateOrInsertOriginalGameLogs($insertRows,"insert");
		 }
		 unset($insertRows);

		 if(count($apiReturnData) > 0){
			$dataResult["data_count_update"] = $this->updateOrInsertOriginalGameLogs($updateRows,"update");
		 }
		 unset($updateRows);

		 return $dataResult;
	}

	public function processGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords)){
            
            foreach($gameRecords as $index => $record){

				$data['partner_data'] = isset($record['partner_data']) ? $record['partner_data'] : NULL;
				$data['user_id'] = isset($record['user_id']) ? $record['user_id'] : NULL;
				$data['game_id'] = isset($record['game_id']) ? $record['game_id'] : NULL;
				$data['causality'] = isset($record['causality']) ? $record['causality'] : NULL;
				$data['timestamp'] = isset($record['timestamp']) ? (date('Y-m-d H:i:s', strtotime($record['timestamp']))) : NULL;
				$data['currency'] = isset($record['currency']) ? $record['currency'] : NULL;
				$data['total_bet'] = isset($record['total_bet']) ? $record['total_bet'] : NULL;
				$data['total_won'] = isset($record['total_won']) ? $record['total_won'] : NULL;
				$data['balance'] = isset($record['balance']) ? $record['balance'] : NULL;
				$data['merchantcode'] = isset($record['merchantcode']) ? $record['merchantcode'] : NULL;
				$data['device'] = isset($record['device']) ? $record['device'] : NULL;
				$data['user_type'] = isset($record['user_type']) ? $record['user_type'] : NULL;
				$data['player_id'] = 0; // default to 0, because not in API response and not nullable in DB
                # default data
				$data['external_uniqueid'] = isset($record['causality']) ? $record['causality'] : NULL;
				$data['response_result_id'] = $responseResultId;
				$data['created_at'] = $this->CI->utils->getNowForMysql();
                
                $gameRecords[$index] = $data;
				unset($data);
            }
        }
	}
	
	/** 
     * Update or Insert for Original game logs Table
     * 
     * @param array $data
     * @param string $queryType
     * 
     * @return int
    */
    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
      $dataCount=0;
      if(!empty($data)){
          foreach ($data as $record) {
              if ($queryType == 'update') {
                  $record['updated_at'] = $this->utils->getNowForMysql();
                  $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalGameLogsTable, $record);
              } else {
                  unset($record['id']);
                  $record['created_at'] = $this->utils->getNowForMysql();
                  $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalGameLogsTable, $record);
              }
              $dataCount++;
              unset($record);
          }
      }
      return $dataCount;
   }

	public function syncMergeToGameLogs($token)
	{
		$this->CI->load->model(array('genesism4_game_logs'));

		$enabled_game_logs_unsettle = false;
		return $this->commonSyncMergeToGameLogs($token,
		 $this,
		 [$this,'queryOriginalGameLogs'],
		 [$this,'makeParamsForInsertOrUpdateGameLogsRow'],
		 [$this, 'preprocessOriginalRowForGameLogs'],
		 $enabled_game_logs_unsettle
		);
	}

	/** 
     * Query Original Game Logs for Merging
     * 
     * @param string $dateFrom where the date start for sync original
     * @param string $dataTo where the date end
	 * @param boolean $use_bet_time
     * 
     * @return array 
    */
    public function queryOriginalGameLogs($dateFrom,$dateTo,$use_bet_time)
    {
		return $this->CI->genesism4_game_logs->getGameLogStatistics($dateFrom, $dateTo);
	}
	
	/** 
	 * Process result from original table before merging
	 * 
	 * @param array $row
	 * 
	 * @return array
	*/
	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
			'trans_amount'=> isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : null,
			'table' => isset($row['causality']) ? $row['causality'] : null,
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
		}
		
		$fishing = $row['game_code'] == self::FISHINGID;
		$after_balance = $fishing ? null : $this->gameAmountToDB($row['after_balance']);
		$betAmount = isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : null;
		$resultAmount = (isset($row['won_amount']) && isset($row['bet_amount'])) ? $this->gameAmountToDB($row['won_amount'] - $row['bet_amount']) : 0;

        return [
            'game_info' => [
                'game_type_id' => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type' => null,
                'game' =>  isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id' => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username' => isset($row['user_id']) ? $row['user_id'] : null
            ],
            'amount_info' => [
                'bet_amount' => $betAmount,
                'result_amount' => $resultAmount,
                'bet_for_cashback' => $betAmount,
                'real_betting_amount' =>  $betAmount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance
            ],
            'date_info' => [
                'start_at' => isset($row['game_date']) ? $row['game_date'] : null,
                'end_at' => isset($row['game_date']) ? $row['game_date'] : null,
                'bet_at' => isset($row['game_date']) ? $row['game_date'] : null,
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => "",
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null # BET_TYPE_MULTI_BET or BET_TYPE_SINGLE_BET
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null
        ];
	}
	
	/**
     * Prepare Original rows, include process unknown game, pack bet details, convert game status
     *
     * @param array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
		$this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        # we process unknown game here
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
		}
		
		$bet_details = [
            'Currency' => isset($row['currency']) ? $row['currency'] : "",
            'Device Type' => isset($row['device']) ? $row['device'] : "",
        ];
        
        $row['game_description_id' ]= $game_description_id;
		$row['game_type_id'] = $game_type_id;
		$row['bet_details'] = $bet_details;
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_name = $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $game_name, $game_type, $external_game_id, $extra,
            $unknownGame);
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

	public function processResultForgetVendorId($params) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'transactionId' => $transactionId,
        );

        $params = array(
			"method" 			=> self::URI_MAP[self::API_queryTransaction].$transactionId,
			"request_method" 	=> self::METHOD_GET
        );
        $this->utils->debug_log("Query Transaction params ============================>", $params);
        return $this->callApi(self::API_queryTransaction, $params, $context); 
	}

	public function processResultForQueryTransaction($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        return array($success, $resultArr);
	}

	public function getReasonId($params){
		if (array_key_exists("error",$params)){
			$reason = $params['error']['reason'];
			switch ($reason) {
			    case (strpos($reason, '(RECIPIENT_FAILURE,400)')):
			        return self::REASON_FAILED_FROM_API;
			        break;
			    case (strpos($reason, '(RECIPIENT_FAILURE,403)')):
			        return self::REASON_INVALID_KEY;
			        break;
			    case (strpos($reason, '(RECIPIENT_FAILURE,404)')):
			        return self::REASON_NOT_FOUND_PLAYER;
			        break;
			    case (strpos($reason, '(RECIPIENT_FAILURE,500)')):
			        return self::REASON_NETWORK_ERROR;
			        break;
			    default:
			        return self::REASON_UNKNOWN ;
			}
		}
    	return self::REASON_UNKNOWN ;
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

	public function generatePromoLink($playerName,$extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$token = $this->queryPlayerBalance($playerName);

		$params =array(
			"playerid" => $gameUsername,
			"partner" => $this->partnerToken,
			"currency" => $this->currency
		);

		$input = json_encode($params);
		$key = $this->promo_secret_key;
		$data = openssl_encrypt($input, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
 		$data = base64_encode($data);
 		$url = $this->promo_url.$data;

        switch (@$extra['language']) {
            case 2:
            case 'zh-cn':
                $lang = 'cn'; // chinese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }

 		return $url.'&lang='.$lang;
	}
	
}

/*end of file*/