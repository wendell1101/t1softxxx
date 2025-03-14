<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * *Getting platform code
 * *Empty Bet Limit
 * *Generate Soap method
 * *Generate URL
 * *Extract XML Content
 * *Checks if the server is up
 * *Create Player
 * *Check if Player exist
 * *Check player information
 * *Block/Unblock Player
 * *Deposit to game
 * *Withdraw from game
 * *Check Player Balance
 * *Confirm Transfer
 * *Checks Forward Game
 * *Synchronize Original Game Logs
 * *Synchronize and Merge GAMESOS To Game Logs
 * *Get Game Description Information
 * *Login/Logout
 * *Update Player Info
 * *Check Player Daily Balance
 * *Check Login Status
 * *Check Total Betting Amount
 * *Check Transaction
 * *Get Gameplay Game Log Statistics
 * Behavior not implemented:
 * *Check Game Records
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 * @see Redirect redirect to game page
 *
 * @category Game API
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_gamesos extends Abstract_game_api {

	private $api_url;
	private $gamesos_ewallet_service_url;
	private $gamesos_game_launcher_url;
	private $gamesos_merchant_id;
	private $gamesos_merchant_pw;
	private $gamesos_mobilegame_launcher;



	private $currentProcess;
	private $settingsStartTrxId;
	private $isTestCustomer;
	private $Language;
	private $CurrencyCode;

	#
	private $gamesos_mobile_login_url;
	private $gamesos_mobile_cashier_url;
	private $gamesos_mobile_back_url;
	private $gamesos_mobile_error_url;

	const URI_MAP = array(
		self::API_createPlayer => 'RegisterCustomer',
		self::API_depositToGame => 'RequestCredit',
		self::API_withdrawFromGame => 'RequestDebit',
		self::API_queryPlayerBalance => 'GetBalance',
		self::API_isPlayerExist => 'IsRegisteredCustomer',
		self::API_syncGameRecords => 'GetTransactionList',
		self::API_checkFundTransfer => 'Confirm',
		self::API_queryForwardGame => 'GetExternalTicket'
	);

	public function __construct() {

		parent::__construct();

		// $this->gamesos_ewallet_service_url = 'http://staging-asia-2.ctxm.com/xbase-service/services/EWalletService?wsdl';
		// $this->gamesos_game_service_url ="http://staging-asia-2.ctxm.com/xbase-service/services/GameService?wsdl";
		// $this->gamesos_game_launcher_url = 'http://staging-asia-2.ctxm.com/whl_lobby/game.do';
		// $this->gamesos_merchant_id ='WEBET88';
	    // $this->gamesos_merchant_pw = 'WEBET88Pass' ;

		$this->api_url = $this->getSystemInfo('url');
		$this->gamesos_merchant_id = $this->getSystemInfo('key');
	    $this->gamesos_merchant_pw = $this->getSystemInfo('secret') ;
	    $this->gamesos_ewallet_service_url = $this->getSystemInfo('gamesos_ewallet_service');
		$this->gamesos_game_service_url    = $this->getSystemInfo('gamesos_game_service');
		$this->gamesos_game_launcher_url   = $this->getSystemInfo('gamesos_game_launcher');
		$this->gamesos_mobilegame_launcher = $this->getSystemInfo('gamesos_mobilegame_launcher');
		$this->settingsStartTrxId = $this->getSystemInfo('start_transaction_id');
		$this->isTestCustomer = $this->getSystemInfo('isTestCustomer');
		$this->Language = $this->getSystemInfo('Language');
		$this->CurrencyCode = $this->getSystemInfo('CurrencyCode');


		#MOBILE GAMES NOT MANDATORY URL  PARAMS
		$this->gamesos_mobile_login_url = $this->getSystemInfo('gamesos_mobile_login_url');
		$this->gamesos_mobile_cashier_url = $this->getSystemInfo('gamesos_mobile_cashier_url');
		$this->gamesos_mobile_back_url = $this->getSystemInfo('gamesos_mobile_back_url');
		$this->gamesos_mobile_error_url = $this->getSystemInfo('gamesos_mobile_error_url');

		if($this->api_url != null){
			$this->isServerUp($this->api_url);
		}


	}

	/**
	 * overview : get platform code
	 *
	 * @return int
	 */
	public function getPlatformCode() {
		return GAMESOS_API;
	}

	/**
	 * overview : get call type
	 *
	 * @param $apiName
	 * @param $params
	 * @return int
	 */
	protected function getCallType($apiName, $params) {
		//overwrite in sub-class
		return self::CALL_TYPE_SOAP;
	}

	/**
	 * overview : generate soap method
	 *
	 * @param $apiName
	 * @param $params
	 * @return array
	 */
    protected function generateSoapMethod($apiName, $params) {

		switch ($apiName) {
		case self::API_createPlayer:
			return array('RegisterCustomer', $params);
			break;

		case self::API_depositToGame:
			return array('RequestCredit', $params);
			break;

		case self::API_withdrawFromGame:
			return array('RequestDebit', $params);
			break;

		case self::API_queryPlayerBalance:
			return array('GetBalance', $params);
			break;

	    case self::API_syncGameRecords:

	    return array('GetTransactionList', $params);
			break;

		case self::API_checkFundTransfer :
			return array('Confirm', $params);
			break;

		case self::API_queryForwardGame :
			return array('GetExternalTicket', $params);
			break;


	}

		return parent::generateSoapMethod($apiName, $params);
	}

	/**
	 * overview : generate url
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {

		if ($apiName == self::API_syncGameRecords) {

			return $this->gamesos_game_service_url;

		}else{

			return $this->gamesos_ewallet_service_url;

		}
	}

	/**
	 * overview : extract xml content
	 *
	 * @param $resultText
	 * @param $responseString
	 * @return mixed
	 */
	private function extractXmlContent($resultText, $responseString){

		$xml= new DOMDocument();
		$xml->preserveWhiteSpace=false;
		$xml->loadXML($resultText);
		$xmlArray = $this->xml2array($xml);
		$xmlContent = $xmlArray['soapenv:Envelope']['soapenv:Body'][$responseString] ;

		return  $xmlContent;
	}

	/**
	 * overview : convert xml data to array
	 *
	 * @param $n
	 * @return array
	 */
	private function xml2array($n){
	    $return=array();
	    foreach($n->childNodes as $nc)
	    ($nc->hasChildNodes())
	    ?($n->firstChild->nodeName== $n->lastChild->nodeName&&$n->childNodes->length>1)
	    ?$return[$nc->nodeName][]=$this->xml2array($item)
	    :$return[$nc->nodeName]=$this->xml2array($nc)
	    :$return=$nc->nodeValue;
	    return $return;
	}

	/**
	 * overview : check if server is up
	 *
	 * @param null $url
	 * @return bool
	 */
	private function isServerUp($url=NULL)
		{
			if($url == NULL) return false;
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$data = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if($httpcode>=200 && $httpcode<300){
				return true;
			} else {
				;
				$this->CI->utils->debug_log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<GAMESOS SOAP SERVER IS DOWN:'. $this->api_url.' PLEASE INFORM GAMESOS ADMINISTRATOR>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>');
			}
	}

	/**
	 * overview : result after process
	 *
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultText
	 * @param $statusCode
	 * @param null $statusText
	 * @param null $extra
	 * @param null $resultObj
	 * @return array
	 */
	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

		function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

		$success = !empty($resultArr) && $resultArr['ErrorCode'] == '0';

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GAMESOS got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}


	/**
	 * overview : create player
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

		$this->CI->load->model(array('player'));
	     $playerInfo = $this->CI->player->getPlayerById($playerId);
	    // print_r($playerInfo);


	    $countryIso2 = unserialize(COUNTRY_ISO2);

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		#OPTIONAL PARAMETERS
		// $CustomerInfoType = array(
		// 	'FirstName' => 'testfn',
		// 	'LastName' =>'testln',
		// 	'MiddleName' =>'testmn',
		// 	'DateOfBirth '=> '1988-04-01 00:00:00',
		// 	'Sex' => 'MALE',
		// 	'Salutation' => 'Mr',
		// 	'Nickname' => 'Nick',
		// 	'AboutMe' => 'Master Gamer'
		// 	);


		// if (array_key_exists('address', $playerInfo)  && isset($playerInfo['address']) && $playerInfo['address'] !=null ) {
		// 	$address =  $playerInfo['address'];
		// }else{
		// 	$address =  'No Address Provided ';
		// }


		// if (array_key_exists('postal_code', $playerInfo)  && isset($playerInfo['postal_code']) && $playerInfo['postal_code'] !=null ) {
		// 	$postalCode =  $playerInfo['postal_code'];
		// }else{
		// 	$postalCode =  'No Postal Code Provided ';
		// }

		// if (array_key_exists('city', $playerInfo)  &&  isset($playerInfo['city']) && $playerInfo['city'] != null ) {
		// 	$city =  $playerInfo['city'];
		// }else{
		// 	$city =  'No City Provided ';
		// }

		// if (array_key_exists('state', $playerInfo)  && isset($playerInfo['state']) && $playerInfo['state'] != null) {
		// 	$state =  $playerInfo['state'];
		// }else{
		// 	$state =  'No State Provided ';
		// }

		// if ( isset($countryIso2[$playerInfo['country']])  &&  $countryIso2[$playerInfo['country']] != null ) {
		// 	$country =  $playerInfo['country'];
		// }else{
		// 	$country  =  'NO';
		// }

		// if (array_key_exists('secretQuestion', $playerInfo)  && isset($playerInfo['secretQuestion']) && $playerInfo['secretQuestion'] != null) {
		// 	$secretQuestion =  $playerInfo['secretQuestion'];
		// }else{
		// 	$secretQuestion  = 'No  Provided Secret Question';
		// }

		// if (array_key_exists('secretAnswer', $playerInfo)  && isset($playerInfo['secretAnswer']) && $playerInfo['secretAnswer'] != null) {
		// 	$secretAnswer=  $playerInfo['secretAnswer'];
		// }else{
		// 	$secretAnswer  = 'No  Provided Secret answer';
		// }

		// if (array_key_exists('currency', $playerInfo)  && isset($playerInfo['currency']) && $playerInfo['currency'] != null) {
		// 	$currency =  $playerInfo['currency'];
		// }else{
		// 	$currency  = 'CNY';
		// }

		// if (array_key_exists('language', $playerInfo)  && isset($playerInfo['language']) && $playerInfo['language'] != null) {

		// 	$lang =  $playerInfo['language'];

		// 	if($lang =='English'){
		// 		$language =	'EN';
		// 	}elseif($lang =='Chinese'){
		// 		$language =	'ZH';
		// 	}else{
		// 		$language =	'EN';
		// 	}

		// }else{
		// 	$language  = 'EN';
		// }

		// if (array_key_exists('phone', $playerInfo)  && isset($playerInfo['phone']) && $playerInfo['phone'] != null) {
		// 	$phone =  $playerInfo['phone'];
		// }else{
		// 	$phone = 'No Phone Provided';
		// }

		// if (array_key_exists('phone2', $playerInfo)  && isset($playerInfo['phone2']) && $playerInfo['phone2'] != null) {
		// 	$phone2 =  $playerInfo['phone2'];
		// }else{
		// 	$phone2 = 'No Phone 2 Provided';
		// }

		// if (array_key_exists('registrationWebsite', $playerInfo)  && isset($playerInfo['registrationWebsite']) && $playerInfo['registrationWebsite'] != null) {
		// 	$registrationWebsite =  $playerInfo['registrationWebsite'];
		// }else{
		// 	$registrationWebsite = $this->utils->getHttpReferer();
		// }


		#FILL SOME FAKE DATA  PER JAMES INSTRUCTIONS

		$AddressType = array(
		'AddressLine' => 'No Address Provided',
		'City' => 'No city Provided',
		'PostalCode' => 'No  Postal Code Provided' ,
		'State' => 'No State Provided ',
		'Country' => 'CN'
		);


		$AccountInfo =array(
			'AccountId' =>  $playerInfo['username'],
			'Login' =>$playerInfo['username'],
			'Email' =>'No email provided',
			'SecretQuestion' => 'No secret Question Provided' ,
			'SecretAnswer' => 'No secret Answer',
			'CurrencyCode' => $this->CurrencyCode,
			'Language' => $this->Language ,
			'TestCustomer' => $this->isTestCustomer,
			'Phone '=>  'No Phone Provided',
			'Phone2' =>  'No Phone2 Provided',
			'CustomerInfo' => '',
			'Address' =>  $AddressType,
			);
		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'ReferralInfo' => '',
			'ReferrerHost' =>  $this->utils->getHttpReferer(),
			'ReferrerQuery' => '',
			'RakeBack' => 'true',//$CustomerInfoType,
			'IPAddress' =>  $playerInfo['registrationIP'],
			'AccountInfo' => $AccountInfo,
			);

		$this->CI->utils->debug_log($params);
   		return $this->callApi(self::API_createPlayer,$params,$context);
	}

	/**
	 * overview : process result for creating player
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForCreatePlayer($params) {

		$resultArr =$this->extractXmlContent(@$params['resultText'], 'RegisterCustomerResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$result = array();
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName)) {

			$success = true;
			$result['result'] = $resultArr;

		} else {
			$result['exists'] = true;
			$result['errorcode'] = @$resultArr['ErrorCode'];

		}

		return array($success, $result);
	}

	/**
	 * overview : check if player exist
	 *
	 * @param $playerName
	 * @return array
	 */
	public function isPlayerExist($playerName) {

		$this->CI->load->model(array('player_model','game_provider_auth'));
		$result = array();
		$result['success']= true;
		$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

		$platformId = $this->getPlatformCode();

		if ($this->CI->game_provider_auth->isRegisterd($playerId, $platformId)) {
			$result['exists'] = true;
		} else {
			$result['exists'] = false;
		}

		return $result;

	}

	/**
	 * overview : query player information
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerInfo($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	/**
	 * overview : change player password
	 *
	 * @param $playerName
	 * @param $oldPassword
	 * @param $newPassword
	 * @return array
	 */
	public function changePassword($playerName, $oldPassword, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	/**
	 * overview : block player
	 *
	 * @param $playerName
	 * @return array
	 */
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	/**
	 * overview : unblock player
	 *
	 * @param $playerName
	 * @return array
	 */
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	/**
	 * overview : deposit player to game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

		$this->CI->load->model(array('player'));
		$playerGameName = $this->getGameUsernameByPlayerUsername($playerName);
		$player = $this->CI->player->getPlayerByUsername($playerName);
		$playerId = $player->playerId;


		$playerInfo = $this->CI->player->getPlayerById($playerId);
		$trx_id = rand(1,99).time();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'trx_id' => $trx_id ,
		);

		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'Ticket' => $playerName,
			'CurrencyCode' => $playerInfo['currency'],
			'Amount' => $amount,
			'MerchantTrxId' => $trx_id ,
			'IPAddress' =>  $this->CI->utils->getIP(),
			);

      $this->CI->utils->debug_log('DEPOSIT PARAM',$params);
		return $this->callApi(self::API_depositToGame,$params,$context);

	}

	/**
	 * overview : processing result for deposit game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForDepositToGame($params) {

       $MerchantTrxId= $this->getVariableFromContext($params, 'trx_id');
       $playerName = $this->getVariableFromContext($params, 'playerName');
       $amount = $this->getVariableFromContext($params, 'amount');
       $result = $this->confirmTransfer($MerchantTrxId,$playerName,$amount);

		 // if ($result['success']) {
		// 	return $result["success"];
		// }

       return $result;

	}

	/**
	 * overview : withdraw from game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$this->CI->load->model(array('player'));
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$player = $this->CI->player->getPlayerByUsername($playerName);
		$playerId = $player->playerId;


		$playerInfo = $this->CI->player->getPlayerById($playerId);

		$trx_id = rand(1,99).time();
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'amount' => $amount,
			'trx_id' => $trx_id ,
		);


		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'Ticket' => $playerName,
			'CurrencyCode' => $playerInfo['currency'],
			'Amount' => $amount,
			'MerchantTrxId' => $trx_id ,
			'IPAddress' =>  $this->CI->utils->getIP(),
			);


		return $this->callApi(self::API_withdrawFromGame,$params,$context);
	}

	/**
	 * overview : process result for withdraw to game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForWithdrawToGame($params) {
	   $MerchantTrxId= $this->getVariableFromContext($params, 'trx_id');
       $playerName = $this->getVariableFromContext($params, 'playerName');
       $amount = $this->getVariableFromContext($params, 'amount');

      $result = $this->confirmTransfer($MerchantTrxId,$playerName,$amount);

        // if ($result['success']) {
		// 	return $result["success"];
		// }

      return $result;

	}

	/**
	 * overview : query player balance
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerBalance($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);


		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'Ticket' =>  $playerName
			);

		return $this->callApi(self::API_queryPlayerBalance ,$params,$context);

	}

	/**
	 * overview : processing result for queryPlayerBalance
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryPlayerBalance($params) {

		$resultArr =$this->extractXmlContent(@$params['resultText'], 'GetBalanceResponse');
		$this->utils->debug_log($resultArr);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = false;
		$result = array();

		if ($this->processResultBoolean($responseResultId, $resultArr, $playerName) && isset($resultArr['Balance'])) {

			$success = true;
			$result['balance'] = @floatval($resultArr['Balance']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$success = false;
			if (@$resultArr['ErrorCode'] == '0') {
				$result['exists'] = false;
			} else {
				$result['exists'] = true;
			}
		}

		return array($success, $result);

	}

	/**
	 * overview : confirmation of transfer
	 *
	 * @param $MerchantTrxId
	 * @param $playerName
	 * @param $amount
	 * @return array
	 */
	private function confirmTransfer($MerchantTrxId,$playerName,$amount){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForConfirmTransfer',
			'trx_id' => $MerchantTrxId,
			'playerName' => $playerName,
			'amount' => $amount,
		);


		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'MerchantTrxId' => $MerchantTrxId,
			'IPAddress' =>  $this->CI->utils->getIP(),
			);

		 $this->CI->utils->debug_log('GAMEOS CONFIRM TRANSFER', $params);

		return $this->callApi(self::API_checkFundTransfer ,$params,$context);
	}

	/**
	 * overview : process result for confirm transfer
	 *
	 * @param $params
	 * @return array
	 */
	public function  processResultForConfirmTransfer($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr =$this->extractXmlContent(@$params['resultText'], 'ConfirmResponse');
		$this->CI->utils->debug_log('GAMESOS TRANSFER',$amount,$resultArr);

		// $result = array();
		$result = array('response_result_id' => $responseResultId);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		if ($success) {

			//for sub wallet
			$afterBalance = @$resultArr['Balance'];
			$result["external_transaction_id"] = @$resultArr['WHLSTransactionId'];
			$result["currentplayerbalance"] = $afterBalance;
			$result["userNotFound"] = false;
			//$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
					$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}

	return array($success, $result);
	}


	/**
	 * overview : query forward game
	 *
	 * @param $playerName
	 * @param $params
	 * @return array
	 */
    public function queryForwardGame($playerName, $params) {

		// http://staging-asia-2.ctxm.com/whl_lobby/game.do?game_code=xc_doublebonusslots&merch_id=WEBET88&playmode=fun&language=EN&ticket=

		//for mobile: http://staging-asia-2.ctxm.com/mobilegames/launcher.htm?code=xc_mobiledoublebonusslots&mode=fun&ticket=&language=EN&merch_id=MMM&cashierUrl=bank.com&loginUrl=mmm.com&errorUrl=mmm.com/error

    	/*
			Mandatory in mobile
			- code
			- merch_id
			- mode -not mandatory but needed , fun or real
			- ticket -not mandatory but needed
			- language -

			Not Mandatory
			screenmode
			loginUrl
			cashierUrl
			backUrl
			errorUrl


    	*/


		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
			'extra' => $params
		);


			$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'Ticket' => $playerName,
			'IPAddress' =>  $this->CI->utils->getIP(),
			);



		return $this->callApi(self::API_queryForwardGame ,$params,$context);

	}

	/**
	 * overview : process result for query forward game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryForwardGame($params){


		$resultArr =$this->extractXmlContent(@$params['resultText'], 'GetExternalTicketResponse');
		$extra = $this->getVariableFromContext($params, 'extra');
        $extra['ticket'] = @$resultArr['ExternalTicket'];
        $extra['merch_id'] =$this->gamesos_merchant_id;

        #NOT MANDATORY
        if($this->gamesos_mobile_login_url != null &&  !empty($this->gamesos_mobile_login_url)){
        	 $extra['loginUrl'] = $this->gamesos_mobile_login_url;
        }
        if($this->gamesos_mobile_cashier_url != null &&  !empty($this->gamesos_mobile_cashier_url)){
        	 $extra['cashierUrl'] = $this->gamesos_mobile_cashier_url;
        }
        if($this->gamesos_mobile_back_url != null &&  !empty($this->gamesos_mobile_back_url)){
        	 $extra['backUrl'] = $this->gamesos_mobile_back_url;
        }
        if($this->gamesos_mobile_error_url != null &&  !empty($this->gamesos_mobile_error_url)){
        	 $extra['errorUrl'] = $this->gamesos_mobile_error_url;
        }

        //var_dump($extra);

        $result = array();
        $success =true;
	    $params_string = http_build_query($extra);


	    if(isset($extra['desktop_client']) && $extra['desktop_client'] != null && !empty($extra['desktop_client'])){

	    	$result['ticket'] = $extra['ticket'];

	    }else{

           if($extra['mobile'] == 'true'){
	    	    $result['url'] = $this->gamesos_mobilegame_launcher . "?" . $params_string;
		    }else{
		    	$result['url'] = $this->gamesos_game_launcher_url . "?" . $params_string;
		    }


	    }




		return array($success,$result);


	}




	private $currentBatchGameLogsCount = 0;
	private $current100GameLogsTransactionId;
	private $currentResponseStatus = true;
	private $errorCount = 0;
	private $loopCount = 0;
	const MAXIMUM_ROW_COUNT = 100;

	/**
	 * overview : sync original game logs
	 *
	 * @param $token
	 * @return array
	 */
	public function syncOriginalGameLogs($token) {



		$this->CI->load->model(array('external_system'));


		do {

			$this->loopCount++;

		    try
		    {


		       	$last_sync_id = $this->CI->external_system->getLastSyncId($this->getPlatformCode());

		     	#Get the last 100 loop transaction id
		    	if($this->current100GameLogsTransactionId){

		    		$StartTrxId = $this->current100GameLogsTransactionId;

		    	#if not get the last  sync id  ex:41994932
		    	}elseif($last_sync_id){

		    	    $StartTrxId =  $last_sync_id;

		    	#else get the start settings
		    	}else{

		    		$StartTrxId = $this->settingsStartTrxId;

		    	}


		    	$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				);


				$params = array(
					'MerchantCode' => $this->gamesos_merchant_id,
					'MerchantPassword' => $this->gamesos_merchant_pw,
				    'StartTrxId' =>$StartTrxId
				);

				$this->currentProcess = self::URI_MAP[self::API_syncGameRecords];
		        $result =  $this->callApi(self::API_syncGameRecords, $params, $context);
		        $this->currentResponseStatus = $result['success'];
		        # rest a little bit
		        sleep(1);
		        if(!$this->currentResponseStatus){
		        	$this->errorCount++;
		        	break;
		        }



		 	} catch (Exception $e) {

		      	$this->CI->utils->debug_log('GAMESOS API:syncOriginalGameLogs @TransactionId:  '.@$StartTrxId);
		        break;
		  	}



		} while(($this->currentBatchGameLogsCount == 0 || $this->currentBatchGameLogsCount == self::MAXIMUM_ROW_COUNT) && $this->currentResponseStatus);

		$this->CI->utils->debug_log('GAMESOS API:syncOriginalGameLogs ERRORCOUNT:  '.@$this->errorCount.' LOOPCOUNT: '.@$this->loopCount );
		return $result;

	}

	/**
	 * overview : process result for sync game records
	 *
	 * @param $params
	 * @return array
	 */
	public	function processResultForSyncGameRecords($params) {

 		if(isset($params["resultObj"])){
			$resultArr =(array)$params["resultObj"];
	    }

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array();

		if(isset($resultArr['Transaction'])){
			$this->currentBatchGameLogsCount = count(@$resultArr['Transaction']);
			$gameRecords = @$resultArr['Transaction'];
			$gameRecords = $gameRecords === array_values($gameRecords) ? $gameRecords : array($gameRecords);
		}


		if(!$success){
			$result['error'] = @$resultArr['ErrorCode'];
		}else{
			$this->CI->load->model(array('gamesos_game_logs', 'external_system'));
			if ($gameRecords) {
				list($availableRows, $maxRowId) = $this->CI->gamesos_game_logs->getAvailableRows($gameRecords);
				if ($availableRows) {
					foreach ($availableRows as $record) {
                        $TransactionDateTime = @$record->TransactionDateTime;
                        $convertedTransDateTime = $this->gameTimeToServerTime($TransactionDateTime);
                        $adjustedTransDateTime = $this->adjustTime($convertedTransDateTime);

						$this->CI->utils->debug_log('GAMESOS SYNC ORIGINAL:  ORIGINAL TIME:  '.@$TransactionDateTime.'  CONVERTED TIME +8HRS: '. $adjustedTransDateTime. ' TRANSACTION ID'. @$record->TransactionId. ' TIME STORED: ' . $this->utils->getNowForMysql() );

						$gamesosGameData = array(
							'external_uniqueid' => @$record->TransactionId,
							'response_result_id' => $responseResultId,
							'uniqueid' => @$record->TransactionId,
							'gameshortcode' => @$record->GameCode,
							'transaction_id' => @$record->TransactionId,
							'paired_transaction_id' => @$record->PairedTransactionID,
							'transaction_date_time' => $adjustedTransDateTime,
							'type' => @$record->Type,
							'status' => @$record->Status,
							'end_balance' => @$record->EndBalance,
							'amount' => @$record->Amount,
							'application_code' => @$record->ApplicationCode,
							'application_type' => @$record->ApplicationType,
							'application_name' => @$record->ApplicationName,
							'category_code' => @$record->CategoryCode,
							'category_type' => @$record->CategoryType,
							'category_name' => @$record->CategoryName,
							'game_id' => @$record->GameId,
							'game_code' => @$record->GameCode,
							'game_name_zh' => @$record->GameName[0]->Value,
							'game_name_en' => @$record->GameName[1]->Value,
							'game_names' => json_encode(@$record->GameName),
							'event_code' => @$record->EventCode,
							'account_id' => @$record->AccountId,
							'attribute' => json_encode(@$record->Attribute),
						);
						$this->CI->gamesos_game_logs->insertGamesosGameLogs($gamesosGameData);
					}

				    #Maximum row can GAMEOS can return
				    if($this->currentBatchGameLogsCount == self::MAXIMUM_ROW_COUNT){
				    	$this->current100GameLogsTransactionId = $gameRecords[self::MAXIMUM_ROW_COUNT-1]->TransactionId;
				    }
				}

			    if ($maxRowId) {
					$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $maxRowId);
					$lastRowId = $maxRowId;
				}
			}
		}

    	return array($success, $result);
   	}



	// protected function makeSoapOptions($options) {

	// 	if($this->currentProcess == self::URI_MAP[self::API_syncGameRecords]){
	// 		//return array('trace'=> 1,'exceptions'  => 1 );
	// 		return array();

	// 	}
	// 	return $options;

	// }

	/**
	 * overview : sync merge to game logs
	 *
	 * @param $token
	 * @return array
	 */
	public function syncMergeToGameLogs($token) {

	   $this->CI->load->model(array('game_logs', 'player_model', 'gamesos_game_logs', 'game_description_model'));

       $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
	   $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
 	   $startDate = $startDate->format('Y-m-d H:i:s');
 	   $endDate = $endDate->format('Y-m-d H:i:s');

       $this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);
       $rlt = array('success' => true);
       $result = $this->CI->gamesos_game_logs->getGamesosGameLogStatistics($startDate, $endDate);
        //print_r($result);
        // exit();

 	   $cnt = 0;

        if ($result) {
            	$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $gamesos_data) {

				if ($player_id = $this->getPlayerIdInGameProviderAuth($gamesos_data->gameUsername)) {
					$username = strtolower($gamesos_data->gameUsername);
  					$player_id = $this->getPlayerIdInGameProviderAuth($username);
 					$cnt++;
					$player = $this->CI->player_model->getPlayerById($player_id);



                   // $abs_bet_amount = abs($gamesos_data->bet_amount);

 	   				$bet_amount = $this->gameAmountToDB($gamesos_data->bet_amount);


 					//$result_amount = $this->gameAmountToDB($gamesos_data->result_amount);


 					$result_amount = abs($gamesos_data->result_amount) - $gamesos_data->bet_amount;


 					$after_balance 	= $this->gameAmountToDB($gamesos_data->end_balance);
 	   				$has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($gamesos_data, $gameDescIdMap);

$this->CI->utils->debug_log('GAMESOS SYNC MERGE:   TRANSACTION ID'. $gamesos_data->external_uniqueid. 'TIME STORED: ' . $this->utils->getNowForMysql() );

					$this->syncGameLogs(

						$game_type_id,
 						$game_description_id,
						$gamesos_data->gameshortcode,
						$gamesos_data->game_type_id,
						$gamesos_data->game_name_en,
        				$player_id,
 						$gamesos_data->gameUsername,
 						$bet_amount,
 						$result_amount,
 						null, # win_amount
 						null, # loss_amount
 						$after_balance, # after_balance
 						$has_both_side,
 						$gamesos_data->external_uniqueid,
 						$gamesos_data->transaction_date_time,
 						$gamesos_data->transaction_date_time,
 						$gamesos_data->response_result_id

					);

				}

			}

 		}

 		$this->CI->utils->debug_log('GAMESOS syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
 	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
 	public function getGameTimeToServerTime() {
        return '+8 hours +30 minutes';
	}

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	public function getServerTimeToGameTime() {
     	return '-8.5 hours -30 minutes';
	}

	/**
	 * overview : adjust time
	 *
	 * @param $transactionTime
	 * @return string
	 */
	private function  adjustTime($transactionTime){

		//echo $transactionTime = "2016-08-27 22:52:18";
		$serverTime = $this->utils->getNowForMysql();

		$transactionTimeStamp = strtotime(date('Y-m-d H:i', strtotime($transactionTime)));
		$serverTimeStamp  = strtotime(date('Y-m-d H:i', strtotime($serverTime)));

		$interval  =  abs($serverTimeStamp  - $transactionTimeStamp);
		$minutes   = round($interval / 60) ;


		if($serverTimeStamp  > $transactionTimeStamp){
			$d = new DateTime($transactionTime);
			$d->modify('+'.$minutes.' minutes');
		}else{

			$d = new DateTime($transactionTime);
			$d->modify('-'.$minutes.' minutes');
		}

		return $d->format('Y-m-d H:i:s');


	}

	/**
	 * overview : get game description information
	 *
	 * @param $row
	 * @param $gameDescIdMap
	 * @return array
	 */
 	private function getGameDescriptionInfo($row, $gameDescIdMap) {

		$game_description_id = null;
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
		}

		$game_type_id = null;
		if (isset($row->game_type_id)) {
			$game_type_id = $row->game_type_id;
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

		return $this->processUnknownGame($game_description_id, $game_type_id,  $row->game_name_zh, $row->game_type_str, $externalGameId, $extra);
	}

	/**
	 * overview : login player
	 *
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
   public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	/**
	 * overview : process result for login player
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForLogin($params) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	/**
	 * overview : logout player
	 * Note: Gamesos has no logout functions,
	 * This is only remedy by getting another external ticket
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
	public function logout($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);


		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName
			);


		$params = array(
			'MerchantCode' => $this->gamesos_merchant_id,
			'MerchantPassword' => $this->gamesos_merchant_pw,
			'Ticket' => $playerName,
			'IPAddress' =>  $this->CI->utils->getIP(),
			);

		return $this->callApi(self::API_queryForwardGame ,$params,$context);

	}

	/**
	 * overview : process result for logout player
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForLogout($params) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}

	/**
	 * overview : update player information
	 *
	 * @param $playerName
	 * @param $infos
	 * @return array
	 */
	public function updatePlayerInfo($playerName, $infos) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}


	/**
	 * overview : query player daily balance
	 *
	 * @param $playerName
	 * @param $playerId
	 * @param null $dateFrom
	 * @param null $dateTo
	 * @return array
	 */
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return array("success" => true);

	}

	/**
	 * overview : query game records
	 *
	 * @param $dateFrom
	 * @param $dateTo
	 * @param null $playerName
	 * @return array
	 */
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : check login status
	 *
	 * @param $playerName
	 * @return array
	 */
	public function checkLoginStatus($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true, "loginStatus" => true);
	}

	/**
	 * overview : total betting amount
	 *
	 * @param $playerName
	 * @param $dateFrom
	 * @param $dateTo
	 * @return array
	 */
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {

		return array("success" => true);

	}

	/**
	 * overview : query transaction
	 *
	 * @param $transactionId
	 * @param $extra
	 * @return array
	 */
	public function queryTransaction($transactionId, $extra) {

		return array("success" => true);

	}

	/**
	 * overview : process result for query transaction
	 *
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
	public function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultXml) {
		return array("success" => true);
	}


	/**
	 * overview : game amount to db
	 * @param $amount
	 * @return float
	 */
	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	/**
	 * overview : game logs statistics
	 *
	 * @param $dateTimeFrom
	 * @param $dateTimeTo
	 * @return mixed
	 */
	private function getGameplayGameLogStatistics($dateTimeFrom, $dateTimeTo) {
		$this->CI->load->model('gameplay_game_logs');
		return $this->CI->gameplay_game_logs->getGameplayGameLogStatistics($dateTimeFrom, $dateTimeTo);
	}

	/**
	 * overview : process the game list
	 *
	 * @param $game
	 * @return array
	 */
	public function processGameList($game) {

		$game_image = substr($game['game_code'], 3) . '.' . $this->getGameImageExtension();
		$game_image = $this->checkIfGameImageExist($game_image) ? $game_image : $this->getDefaultGameImage();

		return array(
			'c' => $game['game_code'], # C - GAME CODE
			'n' => $game['game_name'], # N - GAME NAME
			'i' => 'xc_'.$game_image, # I - GAME IMAGE
			'g' => $game['game_type_id'], # G - GAME TYPE ID
			'r' => $game['offline_enabled'] == 1, # R - TRIAL
		);
	}

}

/*end of file*/
