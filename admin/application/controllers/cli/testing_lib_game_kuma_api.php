<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_game_kuma_api extends BaseTesting {

	private $platformCode = KUMA_API;
	private $platformName = 'KUMA_API';

	private $api = null;


	public function init() {

		$this->api = $this->utils->loadExternalSystemLibObject($this->platformCode);


	}

	public function testAll() {

	 	$this->init();
	   	//$this->eto();
	   	$this->testCreatePlayer();
	   	//$this->testLoginPlayer();
	    //$this->testQueryPlayerBalance();
	    //$this->prepareTransferCredit();
	    //$this->testDeposit();
	    //$this->testWithdraw();
	    //$this->testSyncGameLogs();
	    //$this->testSyncMergeToGameLogs();

	    //$this->testIsPlayerExist();
	    //$this->testQueryForwardGame();
	 	//$this->testChangePassword();
	 	//$this->testBlockPlayer();
	 	//$this->testUnblockPlayer();
	    //$this->testSyncMergeToGameLogs();
	    // $this->testIsPlayerExist();
	     //$this->testLogout();


	}

	private function eto() {
		$DO_URL = "http://www.kgslots.com/kg_api/action/doValidate.php";
		$FW_URL = "http://www.kgslots.com/kg_api/action/fwValidate.php";

		$LANGUAGE = "zh-cn";
		$CURRENCY = "CNY";
		$ODD_TYPE = "A";

		$TEST_VENDOR_ID = "CS0001";
		$TEST_VENDOR_KEY= "4247a396b1bb56afb32f7efcb0b19f01";
		$TEST_NICK_NAME = "testshu";
		$TEST_PLAYER_NAME = "testshu";
		$TEST_SECURE_TOKEN = $TEST_VENDOR_ID.time();// this is example.
		$TEST_BILL_NUMBER = $TEST_VENDOR_ID.time();	// this is example, big letter + Number will valid.
		$TEST_GAME_ID = "1011";
		$TEST_CREDIT = "100";
		$TEST_HOME_URL = "http://www.kgslots.com";

		//----------------------------------------------------------------------------------------------------

		// Create Player.
		$crtParams = [];
		$crtParams['Loginname'] = $TEST_PLAYER_NAME;
		$crtParams['SecureToken'] = $TEST_SECURE_TOKEN;
		$crtParams['NickName'] = $TEST_NICK_NAME;
		$crtParams['Cur'] = $CURRENCY;
		$crtParams['Oddtype'] = $ODD_TYPE;
		echo "<pre>";print_r($this->kg_player_create($TEST_VENDOR_ID, $TEST_VENDOR_KEY, $crtParams, $DO_URL));
	}

	function kg_player_create($buId, $priKey, $userParms, $APIUrl)
	{
		$createRet = $this->send_kg_get($APIUrl, $this->get_format_string($buId, "lg", $userParms), $priKey);
		return $createRet['Status'] == "1";
	}

	function get_format_string($buId, $method, $dtlParms)
	{
		$postString = "CTGent=".$buId.",Method=".$method;
		$parmsString = "";
		foreach ($dtlParms as $key => $value) {
		  	$parmsString = $parmsString.$key."=".$value.",";
		}
		return $postString.",".$parmsString;
	}

	function send_kg_get($url, $fParms, $priKey)
	{
		$postArray =[];
		$postArray['params'] = $this->get_aes_string($fParms);
		$postArray['key'] = md5($fParms.$priKey);
		$url = $url."?params=".$postArray['params']."&key=".$postArray['key'];
		echo $fParms;exit;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$output = curl_exec($ch);
		$result = [];
		if (!curl_errno($ch)) {
		  switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
		    case 200:  # OK
					$explodeOutput = explode(",",$output);
					foreach ($explodeOutput as $value) {
						$temp = explode("=",$value,2);
						if(count($temp) >1){
							$result[$temp[0]] = $temp[1];
						}
					}
		      break;
		    default:
					$result['Status'] = 0;
					$result['ErrorMsg'] = "Unexpected HTTP code: ".$http_code;
		  }
		}
		curl_close($ch);
		echo "<pre>";print_r($result);exit;
		return $result;
	}

	function get_aes_string($parms)
	{

		$kgAESKey = "3f203ac36b4262ba28afa0366f4a63b9";
		$key = pack('H*', $kgAESKey);
		$iv_size = @mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = @mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$cipher_params = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $parms, MCRYPT_MODE_CBC, $iv);
		$cipher_params_iv = $iv . $cipher_params;
		return base64_encode($cipher_params_iv);
	}

	private function testSyncGameLogs() {

		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-12-03 15:00:00');
		$dateTimeTo = new DateTime('2016-12-03 16:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncOriginalGameLogs($token);

		echo "<pre>";print_r($rlt);exit;
	}

	private function testSyncMergeToGameLogs() {
		//$api = $this->game_platform_manager->initApi($this->platformCode);
		$token = 'abc123d';
		$dateTimeFrom = new DateTime('2016-12-03 15:00:00');
		$dateTimeTo = new DateTime('2016-12-03 16:00:00');

		$this->api->syncInfo[$token] = array("dateTimeFrom" => $dateTimeFrom, "dateTimeTo" => $dateTimeTo);
		$rlt = $this->api->syncMergeToGameLogs($token);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testCreatePlayer() {

		//$username = $this->existPlayer;
		$username = 'testshu12';
		$password = 'password';
		$playerId = '999';
		$rlt = $this->api->createPlayer($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testLoginPlayer(){
		$username = 'testshu123';
		$password = 'password';
		$playerId = '111';
		$rlt = $this->api->login($username,$playerId,$password);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testChangePassword() {
		$username = 'testshu';
		//$oldPassword = $this->password;
		$oldPassword = 'password1';
		$newPassword = 'password';

		$rlt = $this->api->changePassword($username, $oldPassword, $newPassword);
		echo "<pre>";print_r($rlt);exit;
	}

	public function testQueryPlayerBalance() {

		$playerName = 'testshu';
		$rlt = $this->api->queryPlayerBalance($playerName);
		echo "<pre>";print_r($rlt);exit;

	}


	private function prepareTransferCredit() {

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->prepareTransferCredit($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testDeposit() {

	    $playerName = 'testshu';
	    $depositAmount = 1;
		$rlt = $this->api->depositToGame($playerName, $depositAmount);
		echo "<pre>";print_r($rlt);exit;

	}



	private function testWithdraw() {
		$playerName = 'testshu';
		$withdrawAmount = 1;
		$rlt = $this->api->withdrawFromGame($playerName, $withdrawAmount);
		echo "<pre>";print_r($rlt);exit;

	}

	private function testIsPlayerExist() {

		$username =  'testshu';
		$rlt = $this->api->isPlayerExist($username);
		var_dump($rlt);

	}

	private function testQueryForwardGame(){
     	$username =  'testshu';
		$rlt = $this->api->queryForwardGame($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testBlockPlayer(){
		$username =  'testshu';
		$rlt = $this->api->blockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}

	private function testUnblockPlayer(){
		$username =  'testshu';
		$rlt = $this->api->unblockPlayer($username);
		echo "<pre>";print_r($rlt);exit;
	}



}