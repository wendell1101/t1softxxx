<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Check Transaction
 * * Synchronize Player Account
 * * Create Player
 * * Deposit to game
 * * Withdraw from game
 * * Check player balance
 * * Check if player exist
 * * Block/Unblock Player
 * * logout
 * * Check Login Status
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get Game Description Information
 * Behaviors not implemented
 * * Check Player's information
 * * Change password
 * * login
 * * Update Player's information
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Login Token
 * * Check total Betting amount
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_gd extends Abstract_game_api {

	private $api_url;
	private $api_key;
	private $game_launcher_url;
	private $currentProcess;
	private $MerchantID;
	private $CurrencyCode;
	private $messageId;

	const URI_MAP = array(
		self::API_createPlayer => 'cCreateMember',
		self::API_depositToGame => 'cDeposit',
		self::API_withdrawFromGame => 'cWithdrawal',
		self::API_queryPlayerBalance => 'cCheckClient',
		self::API_isPlayerExist => 'cCreateMember',
		self::API_operatorLogin => 'cMemberAuthentication',
		self::API_logout => 'cLogoutPlayer',
		self::API_queryTransaction => 'cCheckTransactionStatus',
	);

	const GD_syncGameRecordsLive = 'cGetBetHistory';
	const GD_syncGameRecordsSlots = 'cGetRNGBetHistory';

	const MESSAGE_ID_PREFIX_TRANSACTION_STATUS = 'S';
	const MESSAGE_ID_PREFIX_CREATE_MEMBER = 'M';
	const MESSAGE_ID_PREFIX_DEPOSIT = 'D';
	const MESSAGE_ID_PREFIX_WITHDRAWAL = 'W';
	const MESSAGE_ID_PREFIX_BET_HISTORY_LIVE = 'H';
	const MESSAGE_ID_PREFIX_BET_HISTORY_SLOTS = 'R';
	const MESSAGE_ID_PREFIX_MEMBER_BALANCE = 'C';
	const MESSAGE_ID_PREFIX_MEMBER_LOGOUT = 'L';

	const ShowBalance = '1';
	const Index = '0';
	const ShowRefID = '1';

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->api_key = $this->getSystemInfo('key');
		$this->game_launcher_url = $this->getSystemInfo('game_launcher_url');
		$this->MerchantID = $this->getSystemInfo('merchant_id');
		$this->CurrencyCode = $this->getSystemInfo('currency_code');
		$this->merge_after_balance = $this->getSystemInfo('merge_after_balance',false);

	}

	public function getPlatformCode() {
		return GD_API;
	}

	public function generateUrl($apiName, $params) {

		$url = $this->api_url;

		return $url;

	}

	protected function customHttpCall($ch, $params) {

		$randomString = substr(date('YmdHis'), 2) . random_string('alnum', 5);

		switch ($this->currentProcess) {
			case self::URI_MAP[self::API_logout]:
				$header = array(
					"Method" => self::URI_MAP[self::API_logout],
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_MEMBER_LOGOUT . $randomString,
				);
				break;

			case self::URI_MAP[self::API_isPlayerExist]:
				$header = array(
					"Method" => self::URI_MAP[self::API_isPlayerExist],
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_CREATE_MEMBER . $randomString,
				);
				break;
			case self::URI_MAP[self::API_createPlayer]:
				$header = array(
					"Method" => self::URI_MAP[self::API_createPlayer],
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_CREATE_MEMBER . $randomString,
				);
				break;

			case self::URI_MAP[self::API_depositToGame]:
				$header = array(
					"Method" => self::URI_MAP[self::API_depositToGame],
					"MerchantID" => $this->MerchantID,
					'MessageID' => $this->messageId,
				);
				break;

			case self::URI_MAP[self::API_withdrawFromGame]:
				$header = array(
					"Method" => self::URI_MAP[self::API_withdrawFromGame],
					"MerchantID" => $this->MerchantID,
					'MessageID' => $this->messageId,
				);
				break;

			case self::GD_syncGameRecordsLive:
				$header = array(
					"Method" => self::GD_syncGameRecordsLive,
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_BET_HISTORY_LIVE . $randomString,
				);
				break;

			case self::GD_syncGameRecordsSlots:
				$header = array(
					"Method" => self::GD_syncGameRecordsSlots,
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_BET_HISTORY_SLOTS . $randomString,
				);
				break;

			case self::URI_MAP[self::API_queryPlayerBalance]:
				$header = array(
					"Method" => self::URI_MAP[self::API_queryPlayerBalance],
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_MEMBER_BALANCE . $randomString,
				);
				break;

			case self::URI_MAP[self::API_queryTransaction]:
				$header = array(
					"Method" => self::URI_MAP[self::API_queryTransaction],
					"MerchantID" => $this->MerchantID,
					'MessageID' => self::MESSAGE_ID_PREFIX_TRANSACTION_STATUS . $randomString,
				);
				break;
		}

		$data = array(
			'Header' => $header,
			'Param' => $params,
		);

		$xml_object = new SimpleXMLElement("<Request></Request>");
		$xmlData = $this->CI->utils->arrayToXml($data, $xml_object);
		$this->CI->utils->debug_log('-----------------------GD POST XML STRING ----------------------------',$xmlData);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

		$success = !empty($resultArr) && $resultArr['Header']['ErrorCode'] == '0';

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GD got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->currentProcess = self::URI_MAP[self::API_queryTransaction];
        
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"MessageID" => $transactionId,
			"UserID" => $gameUsername,
			"CurrencyCode" => $this->getPlayerGDCurrency($gameUsername)
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
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
			$error_code = @$resultArr['Header']['ErrorCode'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		$this->CI->utils->debug_log('username', $username, 'playerId', $playerId);
		// $success = false;
		$balance = null;
		$rlt = $this->isPlayerExist($username);
		$success = $rlt['success'];
		if ($rlt['success']) {
			if ($rlt['exists']) {
				//update register flag
				$this->updateRegisterFlag($playerId, true);
			} else {
				$rlt = $this->createPlayer($username, $password, $playerId);
				$success = $rlt['success'];
				if ($rlt['success']) {
					$this->updateRegisterFlag($playerId, true);
				}
			}
		}
		if ($success) {
			//update balance
			$rlt = $this->queryPlayerBalance($username);
			$success = $rlt['success'];
			if ($success) {
				//for sub wallet
				$balance = isset($rlt['balance']) ? floatval($rlt['balance']) : null;
				if ($balance !== null) {
					//update
					$this->updatePlayerSubwalletBalance($playerId, $balance);
				}
			}

		}
		return array('success' => $success, 'balance' => $balance);
	}


	private function getPlayerGDCurrency($username){
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				return $currencyCode;
			}else{
				return $this->CurrencyCode;
			}
		}else{
			return $this->CurrencyCode;
		}
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		/*
			   <?xml version="1.0"?>
				<Request>
					<Header>
				        <Method>cCreateMember</Method>
						<MerchantID>Yabotest</MerchantID>
						<MessageID>M160428032401abzdf</MessageID>
					</Header>
					<Param>
						<UserID>testplayer</UserID>
				        <CurrencyCode>CNY</CurrencyCode>
					</Param>
				</Request>
		*/

		$this->currentProcess = self::URI_MAP[self::API_createPlayer];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			'UserID' => $playerName,
			'CurrencyCode' => $this->getPlayerGDCurrency($playerName),
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	function processResultForCreatePlayer($params) {

		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$this->CI->utils->debug_log('-----------------------GD POST RESPONSE XML STRING ----------------------------', @$params['resultText']);
		$resultArr = json_decode(json_encode($resultXml), true);

		$result = array();
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName)) {

			$success = true;
			$result['result'] = $resultArr;
			$this->CI->utils->debug_log('-----------------------GD SUCCESS CREATE PLAYER----------------------------',$result['result']);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} else {
			$result['errorcode'] = @$resultArr['Header']['ErrorCode'];
			$result['error'] = @$resultArr['Param']['ErrorDesc'];

			if($result['errorcode']=='207'){ //exists
				$result['exists'] = true;
				$success=true;
			}

			$this->CI->utils->debug_log('-----------------------GD ERROR CREATE PLAYER----------------------------',$result);

		}

		return array($success, $result);

	}

	function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		/*
			<?xml version="1.0"?>
			<Request>
				<Header>
			        <Method>cDeposit</Method>
					<MerchantID>Yabotest</MerchantID>
					<MessageID>D160428032401abrdf</MessageID>
				</Header>
				<Param>
					<UserID>testplayer</UserID>
					<CurrencyCode>CNY</CurrencyCode>
					<Amount>1</Amount>
				</Param>
			</Request>
		*/

		$randomString = substr(date('YmdHis'), 2) . random_string('alnum', 5);
		$external_trans_id = $this->messageId =  self::MESSAGE_ID_PREFIX_DEPOSIT . $randomString;

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->currentProcess = self::URI_MAP[self::API_depositToGame];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'external_transaction_id' => $external_trans_id

		);

		$params = array(
			'UserID' => $gameUsername,
			'CurrencyCode' => $this->getPlayerGDCurrency($gameUsername),
			'Amount' => $amount,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		/*
			<?xml version="1.0" encoding="UTF-8"?>
			<Reply>
			   <Header>
			      <Method>cDeposit</Method>
			      <ErrorCode>0</ErrorCode>
			      <MerchantID>Yabotest</MerchantID>
			      <MessageID>D160429192204KAEeb</MessageID>
			   </Header>
			   <Param>
			      <TransactionID>c8321439-0c4f-a626-bf8d-099504c4c58a</TransactionID>
			      <PaymentID>D160429192204KAEeb</PaymentID>
			      <ErrorDesc />
			   </Param>
			</Reply>
		*/

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultArr['Header']['ErrorCode'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	private function getReasons($error_code){
		switch($error_code) {
			case '201' :
				 return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case '202' :
				 return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;
			case '203' :
				 return self::REASON_NO_ENOUGH_BALANCE;
				break;
			case '204' :
			case '205' :  // error client in game
				 return self::REASON_LOCKED_GAME_MERCHANT;
				break;
			case '206' :  // message id existed
				 return self::REASON_DUPLICATE_TRANSFER;
				break;
            default:
                return self::REASON_UNKNOWN;
                break;
		}
	}
			

	function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		/*
			<?xml version="1.0"?>
			<Request>
				<Header>
			        <Method>cDeposit</Method>
					<MerchantID>Yabotest</MerchantID>
					<MessageID>D160428032401abrdf</MessageID>
				</Header>
				<Param>
					<UserID>testplayer</UserID>
					<CurrencyCode>CNY</CurrencyCode>
					<Amount>1</Amount>
				</Param>
			</Request>
		*/

		$randomString = substr(date('YmdHis'), 2) . random_string('alnum', 5);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_trans_id = $this->messageId =  self::MESSAGE_ID_PREFIX_WITHDRAWAL . $randomString;
		$this->currentProcess = self::URI_MAP[self::API_withdrawFromGame];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'external_transaction_id' => $external_trans_id

		);

		$params = array(
			'UserID' => $gameUsername,
			'CurrencyCode' => $this->getPlayerGDCurrency($gameUsername),
			'Amount' => $amount,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {
		/*
			<?xml version="1.0" encoding="UTF-8"?>
			<Reply>
			   <Header>
			      <Method>cWithdrawal</Method>
			      <ErrorCode>0</ErrorCode>
			      <MerchantID>Yabotest</MerchantID>
			      <MessageID>W160429194121d3cAr</MessageID>
			   </Header>
			   <Param>
			      <TransactionID>2ceabc02-7bcb-b95c-c016-bba328c0b96d</TransactionID>
			      <PaymentID>W160429194121d3cAr</PaymentID>
			      <ErrorDesc />
			   </Param>
			</Reply>
		*/

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());

			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultArr['Header']['ErrorCode'];
            $result['reason_id']=$this->getReasons($error_code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	function queryPlayerBalance($playerName) {
		/*
			<?xml version="1.0"?>
			<Request>
				<Header>
			        <Method>cCheckClient</Method>
					<MerchantID>Yabotest</MerchantID>
					<MessageID>C16042803240184r4b</MessageID>
				</Header>
				<Param>
				<UserID>siopao36</UserID>
				<CurrencyCode>CNY</CurrencyCode>
				</Param>
			</Request>
		*/

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$this->currentProcess = self::URI_MAP[self::API_queryPlayerBalance];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			//'playerId' => $playerId,
		);

		$params = array(
			'UserID' => $playerName,
			'CurrencyCode' => $this->getPlayerGDCurrency($playerName),
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {
		//var_dump($params);//exit();
		//var_dump($params); exit();
		/*
		<?xml version="1.0" encoding="UTF-8"?>
		<Reply>
		   <Header>
		      <Method>cCheckClient</Method>
		      <ErrorCode>0</ErrorCode>
		      <MerchantID>Yabotest</MerchantID>
		      <MessageID>C160429195837OXa69</MessageID>
		   </Header>
		   <Param>
		      <UserID>siopao36</UserID>
		      <CurrencyCode>CNY</CurrencyCode>
		      <Balance>18.00</Balance>
		      <ErrorDesc />
		   </Param>
		</Reply>
		*/

		$playerName = $this->getVariableFromContext($params, 'playerName');
		//$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = false;
		$result = array();

		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName) && isset($resultArr['Param']['Balance'])) {

			$success = true;
			$result['balance'] = @floatval($resultArr['Param']['Balance']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$success = false;
			if (@$resultArr['error'] == 'PLAYER NOT FOUND') {
				$result['exists'] = false;
			} else {
				$result['exists'] = true;
			}
		}

		return array($success, $result);

	}

	function isPlayerExist($playerName) {
		/*
			   <?xml version="1.0"?>
				<Request>
					<Header>
				        <Method>cCreateMember</Method>
						<MerchantID>Yabotest</MerchantID>
						<MessageID>M160428032401abzdf</MessageID>
					</Header>
					<Param>
						<UserID>testplayer</UserID>
				        <CurrencyCode>CNY</CurrencyCode>
					</Param>
				</Request>
		*/
		$this->currentProcess = self::URI_MAP[self::API_isPlayerExist];
		$sbe_playerName = $playerName;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'sbe_playerName' => $sbe_playerName,
		);

		$params = array(
			'UserID' => $playerName,
			'CurrencyCode' => $this->getPlayerGDCurrency($playerName),
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	function processResultForIsPlayerExist($params) {

		//var_dump($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$result = array();
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName)) {
			$success = true;
			$result['exists'] = false;
			$playerId = $this->getPlayerIdInPlayer($sbe_playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

		} else {
			$result['exists'] = true;
			$result['errorcode'] = @$resultArr['Header']['ErrorCode'];
			$result['error'] = @$resultArr['Param']['ErrorDesc'];

		}

		return array($success, $result);

	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {

		return $this->returnUnimplemented();
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {

		/*
			   <?xml version="1.0"?>
				<Request>
					<Header>
				        <Method>cCreateMember</Method>
						<MerchantID>Yabotest</MerchantID>
						<MessageID>L160428032401abzdf</MessageID>
					</Header>
					<Param>
						<UserID>testplayer</UserID>
				      </Param>
				</Request>
		*/
		$this->currentProcess = self::URI_MAP[self::API_logout];
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$params = array(
			'UserID' => $playerName,
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		//var_dump($resultArr);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr)) {
			$success = true;
		} else {
			$success = false;
		}
		return array($success, null);
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {

		//return $this->returnUnimplemented();
		return array("success" => true);
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}


    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en-us'; // english
                break;
            case 2:
            case 'zh-cn':

                $lang = 'zh-cn'; // chinese
                break;
            default:
                $lang = 'en-us'; // default as english
                break;
        }
        return $lang;
    }

	function queryForwardGame($playerName, $extra) {

		$rlt = $this->checkLoginStatus($playerName);
		$password = $this->getPasswordString($playerName);
		if (!$rlt['success']) {
			$rlt = $this->login($playerName, $password);
			if (!$rlt['online']) {
				return array(
					'success' => false,
				);
			}
		}


		//NOTE:LOGIN WITH VPN
		//http://your_game_URL/main.php?OperatorCode=AAA&lang=BBB&playerid=CCC&LoginTokenID=DDD&Currency=EEE&Key=FFF&view=N&mode=real&nickname=desire_name&mobile=1&PlayerGroup=default
		// AAA = the merchant code assigned to you (unchanged from current login method)
		// BBB = the initial language to display in game (unchanged from current login method)
		// CCC = Player ID (unchanged from current login method)
		// DDD = Login token generated for this login session (unchanged from current login method) Merchant’s generated Token (generated by Merchant for merchant to verify the login process at the next step), the maximum length of the Token is 128 characters and special symbols (!@#$%^&*()_+<>?) is not support. The token will be expired after 24 hours.
		// EEE = Three -letter currency code in uppercase of the player’s account (required for one-way authentication)
		// FFF = A security key generated using a predefined algorithm to authenticate the request as genuine (required for one-way authentication)
		// 1 = additional parameter when logging in through mobile web client,
		//FFF = SHA256(AAA + DDD + Merchant Access Key + CCC + EEE)

		//var_dump($extra);exit();
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$AAA = $this->MerchantID; //OperatorCode
		$BBB = $this->getLauncherLanguage($extra['language']); //"en-us";//lang
		$CCC = $playerName; //playerid
		$DDD = random_string('alnum', 128); //LoginTokenID
		$EEE = $this->getPlayerGDCurrency($playerName); //Currency
		$FFF = hash('sha256', $AAA . $DDD . $this->api_key . $CCC . $EEE);
		$view = $extra['game_code']; //'RNG4583';

		$params = array(
			'OperatorCode' => $AAA,
			'lang' => $BBB,
			'playerid' => $CCC,
			'LoginTokenID' => $DDD,
			'Currency' => $EEE,
			'Key' => $FFF,
			'view' => $view,
		);

		if($extra['game_type']=='slots'){
			$params['mode'] =  $extra['game_mode'] == "real"?$extra['game_mode']:"fun";
		}
		$params['mobile'] = $extra['is_mobile']?"2":"0";

		$params_string = http_build_query($params);
		$link = $this->game_launcher_url . "?" . $params_string;
		$this->utils->debug_log('==========================================>link===========>', $link);
		return array('success' => true, 'url' => $link);

	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate->modify($this->getDatetimeAdjust());

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$this->syncOriginalGameLogsOnLive($startDate->format('m/d/Y H:i:s'), $endDate->format('m/d/Y H:i:s'));
		sleep(2);// sleep 2 seconds
		$this->syncOriginalGameLogsOnSlots($startDate->format('m/d/Y H:i:s'), $endDate->format('m/d/Y H:i:s'));
		return array("success"=>true);
	}

	function syncOriginalGameLogsOnLive($startDate, $endDate) {

		/*
			<?xml version="1.0"?>
				<Request>
					<Header>
						<Method>cGetBetHistory</Method>
						<MerchantID>1235</MerchantID>
						<MessageID>H110830134512K9n8d</MessageID>
					</Header>
					<Param>
						<FromTime>08/01/2011 00:00:00</FromTime>
						<ToTime>09/30/2011 00:00:00</ToTime>
						<Index>2</Index>
				<UserID>Player_1313455</UserID>
				<ShowBalance>1</ShowBalance>
				<SearchByBalanceTime>1</SearchByBalanceTime>
					</Param>
				</Request>
		*/

		//206  - This message id was used before
		//201   -wrong xml format/No matching records found
		$this->currentProcess = self::GD_syncGameRecordsLive;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'currnet_process' => $this->currentProcess
		);

		$params = array(

			'FromTime' => $startDate,
			'ToTime' => $endDate,
			'Index' => self::Index,
			'ShowBalance' => self::ShowBalance,
			'ShowRefID' => self::ShowRefID,
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	private function syncOriginalGameLogsOnSlots($startDate, $endDate) {

		/*
			<?xml version="1.0"?>
				<Request>
					<Header>
						<Method>cGetRNGBetHistory</Method>
						<MerchantID>1235</MerchantID>
						<MessageID>R110830134512K9n8d</MessageID>
					</Header>
					<Param>
						<FromTime>08/01/2011 00:00:00</FromTime>
						<ToTime>09/30/2011 00:00:00</ToTime>
						<Index>2</Index>
					</Param>
				</Request>
		*/

		$this->currentProcess = self::GD_syncGameRecordsSlots;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogsOnSlots',
			'currnet_process' => $this->currentProcess,
		);

		$params = array(
			'FromTime' => $startDate,
			'ToTime' => $endDate,
			'Index' => self::Index,
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	function processResultForSyncOriginalGameLogsOnSlots($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$this->CI->load->model(array('gd_game_logs'));

		$result = array();
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		if($success){
			if(isset($resultArr['Param']) && isset($resultArr['Param']['BetInfo'])){
				$gameRecords = $resultArr['Param']['BetInfo'];
				$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
	        	$availableRows = $this->CI->gd_game_logs->getAvailableRows($gameRecords);

				foreach ($availableRows as $record) {
					if($record['Status'] != "Completed"){
						continue; #skip on progress bet
					}
					$insertArray = array();
					$insertArray['no'] = isset($record['No'])?$record['No']:null;
					$insertArray['user_id'] = isset($record['UserID'])?$record['UserID']:null;
					$insertArray['bet_time'] = isset($record['Time'])? $this->gameTimeToServerTime($record['Time']):null;
					$insertArray['product_id'] = $insertArray['gameshortcode'] = isset($record['RNGID'])?$record['RNGID']:null;
					$insertArray['bet_id'] = isset($record['BetID'])?$record['BetID']:null;
					$insertArray['bet_amount'] = isset($record['BetAmount'])?$record['BetAmount']:null;
					$insertArray['win_loss'] = isset($record['WinLoss'])?$record['WinLoss']:0;
					$insertArray['jackpot_win'] = isset($record['JackpotWin'])?$record['JackpotWin']:null;
					$insertArray['status'] = isset($record['Status'])?$record['Status']:null;

					#SBE Data
					$insertArray['game_id'] = isset($record['RNGID'])?$record['RNGID']:null;
					$insertArray['external_uniqueid'] = isset($record['BetID'])?$record['BetID']:null;
                    $insertArray['response_result_id'] = $responseResultId;

					$this->CI->gd_game_logs->insertGdGameLogs($insertArray);
				}
			}
		}else{
			$result['error'] = @$resultArr['Header']['ErrorCode'];
		}
		return array($success,$result);
	}

	function processResultForSyncGameRecords($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$currnet_process = $this->getVariableFromContext($params, 'currnet_process');

		if(isset($resultXml)){
		    $resultArr = json_decode(json_encode($resultXml), true);
		}

		$result = array();
		$success = false;

		$success = $this->processResultBoolean($responseResultId, $resultArr);

        $count = 0;
		if (!$success) {

			$result['error'] = @$resultArr['Header']['ErrorCode'];

		} else {

			if(isset($resultArr['Param'])){

				$currentGdGameType = isset($resultArr['Header']['Method']) ? $resultArr['Header']['Method'] : null;

				$this->CI->load->model(array('gd_game_logs', 'external_system'));

				if (isset($resultArr['Param']['BetInfo']) && count($resultArr['Param']['BetInfo'])) {

					$gameRecords = $resultArr['Param']['BetInfo'];
					$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
		        	$availableRows = $this->CI->gd_game_logs->getAvailableRows($gameRecords);

                    $game_records = $this->prepareGameRecord($availableRows, $currentGdGameType,$responseResultId);
                    $count = $game_records['count'];
                    $this->CI->utils->debug_log('Sync Original GD count =============>', $count);
				} else {
					$success = true;
				}

			}

		}//success end

		return array($success, array('count'=>$count));

	}

    public function prepareGameRecord($game_records, $currentGdGameType,$responseResultId){
        $gdGameLogs = array();
        $listOfGameId = array();

        if( ! empty($game_records)){
            foreach ($game_records as $record) {
                # Much faster than ternary operator
                $balance_time = null;
                if(isset($record['BalanceTime'])){
                    $balance_time = $this->convertToDateTime($record['BalanceTime']) ;
                    if($balance_time== "1970-01-01 08:00:00"){
                        continue;
                    }
                }else{
                    if(isset($record['Time'])){
                      $balance_time = $record['Time'];
                    }

                }

                $bet_time = null;
                if(isset($record['BetTime'])){
                    $bet_time = $this->convertToDateTime($record['BetTime']) ;
                }else{
                    if(isset($record['Time'])){
                      $bet_time = $record['Time'];
                    }

                }


                if(isset($record['ProductID']) || isset($record['RNGID'])){
                  $gameshortcode  =   $record['ProductID'] ?: $record['RNGID'];
                }


                #Fix  endbalance bug becomes array sometimes on live
                if(isset($record['EndBalance'])){
                    $endBalStr = json_encode($record['EndBalance']);
                    // $endBalance = intval(preg_replace('/[^0-9]+/', '', $endBalStr), 10);
                    $endBalance = $this->get_numerics($endBalStr);
                }

                #don't insert existing bet id
                $isExist = $this->CI->gd_game_logs->checkGameID($record['BetArrays']['Bet']['GameID'], $record['BetID'],$record['UserID']);
                if( ! empty($isExist)) continue;

                $currentGameIdData = $this->CI->gd_game_logs->isGameIdAlreadyExist($record['BetArrays']['Bet']['GameID'],$record['UserID']);

                #check Gameid only, if game id exist merge the current row (bet id) to column `extra`
                if( ! empty($currentGameIdData)){

                    $newGameRecord['extra'] = $this->prepareGameRecordExtra($currentGameIdData,$record,$endBalance);
                    $newGameRecord['extra'] = json_encode($newGameRecord['extra']);

                    $newGameRecord['game_id'] = $record['BetArrays']['Bet']["GameID"];
                    $this->CI->gd_game_logs->updateGameLogs($newGameRecord,$record['UserID'],$endBalance);

                    continue;
                }

                $unique_id = $record['BetArrays']['Bet']['GameID'] . $record['UserID'];

                if( ! in_array($unique_id, $listOfGameId)){
                    array_push($listOfGameId, $unique_id);
                    $gdGameLogs[$unique_id] = array(
                        'uniqueid'           => isset($record['BetID']) ? $record['BetID'] : null,
                        'gameshortcode'      => $gameshortcode ,
                        'gd_game_type'       => $currentGdGameType,
                        'balance_time'       => $this->gameTimeToServerTime($balance_time),
                        'bet_amount'         => isset($record['BetAmount']) ? $record['BetAmount'] : 0 ,
                        'bet_arrays'         => isset($record['BetAmount']) ? json_encode($record['BetArrays']) : null,
                        'bet_id'             => isset($record['BetID']) ? $record['BetID'] : null,
                        'bet_result'         => isset($record['BetResult']) ? $record['BetResult'] : null,
                        'bet_time'           => $this->gameTimeToServerTime($bet_time),
                        'bet_type'           => isset($record['BetType']) ? $record['BetType'] : null,
                        'end_balance'        => $endBalance  ? $endBalance  : 0,
                        'game_interface'     => isset($record['GameInterface']) ? $record['GameInterface'] : null,
                        'jackpot_win'        => isset($record['JackpotWin']) ? $record['JackpotWin']: null,
                        'no'                 => isset($record['No']) ? $record['No'] : null,
                        'product_id'         => $gameshortcode,
                        'start_balance'      => isset($record['StartBalance']) ? (string)$record['StartBalance']: null,
                        'status'             => isset($record['Status']) ? $record['Status'] : null,
                        'time'               => isset($record['Time']) ? $this->gameTimeToServerTime($record['Time']) : null,
                        'transaction_id'     => isset($record['TransactionID']) ? json_encode($record['TransactionID']) : null,
                        'user_id'            => isset($record['UserID']) ? $record['UserID'] : null,
                        'win_loss'           => isset($record['WinLoss']) ? $record['WinLoss']: 0,

                        #SBE Data
                        'game_id'            => $record['BetArrays']['Bet']['GameID'],
                        'external_uniqueid'  => isset($record['BetID']) ? $record['BetID'] : null,
                        'response_result_id' => $responseResultId,
                        'extra'              => null,
                    );
                }else{
                    $extra = [
                        'odds' => null,
                        'bet_amount' => $record["BetAmount"],
                        'win_amount' => ($record["WinLoss"] > 0) ? $record["WinLoss"]:0,
                        'place_of_bet' => $record['BetArrays']['Bet']['SubBetType'],
                        'after_balance' => $endBalance,
                        'winloss_amount' => $record["WinLoss"],
                    ];

                    $gdGameLogs[$unique_id]['extra'][$record["BetID"]] = $extra;
                }

            }

            $count = 0;
            foreach ($gdGameLogs as $key => $gameRecord) {
                $gameRecord['extra'] = !empty($gameRecord['extra']) ? json_encode($gameRecord['extra']):null;
                $this->CI->gd_game_logs->insertGdGameLogs($gameRecord);
                $count++;
            }

            return ['count'=>$count];
        }else{
            return ['count'=>null];

        }

    }

    public function prepareGameRecordExtra($current_record = null, $new_record, $endBalance){

        if(!empty($current_record['extra'])){
            $extra = json_decode($current_record['extra'],true);
        }

        $extra[$new_record['BetID']] = [
            'odds' => null,
            'bet_amount' => $new_record["BetAmount"],
            'win_amount' => ($new_record["WinLoss"] > 0) ? $new_record["WinLoss"]:0,
            'place_of_bet' => $new_record['BetArrays']['Bet']['SubBetType'],
            'after_balance' => $endBalance,
            'winloss_amount' => $new_record["WinLoss"],
        ];

        return $extra;
    }

	private function get_numerics($str) {
    preg_match_all('/\d+./', $str, $matches);
    //return $matches[0];
    return (float)implode('',$matches[0]);
    }

	private function convertToDateTime($datetime) {

		if ($datetime == null) {
			return null;
		}

		// 05/05/2016 14:20:57
		$dateArr = explode('/', $datetime);
		$yearAndTime = explode(' ', $dateArr[2]);
		$year = $yearAndTime[0];
		$time = $yearAndTime[1];
		$mysqldatetime = $year . '-' . $dateArr[0] . '-' . $dateArr[1] . ' ' . $time;

		return $mysqldatetime;

	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'gd_game_logs', 'game_description_model'));

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate->modify($this->getDatetimeAdjust());
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);

		$rlt = array('success' => true);
		$result = $this->CI->gd_game_logs->getGdGameLogStatistics($startDate, $endDate);

		$cnt = 0;

		if ($result) {

			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $gd_data) {
                $player_id = $this->getPlayerIdInGameProviderAuth($gd_data->gameUsername);

				if ($player_id = $this->getPlayerIdInGameProviderAuth($gd_data->gameUsername)) {

					$username = strtolower($gd_data->gameUsername);

                    $bet_amount = $gd_data->bet_amount;
                    $valid_bet_amount = $bet_amount;
                    // $result_amount = $gd_data->win_loss;
                    $result_amount = round(($gd_data->win_loss - $gd_data->bet_amount), 2);

                    $bet_arrays = json_decode($gd_data->bet_arrays);

                    if ( ! empty($gd_data->extra)) {

                        $extra = json_decode($gd_data->extra,true);
                        $extra['current_bet_id'] = $gd_data->external_uniqueid;
                        list($result_amount, $valid_bet_amount, $bet_amount) = $this->prepareValidBetAmount($gd_data->game_name,$bet_arrays->Bet->SubBetType,$bet_amount,$gd_data->win_loss,$extra);
                    }

                    $bet_details = $this->processBetDetails($gd_data);

					$cnt++;

					$player = $this->CI->player_model->getPlayerById($player_id);

					// $bet_amount = $this->gameAmountToDB($gd_data->bet_amount);
					// $result_amount = $this->gameAmountToDB($gd_data->win_loss);
					// $has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;
					// $winLose = round(($gd_data->win_loss), 2);

					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gd_data, $gameDescIdMap);

                    $extra = array(
                        'trans_amount' => $bet_amount,
                        'bet_details'  => $bet_details['bet_details'],
                        'bet_type'     => $bet_details['multibet'] ? 'Combo Bet':'Single Bet',
                        'table'        => $gd_data->game_id,
                    );

                    # add control on after balance
                    $after_balance = 0;
                    if($this->merge_after_balance){
						$after_balance = $gd_data->end_balance;
                    }


					$this->syncGameLogs(
						//$r = array(
						$game_type_id,
						$game_description_id,
						$gd_data->gameshortcode,
						$gd_data->game_type_id,
						$gd_data->game_name,
						$player_id,
						$gd_data->gameUsername,
						$valid_bet_amount,
						$result_amount,
						null, # win_amount
						null, # loss_amount
						$after_balance,
						null,
						$gd_data->external_uniqueid,
						$gd_data->start_at ? $gd_data->start_at : $gd_data->time,
						$gd_data->end_at ? $gd_data->end_at : $gd_data->time,
						$gd_data->response_result_id,
                        1,
                        $extra
					);
				}
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;

	}

    public function processBetDetails($game_record){
        $game_record = json_decode(json_encode($game_record),true);

        $is_multibet = false;
        $bet_detail = array();
        $bet_type_unique_id = array();
        $filtered_bet_detail = array('bet_details' => null);
        $bet_arrays = json_decode($game_record['bet_arrays'],true);
        $current_bet_placed = json_decode($game_record['bet_arrays'],true)['Bet']['SubBetType'];

        if( ! empty($game_record['extra'])){
            $extra = json_decode($game_record['extra'],true);
            $is_multibet = true;

            foreach ($extra as $key => $data) {
                $win_amount = ($data['winloss_amount'] > 0) ? $data['winloss_amount'] - $data['bet_amount']:$data['win_amount'];
                #for in extra data
                $bet_detail['bet_details'][$key] = [
                    "odds" => null,
                    "win_amount" => $win_amount,
                    "bet_amount" =>  $data['bet_amount'],
                    "bet_placed" =>  $data['place_of_bet'],
                    "won_side" => $bet_arrays['Bet']['WinningBet'],
                    "winloss_amount" => $data['winloss_amount'] - $data['bet_amount'],
                ];

                // $bet_type_unique_id[$data['place_of_bet']] = isset($bet_type_unique_id[$data['place_of_bet']]) ? $bet_type_unique_id[$data['place_of_bet']] .', <br>'. $key:$key;
            }

            $win_amount = ($game_record['win_loss'] > 0) ? $game_record['win_loss'] - $game_record['bet_amount']:$game_record['win_loss'];
            #for current data
            $bet_detail['bet_details'][$game_record['external_uniqueid']] = [
                "odds" => null,
                "win_amount" => $win_amount,
                "bet_amount" =>  $game_record['bet_amount'],
                "bet_placed" => $bet_arrays['Bet']['SubBetType'],
                "won_side" => $bet_arrays['Bet']['WinningBet'],
                "winloss_amount" => $game_record['win_loss'] - $game_record['bet_amount'],
            ];

            // $bet_type_unique_id[$current_bet_placed] = isset($bet_type_unique_id[$current_bet_placed]) ? $bet_type_unique_id[$current_bet_placed] .', <br>'. $game_record['external_uniqueid']: $game_record['external_uniqueid'];

        }else{
            $bet_detail['bet_details'][$game_record['external_uniqueid']] = [
                "odds" => null,
                "win_amount" => $game_record['win_loss'],
                "bet_amount" =>  $game_record['bet_amount'],
                "bet_placed" => $bet_arrays['Bet']['SubBetType'],
                "won_side" => $bet_arrays['Bet']['WinningBet'],
                "winloss_amount" => $game_record['win_loss'],
            ];
        }

        $bet_type_list = array();
        // #filter the same bet type
        // foreach ($bet_detail['bet_details'] as $key => $value) {
        //     if(in_array($value['bet_placed'], $bet_type_list)){
        //         #calculate bet amount for same bet type
        //         $bet_amount = $filtered_bet_detail['bet_details'][$value['bet_placed']]['bet_amount'] + $value['bet_amount'];
        //         #calculation for winloss amount that have same bet placed
        //         $winloss_amount = ($value['winloss_amount'] > 0) ? $value['winloss_amount']: - $bet_amount;
        //         $win_amount = ($value['win_amount'] > 0) ? $value['win_amount']: 0;
        //         #end

        //         $filtered_bet_detail['bet_details'][$value['bet_placed']]['win_amount'] = $win_amount;
        //         $filtered_bet_detail['bet_details'][$value['bet_placed']]['bet_amount'] = $bet_amount;
        //         $filtered_bet_detail['bet_details'][$value['bet_placed']]['winloss_amount'] = $winloss_amount;
        //     }else{
        //         $filtered_bet_detail['bet_details'][$value['bet_placed']] = $value;

        //         #finalize the calculation of winloss amount
        //         $filtered_bet_detail['bet_details'][$value['bet_placed']]['winloss_amount'] = ($value['winloss_amount'] > 0) ? $value['winloss_amount'] -$value['bet_amount']: -$value['bet_amount'];
        //         $filtered_bet_detail['bet_details'][$value['bet_placed']]['win_amount'] = ($value['win_amount'] > 0) ? $value['winloss_amount']-$value['bet_amount']: 0;
        //         #end
        //         array_push($bet_type_list, $value['bet_placed']);
        //     }
        // }

        // foreach ($filtered_bet_detail['bet_details'] as $key => $value) {
        //     if(!empty($bet_type_unique_id[$key])){
        //         $filtered_bet_detail['bet_details'][$bet_type_unique_id[$key]] = $filtered_bet_detail['bet_details'][$key];
        //     }else{
        //         $filtered_bet_detail['bet_details'][$key] = $filtered_bet_detail['bet_details'][$key];
        //     }
        //     unset($filtered_bet_detail['bet_details'][$key]);
        // }

        $bet_details = array(
            "bet_details" => json_encode($bet_detail),
            "multibet" => $is_multibet,
        );

        return $bet_details;

    }

    public function prepareValidBetAmount($current_game_type,$current_bet_type,$current_bet_amount,$current_result_amount,$extra){
        $list_of_opposite_bet_types = [
            "Baccarat" => [
                'player_banker' =>['Player','Banker'],
            ],
            "Dragon Tiger"=> [
                "dragon_tiger" => ['Dragon','Tiger'],
            ],
            "Sicbo"=> [
                "small_big" => ['Small','Big'],
                "odd_even" => ['Even','Odd'],
            ],
            "Roulette" =>[
                "odd_even"=>['Odd','Even'],
                "small_big"=>['1-18','19-36'],
                "red_black"=>['Red','Black'],
            ],
        ];

        $list_of_opposite_bet_type = [
            'Roulette' => ['Odd','Even', 'Red','Black','1-18','19-36'],
            'Baccarat' => ['Player','Banker'],
            'Sicbo' => ['Small','Big','Odd','Even'],
            'Dragon Tiger' => ['Dragon','Tiger']
        ];

        $list_of_current_bet_type = [
            'Baccarat' => [],
            'Roulette' => [],
            'Sicbo' => [],
            'Dragon Tiger' => [],
        ];

        $bet_type_map=[
            'Baccarat' => [],
            'Roulette' => [],
            'Sicbo' => [],
            'Dragon Tiger' => [],
        ];

        $list_of_valid_bet_amount = [];
        $valid_bet_amount = 0;

        $extra[$extra['current_bet_id']] = [
            "bet_amount" => $current_bet_amount,
            "place_of_bet" => $current_bet_type,
            "winloss_amount" => $current_result_amount,
        ];
        unset($extra['current_bet_id']);

        foreach ($extra as $key => $game_record) {

            #insert bet type from extra collumn to list_of_current_bet_type[$current_game_type]
            if( ! in_array($game_record['place_of_bet'], $list_of_current_bet_type[$current_game_type])){

                #check if bet occured 2 times for one bet type only
                if(isset($list_of_current_bet_type[$current_game_type][$game_record['place_of_bet']])){

                    $bet_amount = $list_of_current_bet_type[$current_game_type][$game_record['place_of_bet']]['bet_amount'] + $game_record['bet_amount'];
                    $result_amount = $list_of_current_bet_type[$current_game_type][$game_record['place_of_bet']]['result_amount'] + $game_record['winloss_amount'];

                    $list_of_current_bet_type[$current_game_type][$game_record['place_of_bet']] = [
                        "bet_amount" => $bet_amount,
                        "result_amount" => $result_amount,
                    ];

                }else{
                     $list_of_current_bet_type[$current_game_type][$game_record['place_of_bet']] = [
                        "bet_amount" => $game_record['bet_amount'],
                        "result_amount" => $game_record['winloss_amount'],
                    ];

                }

            }

        }

        #list all available bet
        foreach ($list_of_opposite_bet_types[$current_game_type] as $key => $opposite_bets) {

            foreach ($list_of_current_bet_type[$current_game_type] as $bet_type_key => $bet_details) {

                #calculate the real result amount by subtracting the bet amount to result amount
                $bet_details['result_amount'] = ($bet_details['result_amount']>0) ? $bet_details['result_amount'] - $bet_details['bet_amount']: -$bet_details['bet_amount'];

                #put the bet details in bet type map per opposite bet type
                if(in_array($bet_type_key, $opposite_bets)){
                    $bet_type_map[$current_game_type][$key][$bet_type_key]= $bet_details;
                }

                #list the not opposite bet type for the seperate calcualtion of valid bet amount
                if( ! in_array($bet_type_key, $list_of_opposite_bet_type[$current_game_type])){
                    $bet_type_map[$current_game_type]['not_opposite_bet'][$bet_type_key]= $bet_details;
                }

            }

        }

        #prepare the valid bet amount per opposite bet type
        foreach ($bet_type_map[$current_game_type] as $opposite_bet_name => $current_bet_map) {

            // #opposite bets have different computation
            if ($opposite_bet_name == 'not_opposite_bet') continue;

            // #always clear the data
            $bet_map = [];

            foreach ($current_bet_map as $key => $current_map) {
                if(!empty($bet_map['bet_amount'])){
                    #use result amount when bet amount are equal
                    if($bet_map['bet_amount'] == $current_map['bet_amount']){
                        $current_valid_bet_amount = abs($bet_map['result_amount']+$current_map['result_amount']);
                    } else {
                        $current_valid_bet_amount = abs($bet_map['bet_amount']-$current_map['bet_amount']);
                    }

                    if ($bet_map['bet_amount'] == 0) {
                        $current_valid_bet_amount = $current_map['bet_amount'];
                    }

                    #replace initialized the values
                    $current_total_bet_amount = $bet_map['bet_amount'] + $current_map['bet_amount'];
                    $current_result_amount = $bet_map['result_amount'] + $current_map['result_amount'];
                }else{

                    #for single row only
                    $bet_map = [
                        'bet_amount' => $current_map['bet_amount'],
                        'result_amount' => $current_map['result_amount'],
                    ];

                    #initialize the values
                    $current_total_bet_amount = $current_map['bet_amount'];
                    $current_result_amount = $current_map['result_amount'];
                    $current_valid_bet_amount  = $current_map['bet_amount'];
                }

                $list_of_valid_bet_amount[$opposite_bet_name]['result_amount'] = $current_result_amount;
                $list_of_valid_bet_amount[$opposite_bet_name]['valid_bet_amount'] = $current_valid_bet_amount;
                $list_of_valid_bet_amount[$opposite_bet_name]['total_bet_amount'] = $current_total_bet_amount;
            }


        }

        #initialize value
        $result_amount = 0;
        $total_bet_amount = 0;
        #finalize the result for valid bet amount
        if( ! empty($list_of_valid_bet_amount)){
            foreach ($list_of_valid_bet_amount as $key => $value) {
                $valid_bet_amount+=$value['valid_bet_amount'];
                $result_amount+=$value['result_amount'];
                $total_bet_amount+=$value['total_bet_amount'];
            }
        }

        #calculate data per non opposite bets
        if( ! empty($bet_type_map[$current_game_type]['not_opposite_bet'])){
            foreach ($bet_type_map[$current_game_type]['not_opposite_bet'] as $key => $value) {
                $valid_bet_amount+=$value['bet_amount'];
                $result_amount+=$value['result_amount'];
                $total_bet_amount+=$value['bet_amount'];
            }
        }
        #end

        return array($result_amount, $valid_bet_amount, $total_bet_amount);

    }

	private function getGameDescriptionInfo($row, $gameDescIdMap) {
        $unknownGame = $this->getUnknownGame();

		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}

		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
		}else{
            $game_type_id = $unknownGame->game_type_id;
        }

		$externalGameId = $row->gameshortcode;
		if (empty($game_description_id)) {
			//search game_description_id by code
			if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
				$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
				$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
				if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
					return array(null, null);
				}
			}
		}

		$extra = array('game_code' => $row->gameshortcode);

		return $this->processUnknownGame($game_description_id, $game_type_id, $row->product_id, null, $externalGameId, $extra);
	}

	function getApiAdminName() {
		return array("success" => true);
	}

	function getApiKioskName() {
		return array("success" => true);
	}

	function createVarMap($params) {
		return array("success" => true);
	}

	public function processGameList($game) {

		$game_image = substr($game['game_code'], 3) . '.' . $this->getGameImageExtension();
		$game_image = $this->checkIfGameImageExist($game_image) ? $game_image : $this->getDefaultGameImage();

		return array(
			'c' => $game['game_code'], # C - GAME CODE
			'n' => lang($game['game_name']), # N - GAME NAME
			'i' => $game_image, # I - GAME IMAGE
			'g' => $game['game_type_id'], # G - GAME TYPE ID
			'r' => $game['offline_enabled'] == 1, # R - TRIAL
		);
	}

}

/*end of file*/