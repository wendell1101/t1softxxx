<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: Operator API Integration Guide
	* Document Number: SGS-GO-API v.1.2.5


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_mwg extends Abstract_game_api {

	const TRANSFER_IN = 0; #deposit
	const TRANSFER_OUT = 1; #withdraw
    const API_getMWGAPIDomain = "getMWGAPIDomain";
	const API_prepareTransfer = "prepareTransfer";
	const API_getGameList = "getGameList";
	const POST = "POST";
	const GET = "GET";
	const API_directMWGGame = "directMWGGame";
	const START_PAGE = 1;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->language = $this->getSystemInfo('language');
		$this->site_id = $this->getSystemInfo('site_id');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+5 minutes');
        $this->MW_public_key = $this->getSystemInfo('mwPublicKey');
		$this->pi_key =  openssl_pkey_get_private($this->getSystemInfo('ecPrivateKey'));
		$this->pu_key =  openssl_pkey_get_public($this->getSystemInfo('ecPublicKey'));
		$this->method = self::GET;
		/* 
		Get Type
		0 does not return currency and only
		query game information in CNY
		currency.
		1 returns game information and
		shows values in MW currency.
		2 returns game information and
		shows values in EC currency
		*/
		$this->getType = $this->getSystemInfo('getType',1);

		$this->URI_MAP = array(
			self::API_createPlayer => '/api/oauth',
			self::API_queryPlayerBalance => '/api/userInfo',
			self::API_isPlayerExist => '/api/userInfo',
			self::API_prepareTransfer => '/api/transferPrepare',
	        self::API_depositToGame => '/api/transferPay',
	        self::API_withdrawFromGame => '/api/transferPay',
	        self::API_queryForwardGame => '/api/oauth',
	        self::API_getGameList => '/api/gameInfo',
	        self::API_directMWGGame => '',
			self::API_syncGameRecords => '/api/siteUsergamelog',
			self::API_getMWGAPIDomain => '/api/domain',
		);

		$this->encrpytion   = 'aes-128-ecb';
	}

	public function getPlatformCode() {
		return MWG_API;
	}

	public function generateUrl($apiName, $params) {

		$function = $params['function'];
		unset($params['function']);
		if($function == 'domain'){
			$this->api_url = $this->getSystemInfo('second_url');
		}


		$apiUri = $this->URI_MAP[$apiName];
		if($apiName == self::API_syncGameRecords){
			$this->api_url = str_replace("as-lobby", "as-service", $this->api_url);
		}
		$url = $this->api_url.$apiUri;

		// if($this->URI_MAP[$apiName] == $this->URI_MAP['directMWGGame']){
		// 	return $url.'?'.http_build_query($params);
		// }

		$lang = isset($params['lang']) ? $params['lang'] : "";
		$signContent = $this->getSignContentString($params);
		openssl_sign($signContent, $out, $this->pi_key);
		$sign = base64_encode($out);
		$sign = str_replace("\\", "", $sign);
		$params["sign"] = $sign;
		$json_str = json_encode($params);

		// EC Platform AES Key 生成
		$AES_key = $this->getAESkey();

		$data = $this->aes_encript($AES_key,$json_str);
		openssl_public_encrypt($AES_key,$key,$this->MW_public_key);
		$key = base64_encode($key);
		$key = str_replace("\\", "", $key);

		$data = urlencode($data);
		$key = urlencode($key);
		$post_params = array(
			"func"=>$function,
			"resultType"=>"json",
			"lang"=>$lang,
			"siteId"=>$this->site_id,
			"data"=>$data,
			"key"=>$key,
		);

		$url = $url.'?'.http_build_query($post_params);

		return $url;
	}


    // protected function getHttpHeaders($params) {
    // 	$headers = array();
    // 	if ($params['function'] == 'directGame') {
    // 		$headers['Content-Type'] = 'application/json';
    // 	}
    //     return $headers;
    // }

	protected function customHttpCall($ch, $params) {
		if($this->method == self::POST){
			$function = $params['function'];
			unset($params['function']);
			if($function == 'domain'){
				$this->api_url = $this->getSystemInfo('second_url');
			}

			// if($function == 'directGame'){
			// 	curl_setopt($ch, CURLOPT_POST, TRUE);
			// 	print_r(json_encode($params));
			// 	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
			// 	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			// 	return;
			// }
			$lang = isset($params['lang']) ? $params['lang'] : $this->language;
			$signContent = $this->getSignContentString($params);
			openssl_sign($signContent, $out, $this->pi_key);
			$sign = base64_encode($out);
			$sign = str_replace("\\", "", $sign);
			$params["sign"] = $sign;
			$json_str = json_encode($params);
			$this->CI->utils->debug_log('============== debug 1', $params);
			// EC Platform AES Key 生成
			$AES_key = $this->getAESkey();

			$data = $this->aes_encript($AES_key,$json_str);
			openssl_public_encrypt($AES_key,$key,$this->MW_public_key);
			$key = base64_encode($key);
			$key = str_replace("\\", "", $key);

			$data = urlencode($data);
			$key = urlencode($key);
			$post_params = array(
				"func"=>$function,
				"resultType"=>"json",
				"lang"=>$lang,
				"siteId"=>$this->site_id,
				"data"=>$data,
				"key"=>$key,
			);
			$this->CI->utils->debug_log('============== debug 2', $post_params);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_params,true));
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if($resultArr['ret']=='0000' || $resultArr['ret']==1005){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('WMG API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	private function aes_encript($key,$str){
		// $size = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
		// $str = $this->pkcs5_pad($str, $size);

		// $encrypted = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB, $key);

		$encrypted = openssl_encrypt($str, $this->encrpytion, $key, OPENSSL_RAW_DATA);

		return base64_encode($encrypted);

	}

	private function getAESkey() {
		$aes = null;
		$strPol = $this->random_alphanumeric_string(62);
		$max = strlen($strPol) - 1;

		for ($i = 0; $i < 16; $i++) {
			$aes .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
		}

		return $aes;
	}

	private function random_alphanumeric_string($length) {
	    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)) )), 0, $length);
	}

	private function getSignContentString($dataArray){
		$signContent = null;
		ksort($dataArray);

		foreach($dataArray as $key=>$value)
		{
			$signContent .= "$key=$value";
		}

		return $signContent;
	}

	//encryption
	private function aes_encrypt($key,$str){

		// $size = @mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'ecb');
		// $str = $this->pkcs5_pad($str, $size);

		// $encrypted = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB, $key);

		$encrypted = openssl_encrypt($str, $this->encrpytion, $key, OPENSSL_RAW_DATA);
		return base64_encode($encrypted);
	}

	//(PCSK5 padding)
	private function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}


	//Decrypt
	private function aes_decrypt($key,$str){
		// $encryptedData = base64_decode($str);
		// $decrypted = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $encryptedData, MCRYPT_MODE_ECB, $key);
		// $decrypted = $this->pkcs5_unpad($decrypted);

		$decrypted = openssl_decrypt($str, $this->encrpytion, $key, OPENSSL_RAW_DATA);
		return $decrypted;
	}

	private function pkcs5_unpad($text) {
		//$pad = ord($text{strlen($text) - 1});
        $pad = ord(substr($text, -1));

		if ($pad > strlen($text))
				return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
				return false;
		return substr($text, 0, -1 * $pad);
	}

	private function decryptNotifierData($key, $data, $ec_private_key)
	{
		//$key = urldecode($key);
		$key = base64_decode($key);

		$private_key = openssl_pkey_get_private($ec_private_key);
		openssl_private_decrypt($key, $decryptAes, $private_key);
		//$data = urldecode($data);
		$decryptData = $this->aes_decrypt($decryptAes, $data);

		return $decryptData;
	}

	private function verifyNotifierData($data, $mwPublickey)
	{
		$json = json_decode($data, true);
		$sign = $json["sign"];

		ksort($json);

		$json_contennt = '';
		foreach($json as $key=>$value)
		{
			if($key=='sign')
				continue;
			$json_contennt = $json_contennt."$key=$value";
		}

		$sign = base64_decode($sign);
		$isVerify = openssl_verify($json_contennt, $sign, $mwPublickey)==1;
		return $isVerify;
	}

	public function getMWGAPIDomain(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMWGAPIDomain',
		);

		$params = array(
			"timestamp" => time(),
			"function" => 'domain'
		);

		return $this->callApi(self::API_getMWGAPIDomain, $params, $context);
	}

	public function processResultForGetMWGAPIDomain($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		return array($success, $resultArr);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"uid" => $gameUsername,
			"utoken" => md5($gameUsername),
			"timestamp" => time(),
			"currency" => $this->currency,
			"function" => 'oauth'
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array(
			"player" => $gameUsername,
			"exists" => true
		);

		return array($success, $result);
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
                $lang = 'cn'; // chinese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);
		# GET TYPE
		# 0 does not return currency and only query game information in CNY currency.
		# 1 returns game information and shows values in MW currency.
		# 2 returns game information and shows values in EC currency.
		$params = array(
			'uid' => $gameUsername,
			'utoken' => md5($gameUsername),
			'timestamp' => time(),
			'getType' => $this->getType,
			'function' => 'userInfo',
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array();
		if($success){
			$result['balance'] = $this->gameAmountToDB($resultArr['userInfo']['money']);
		}

		return array($success, $result);

	}

	public function isPlayerExist($playerName, $extra=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$language = $this->getLauncherLanguage($extra['language']);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

        $params = array(
			'uid' => $gameUsername,
			'utoken' => md5($gameUsername),
			'timestamp' => time(),
			'getType' => $this->getType,
			'function' => 'userInfo'
		);
        $context['lang'] = $params['lang'] = $language;
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $playerId = $this->getPlayerIdInPlayer($playerName);

        if(empty($resultArr)){
        	$success = false;
        	$result = array('exists' => null);
        }else{
	        if($resultArr['ret']=="0000") {
	        	$result = array('exists' => true);
	        	# update flag to registered = truer
	        	$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        }else{
	            $result = array('exists' => false); # Player not found
	        }
	    }

	    $this->CI->utils->debug_log('============== MWG processResultForIsPlayerExist', $result , $resultArr);

        return array($success, $result);
    }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

    public function onlyTransferPositiveInteger(){
        return true;
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_IN;
		$extra = $result = $this->prepareTransfer($playerName, $amount, $type, $transfer_secure_id=null);

		if($result['success']&&!is_null($result['transferOrderNo'])){
			$result = $this->confirmTransfer($playerName, $amount, $type, $transfer_secure_id,$extra);
		}

		return $result;
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$type = self::TRANSFER_OUT;
		$extra = $result = $this->prepareTransfer($playerName, $amount, $type, $transfer_secure_id=null);

		if($result['success']&&!is_null($result['transferOrderNo'])){
			$result = $this->confirmTransfer($playerName, $amount, $type, $transfer_secure_id,$extra);
		}

		return $result;
	}

	public function preDepositToGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
		if($this->currency == "THB"){
			# THB deposit and Withdraw should be multiple of 5
			# 5THB : 1MW (Game value)
			if ($amount < 5){
				return array(
					'success' => false,
					'message' => sprintf(lang('Transfer failed. Less than %s %s is not valid'),'5.0',$this->currency)
				);
			}
			if ($amount % 5 != 0){
				return array(
					'success' => false,
					'message' => sprintf(lang('Transfer failed. Your transfer amount must be a multiple of %s %s'),'5.0',$this->currency)
				);
			}
		}

		return $this->returnUnimplemented();
	}

	public function preWithdrawFromGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
		if($this->currency == "THB"){
			# THB deposit and Withdraw should be multiple of 5
			# 5THB : 1MW (Game value)
			if ($amount < 5){
				return array(
					'success' => false,
					'message' => sprintf(lang('Transfer failed. Less than %s %s is not valid'),'5.0',$this->currency)
				);
			}
			if ($amount % 5 != 0){
				return array(
					'success' => false,
					'message' => sprintf(lang('Transfer failed. Your transfer amount must be a multiple of %s %s'),'5.0',$this->currency)
				);
			}
		}

		return $this->returnUnimplemented();
	}

	public function prepareTransfer($playerName, $amount, $type, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$transferOrderNo = $type == self::TRANSFER_IN? 'DEP_'.$gameUsername.time():'WID_'.$gameUsername.time();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForPrepareTransfer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'type' => $type,
            'transferOrderNo' => $transferOrderNo
        );

		$params = array(
            'uid' => $gameUsername,
			'utoken' => md5($gameUsername),
			'transferType' => $type,
			'transferAmount' => $amount,
			'transferOrderNo' => $transferOrderNo,
			'transferOrderTime' => date('Y-m-d H:i:s'),
			'transferClientIp' => $this->CI->input->ip_address(),
			'transferNotifierUrl' => '', # just empty
			'function' => 'transferPrepare',
			'timestamp' => time(),
			'currency' => $this->currency,
		);

		return $this->callApi(self::API_prepareTransfer, $params, $context);
	}

	public function processResultForPrepareTransfer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $transferOrderNo = $this->getVariableFromContext($params, 'transferOrderNo');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        if($success){
			$result['asinTransferOrderNo']= isset($resultArr['asinTransferOrderNo']) ? $resultArr['asinTransferOrderNo'] : "";
        	$result['asinTransferDate']= isset($resultArr['asinTransferDate']) ? $resultArr['asinTransferDate'] : "";
        	$result['transferOrderNo']=$transferOrderNo;
        } else {
			$error_code = @$resultArr['ret'];
			switch($error_code) {
				case '1000' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER; # 接入平台授权参数缺失[uid]
					break;
				case '1001' :
					$result['reason_id'] = self::REASON_INVALID_KEY; # 接入平台授权参数有误[utoken]
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

        return array($success, $result);
	}

	public function confirmTransfer($playerName, $amount, $type, $transfer_secure_id=null, $extra=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTransferCredit',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'type' => $type,
            'transferOrderNo' => @$extra['transferOrderNo']
        );

		$params = array(
            'uid' => $gameUsername,
			'utoken' => md5($gameUsername),
			'asinTransferOrderNo' => @$extra['asinTransferOrderNo'],
			'asinTransferOrderTime' => @$extra['asinTransferDate'],
			'transferOrderNo' => @$extra['transferOrderNo'],
			'transferAmount' => $amount,
			'transferClientIp' => $this->CI->input->ip_address(),
			'merchantId' => null,
			'timestamp' => time(),
			'currency' => $this->currency,
			'function' => 'transferPay'
		);

		$callApiMethod = $type==self::TRANSFER_OUT?(self::API_withdrawFromGame):(self::API_depositToGame);

		return $this->callApi($callApiMethod, $params, $context);
	}

	public function processResultForTransferCredit($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transferOrderNo = $this->getVariableFromContext($params, 'transferOrderNo');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
			$error_code = @$resultArr['ret'];
			switch($error_code) {
				case '1000' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER; # 接入平台授权参数缺失[uid]
					break;
				case '1001' :
					$result['reason_id'] = self::REASON_INVALID_KEY; # 接入平台授权参数有误[utoken]
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

        return array($success, $result);

	}

	public function getMWGGameList() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMWGGameList'
		);

		$params = array(
			'timestamp' => time(),
			'function' => 'gameInfo'
		);

		return $this->callApi(self::API_getGameList, $params, $context);
	}

	public function processResultForGetMWGGameList($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result['games'] = $resultArr['games'];
		}

		return array($success, $result);

	}

	public function queryForwardGame($playerName, $extra = null) {
		$playerId = $this->getPlayerIdInPlayer($playerName);
		if ($this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode())) {
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		} else {
			$gameUsername = $playerName;
		}
		
		$language = $this->getLauncherLanguage($extra['language']);
		$game_code = $extra['game_code'];
		$is_mobile = $extra['is_mobile'];
		$is_app = isset($extra['is_app']) ? $extra['is_app'] : null;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardgame',
			'lang' => $language,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"uid" => $gameUsername,
			"utoken" => md5($gameUsername),
			"timestamp" => time(),
			"currency" => $this->currency,
			"function" => 'oauth'
		);

		if(!$is_app){
			$jumpType = 0; #flash or html5
		} else {
			$jumpType = 2; #app
		}

		$params['gameId'] = $game_code;

		$context['jumpType'] = $params['jumpType'] = $jumpType;
		$context['lang'] = $params['lang'] = $language;
		$this->method = self::POST;

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardgame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$jumpType = $this->getVariableFromContext($params, 'jumpType');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if($success){
			$result['url'] = $this->api_url.'/'.$resultArr['interface'];
			// if($jumpType == 3){
			// 	$game = $this->directMWGGame($playerName,$resultArr);
			// 	echo "<pre>";print_r($game);exit;
			// }
		}
		$this->CI->utils->debug_log('============== debug 3', $result , $resultArr);
		return array($success, $result);
	}

	// public function directMWGGame($playerName,$resultArr){
	// 	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$interface = explode('?',$resultArr['interface']);
	// 	$this->URI_MAP[self::API_directMWGGame] = '/'.$interface[0];
	// 	parse_str($interface[1], $postParams);

	// 	$context = array(
	// 		'callback_obj' => $this,
	// 		'callback_method' => 'processResultForDirectMWGGame',
	// 		'playerName' => $playerName,
	// 		'gameUsername' => $gameUsername
	// 	);
	// 	$postParams['function'] ='directGame';
	// 	$params = $postParams;
	// 	$this->method = self::POST;

	// 	// print_r($params);exit;

	// 	return $this->callApi(self::API_directMWGGame, $params, $context);
	// }

	// public function processResultForDirectMWGGame($params){
	// 	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
	// 	$playerName = $this->getVariableFromContext($params, 'playerName');
	// 	$resultArr = $this->getResultJsonFromParams($params);
	// 	$success = isset($resultArr['launchUrl']);
	// 	$result = array();
	// 	if($success){
	// 		$result['url'] = $this->api_url.'/'.$resultArr['launchUrl'];
	// 	}

	// 	return array($success, $result);
	// }

	public function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		//observer the date format
		$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		$queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
	    $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

	    while ($queryDateTimeMax  > $queryDateTimeStart) {

			$done = false;
			$currentPage = self::START_PAGE;
			while (!$done) {

				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'startDate' => $queryDateTimeStart,
					'endDate' => $queryDateTimeEnd
				);

				$params = array(
					'beginTime' => $queryDateTimeStart,
					'endTime' => $queryDateTimeEnd,
					'page' => $currentPage,
					'getType' => $this->getType, // return settled bet only
					'function' => 'siteUsergamelog', // return settled bet only
				);
				$this->method = self::POST;

				$rlt = $this->callApi(self::API_syncGameRecords, $params, $context);

				$done = true;
				if ($rlt) {
					$success = $rlt['success'];
				}

				if ($rlt && $rlt['success']) {
					$currentPage = $rlt['currentPage'];
					$totalItem = $rlt['totalItem'];
					//next page
					$currentPage += 1;

					$done = $totalItem == 0;

					$this->CI->utils->debug_log($params, 'currentPage', $currentPage,'done', $done, 'result', $rlt);
				}
			}

			$queryDateTimeStart = $queryDateTimeEnd ;
	    	$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
		}

		return array("success" => $success);
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('mwg_game_logs'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];

		$dataCount = 0;
		if($success){
			$gameRecords = isset($resultArr['userGameLogs'])?$resultArr['userGameLogs']:array();
			$availableRows = !empty($gameRecords)?$this->CI->mwg_game_logs->getAvailableRows($gameRecords):array();

			foreach ($availableRows as $record) {
				$insertRecord = array();
				$insertRecord['uid'] = isset($record['uid'])?$record['uid']:null;
				$insertRecord['merchantId'] = isset($record['merchantId'])?$record['merchantId']:null;
				$insertRecord['gameName'] = isset($record['gameName'])?$record['gameName']:null;
				$insertRecord['gameNum'] = isset($record['gameNum'])?$record['gameNum']:null;
				$insertRecord['gameType'] = isset($record['gameType'])?$record['gameType']:null;
				$insertRecord['playMoney'] = isset($record['playMoney'])?$record['playMoney']:null;
				$insertRecord['winMoney'] = isset($record['winMoney'])?$record['winMoney']:null;
				$insertRecord['logDate'] = isset($record['logDate'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['logDate']))):null;
				$insertRecord['currency'] = isset($record['currency'])?$record['currency']:null;
				$insertRecord['exInfo'] = isset($record['exInfo'])?$record['exInfo']:null;
				$insertRecord['gameId'] = isset($record['gameId'])?$record['gameId']:null;

				//extra info from SBE
				$insertRecord['uniqueid'] = isset($record['gameNum']) ? $record['gameNum'] : NULL;
				$insertRecord['external_uniqueid'] = isset($record['gameNum']) ? $record['gameNum'] : NULL;
				$insertRecord['response_result_id'] = $responseResultId;
				$insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');

				//insert data to mwg gamelogs table database
				$this->CI->mwg_game_logs->insertGameLogs($insertRecord);
				$dataCount++;
			}

			$result['totalItem'] =  isset($resultArr['total']) ? $resultArr['total'] : 0;
			$result['currentPage'] = isset($resultArr['page']) ? $resultArr['page'] : 0;
			$result['dataCount'] = $dataCount;
		}

		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'mwg_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->mwg_game_logs->getGameLogStatistics($startDate, $endDate);
		// echo "<pre>";print_r($result);exit;
		$cnt = 0;
		if (!empty($result)) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $row) {
				$cnt++;

				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;

				if(empty($row->game_type_id)&&empty($row->game_description_id)){
					list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
				}
				// $bet_amount = $row->bet_amount;
				// $win_amount = $row->result_amount;
				$bet_amount = $this->gameAmountToDB($row->bet_amount);
				$win_amount = $this->gameAmountToDB($row->result_amount);
				$result_amount = (float)$win_amount - (float)$bet_amount;

				$extra = array('table' => $row->round_id);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$row->game_code,
					$row->gameType,
					$row->game,
					$row->player_id,
					$row->uid,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$row->external_uniqueid,
					$row->logDate, //start
					$row->logDate, //end
					$row->response_result_id,
					Game_logs::FLAG_GAME,
                    $extra
				);

			}
		}

		$this->CI->utils->debug_log('MWG PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row->gameId;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->gameName);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($gameUsername);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($gameUsername);
        return array("success" => true);
    }

    public function login($playerName, $password = null, $extra = null) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}
}

/*end of file*/