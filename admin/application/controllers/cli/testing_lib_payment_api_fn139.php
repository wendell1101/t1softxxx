<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_payment_api_fn139 extends BaseTesting {

	private $platformCode = FN139_PAYMENT_API;
	private $api = null;
	private $order_id = null;
	private $time = null;

	# overload parent functions
	public function init() {
		list($loaded, $apiClassName) = $this->utils->loadExternalSystemLib($this->platformCode);

		$this->test($loaded, true, 'Is API class loaded. Expected: true');
		if (!$loaded) {
		 	$this->utils->debug_log("Error: API not loaded, platformCode = " . $this->platformCode);
		 	return;
		}

		$this->api = $this->$apiClassName;
		$this->test($this->api->getPlatformCode(), $this->platformCode, 'Test loaded API\'s platform code. Expected: ' . $this->platformCode);
	}

	## all tests route through this function
	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	# Actual Tests
	## Invokes all tests defined below. A test function's name should begin with 'test'
	public function testAll() {
		$classMethods = get_class_methods($this);
		$excludeMethods = array('test', 'testTarget', 'testAll', 'gen_key');
		foreach ($classMethods as $method) {
			if (strpos($method, 'test') !== 0 || in_array($method, $excludeMethods)) {
				continue;
			}

			$this->$method();
		}
	}

	private function test_transfer() {
		$this->CI = &get_instance();
		$datetime = new DateTime('now', new DateTimeZone('Asia/Taipei'));
		$now = $datetime->format('YmdHis');
		$this->utils->debug_log('now', $now);
		$this->order_id = $now . '0005';
		$this->time = time();
		$params = array(
			'api' => 'transfer',
			'order_id' => $this->order_id,
			'apply_site' => 'fn139',
			'apply_username' => 'spencer',
			'apply_type' => 'birdegg',
			'num' => 100,
			'receive_username' => 'spencer',
			'receive_type' => 'main_wallet',
			'time' => $this->time
		);
		$token = $this->gen_key($params, 'testkey');
		$this->utils->debug_log('token', $token);
		$params['token'] = $token;
		$result = $this->api->validateCallbackParameters($params);
		$this->utils->debug_log('response', $result);
		if ($result['success'] && $result['return_result']['errorNum'] == 0) {
			$this->utils->debug_log('process callbackfromserver');
			$result = $this->api->callbackFromServer(null, $result);
			$this->utils->debug_log('callbackfromserver', $result);
		}
		$this->test($result['success'], true, 'Test Transfer POST To payment_api_fn139. Expected: ' . var_export($result, true));
	}

	private function test_confirm() {
		$this->CI = &get_instance();
		$params = array(
			'api'=>'confirm',
			'order_id'=>$this->order_id,
			'time' => $this->time,
		);
		$token = $this->gen_key($params, 'testkey');
		$params['apply_site'] = 'fn139';
		$params['token'] = $token;
		$this->CI->utils->debug_log('test_confirm', var_export($params, true));
		$result = $this->api->validateCallbackParameters($params);
		$this->test($result['success'], true, 'Test Confirm POST To payment_api_fn139. Expected: ' . var_export($result, true));
	}

	private function gen_key($params,$apiKey){
	    //參數根據鍵值進行升序排序
	    ksort($params,SORT_STRING);
	    //拼接參數字符串，并在前面加上api_key值
	    $str = $apiKey.implode('', $params);
		$this->utils->debug_log('gen_key origional : ', $str);
	    //進行MD5加密
	    return md5($str);
	}

}
