<?php
class Crypto_payment_api_cce_test extends Unit_test_runner_lib {
	private $platformCode = CCE_CRYPTO_PAYMENT_API;

	/** @var Crypto_payment_api_cce */
	private $api = null;

	# overload parent functions
	public function init() {
		$this->_excludeTest('test_callback');

		list($loaded, $apiClassName) = $this->CI->utils->loadExternalSystemLib($this->platformCode);

		$this->assert($loaded, true, 'Is API class loaded');

		$this->api = $this->CI->$apiClassName;
	}

	public function test_createAccount() {
		$account = $this->api->api_createAccount('1');

		// var_export($account);

		$this->assert(!is_null($account), true, 'Test createAccount api');
	}

	public function test_getBalance() {
		$balance = $this->api->api_getBalance('0x9b8bc013f038f303a261ee4807403bb6b02806e1');

		// var_export($balance);

		$this->assert(!is_null($balance), true, 'Test getBalance api');
	}

	public function test_transferOutCoinlogs() {
		$logs = $this->api->api_transferOutCoinlogs('1', 10, '0xe9D3fB5500ea2Efe11B2fB8f43Ffe40EE9D69c52');

		// var_export($logs);

		$this->assert(!is_null($logs), true, 'Test transferOutCoinlogs api');
	}

	public function test_searchHistory() {
		$history = $this->api->api_searchHistory('0x9b8bc013f038f303a261ee4807403bb6b02806e1');

		// var_export($history);

		$this->assert(!is_null($history), true, 'Test searchHistory api');
	}

	public function test_getCoinInfo() {
		$coinfo = $this->api->api_getCoinInfo();

		// var_export($coinfo);

		$this->assert(!is_null($coinfo), true, 'Test getCoinInfo api');
	}

	public function test_callback()
	{
		$block_number = '21';
		$input = [
			'client_id' => $this->api->getSystemInfo('account'),
			'request_type' => 'BJC',
			'charset' => 'UTF-8',
			'request_time' => '20230621000000000',
			'message_no' => '',
			'sign_method' => 'DES',
			'signature' => '',
			'body' => $this->api->encryptData([
				'serie_no' => 'S' . floor(microtime(true) * 1000),
				'tx_hash' => '0x' . md5($block_number),
				'opt_type' => '充值',
				'mny_smb' => 'ETH.USDT',
				'mny_count' => '100.00',
				'from' => '0xe9D3fB5500ea2Efe11B2fB8f43Ffe40EE9D69c52',
				'to' => '0x9b8bc013f038f303a261ee4807403bb6b02806e1',
				'state' => '1',
				'cust_no' => $this->api->formate_uid('1'),
				'block_number' => $block_number,
				'confirm_time' => date('YmdHis') . '000'
			])
		];

		$this->setRequestData($input, static::REQUEST_DATA_INPUT);

		$params = $this->api->getInputGetAndPost();

		$orderId = $this->api->getOrderIdFromParameters($params);
		$playerId = $this->api->getPlayerIdByOrderId($orderId);
		$result = [];

		$this->CI->lockAndTransForPlayerBalance($playerId, function() use ($orderId, $params, &$result){
			$result = $this->api->callbackFromServer($orderId, $params);

			return true;
		});

		// var_export($result);

		$this->assert($result['success'] === true, true, 'Test callback api');
	}

	public function test_decrypt()
	{
		$encryptText = 'KNxuCmf9y6pHudogsPEf4QzCg/ljl93S82p4ozl2Jhq5DfvBq03cjOQYePiFOZCgtmRJ6IJVkkwtV2cA/5/bZGRsJ8fjFMUVv3JuHU2CVOFcmym06kK68oBPEBPjz7RUDMKD+WOX3dLzanijOXYmGrkN+8GrTdyM5Bh4+IU5kKC2ZEnoglWSTC1XZwD/n9tkZGwnx+MUxRW/cm4dTYJU4QiATAbDlQMAVEwSdV85C4+KiHDPKMsplqEfFvUaZpjtvz6L/JL8IcUUZZQOwtARdrQ5ooZzP9ZpTD1gcLcpigxhhl5CnwJgunLML7nwKCHatEcP1c9ed3Bbc+qiNEAho0VSS7Ia7zgTgCChqsABqBbx8cdRvW6Fhr5mlwVBx2p+T8uFsSst77oB0NM5iO/QsgBBdw2FS3YRE0gI9P0MUOzwF21j/clfJ1NAKoZAwx3BkVg9OCOezmrTe6gayO7mb4rmFVnBtSBnTD1gcLcpigxMPWBwtymKDEw9YHC3KYoMWIaHG3+igR9G3yCKilI42J+McV4CLSgit89dYI9xgtLp8wu1yCu7uw==';
		$decryptText = $this->api->decryptData($encryptText);

		// var_export($encryptText);
		// var_export($decryptText);

		$this->assert(!empty($decryptText), true, 'Test decrypt method');
	}
}