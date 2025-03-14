<?php

require_once dirname(__FILE__) . '/base_testing.php';

class Testing_lib_player_center_api_in_smash extends BaseTesting {

	private $api_uri = 'http://player.og.local/api/smash/';
	// http://player.og.local/api/smash/generateEventUrlWithToken/352272?do_public_encrypt=dbg

	private $api_key = 'b1606d14f9309c94';
	private $player_id = 352272;

	// cloned form $config['api_key_player_center_private_key_in_smash_promo_auth']
	private $api_key_player_center_private_key_in_smash_promo_auth = 'MIICXAIBAAKBgQDCxS9ut6Yss8YXaTKtcvULfDkE9+YdBjUMcVCTVAGIWz1b/Myl5CwBglDFuyYFXQii4+Oyruafhh9/SySkHBex15yNszhELQWEwwVH+s2t9sV7V4aqiiPZogE1XhHixyHhJMl/omKTtGCH+0rESBtld4iD9pvuxm4XesWX/+iCXQIDAQABAoGAVamUKwXquE55GWLTPyYIUHzaNy0wsCNCwa402hdgdTBr1EFjYLLyB9fg3pZpKoK4gavpQXVbSV4cDEhoXSVd5byBfloRjuIU09po4uGXaxcwnBgmeBwjjexbTFEtA2sIga1wdC/1a39rKxKTsfeXU9iftHJC+aEO8toOQ6JgxQECQQD4OEHNJq9ET3HRtUpxf+rPeRz9XEMSukOIg8d2TuSENOdQtQmx/dZtmItfqwrYcqdTLfG037g0OL4sKqLFT1m5AkEAyOALI4eE9gk5GrGRliTLepv9E03bVDWO2mKQ0rfwehkg2qs36DVRKd/67DUtXRplL3o3Jpk4mIbht8CQnRuvxQJATxj+PvWY3FfEmWL/+fMdTEf36PTBmvIoGxSDNzwkrcx9+cX29PVCo2H859uFdTvz/hmh8FVqSZnbYA+mFuIWYQJBAK5wYTvpY72FJOHZceRA77L54zvwUJdAK13aWomi0mI1kCJUragpJOKIbw7Q3yQK1/Py3hHW3R8XgsxfnTXR5UECQGFMUTpsPWzD4XIC8zA7lAoGDj37u7Wu870/vRKB+Zc5Lwd7TxBIHp2TCktGqhQwbwecX20gm0YGuHW/GTMDAVk=';

	private $return_list_by_method = [];


	public function init() {
		$this->load->library('utils');
		$this->test($this->utils != null, true, 'init utls');
	}

	/**
	 * Test All Case
	 *
	 * The related commands,
	 * sudo /bin/bash admin/shell/run_ci_cli.sh testing_lib_player_center_api_in_smash testAll > ./logs/testing_lib_player_center_api_in_smash.testAll.20221101.log &
	 *
	 * @return void
	 */
	public function testAll() {
		$this->init();


		// // After login in player site, the get the Cookie string of header in Request Headers.
		// $cookieStr = '__OG_TARGET_DB_player=default; sess_og_player=e59ffd79f31556b69c543c83ee8a6140';
		// $this->async_get_event_url($cookieStr);


		$_params['player_id'] = 352272;
		$_params['reveal_raw_token'] = 1;
		$this->generateEventUrlWithToken($_params);
		$this->useinfo();
		$this->bet_info();
		$this->bet_amount();
		$shouldBe = 'empty';
		$this->invite_friend($shouldBe); // No data in 352272


		// /// case: invite_friend has data
		// $_params['player_id'] = 163299;
		// $_params['reveal_raw_token'] = 1;
		// $this->generateEventUrlWithToken($_params);
		// $shouldBe = 'data';
		// $this->invite_friend();

		// // case: No data
		// $_params['player_id'] = 163299;
		// $_params['reveal_raw_token'] = 1;
		// $this->generateEventUrlWithToken($_params);
		// $this->bet_amount();


		// /// case: invite_friend Not founud by token
		// $shouldBe = 'notFound';
		// $_token = 'NotFromAnyPlayer';
		// $this->invite_friend($shouldBe, $_token);


	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function _genSign($api_key, $token){
		return md5($token. $this->api_key);
	}


	private function async_get_event_url($cookieStr){

		$url = 'http://player.og.local/async/get_event_url/dbg/'. $this->api_key;

		$params = [];
		$params['tmp'] = 123;
		$response = $this->_Httpful_post($url, $params, $cookieStr);
$this->utils->debug_log('async_get_event_url.response.raw_body', $response->raw_body);
		$raw_json = substr($response->raw_body, 1, -1);
		$decoded = $this->utils->json_decode_handleErr($raw_json, true);
		$this->test($decoded['success'], true, 'In async_get_event_url api, success should be true.');
		$this->test( !empty($decoded['raw_token']), true, 'In async_get_event_url api, raw_token should Not Empty.');
		$this->test( !empty($decoded['sign']), true, 'In async_get_event_url api, sign should Not Empty.');
	}

	// testTarget/generateEventUrlWithToken
	private function generateEventUrlWithToken($params = null){

		$url = $this->api_uri. 'generateEventUrlWithToken';

		if($params === null){
			$params = [];
			$params['player_id'] = $this->player_id;
			$params['api_key'] = $this->api_key;
			$params['reveal_raw_token']= 1;
		}


		$response = $this->_Httpful_post($url, $params);
// $this->utils->debug_log('44.response.raw_body', $response->raw_body);
		$decoded = $this->utils->json_decode_handleErr($response->raw_body, true);

		// {"message":"Test [In generateEventUrlWithToken api, success should be true.] failed: Actual [1] => Expected [], Notes: []","context":[],"level":400,"level_name":"ERROR","channel":"default-og","datetime":"2022-11-01 17:00:31 407407","extra":{"tags":{"request_id":"85cfed7867f60f94ce0ceff5f14f284a","env":"live.og_local","version":"6.178.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/submodules/core-lib/system/core/Exceptions.php","line":151,"class":"CI_Exceptions","function":"show_error","process_id":25245,"memory_peak_usage":"30.25 MB","memory_usage":"28.25 MB"}}

		$this->test($decoded['success'], true, 'In generateEventUrlWithToken api, success should be true.');
		$this->test($decoded['code'], 200, 'In generateEventUrlWithToken api, the success code should be 200.');

		$this->test($decoded['result']['is_valid_decrypt'], true, 'In generateEventUrlWithToken api, result.is_valid_decrypt should be true.');
		$this->test(!empty($decoded['result']['raw_token']), true, 'In generateEventUrlWithToken api, result.raw_token should be Not Empty.');


		$_raw_token = null;
		if( ! empty($decoded['result']['raw_token']) ){
			$_raw_token = $decoded['result']['raw_token'];
		}
		$_token = null;
		if( ! empty($decoded['result']['_token']) ){
			$_token = $decoded['result']['_token'];
		}

		$this->return_list_by_method['generateEventUrlWithToken']['raw_token'] = $_raw_token;
		$this->return_list_by_method['generateEventUrlWithToken']['token'] = $_token;

	} // EOF generateEventUrlWithToken


	private function useinfo(){

		$url = $this->api_uri. 'useinfo';
		$params = [];
		// $params['player_id'] = $this->player_id; // TODO, shoud form $this->return_list_by_method['generateEventUrlWithToken']['raw_token']
		$params['token'] = $this->return_list_by_method['generateEventUrlWithToken']['raw_token'];
		$params['sign'] = $this->_genSign($this->api_key, $params['token']);


		// // $params['player_id'] = 352272;
		// $params['token'] = 'c874d8766ad32b1ca4ecfaca171c5d64';
		// $params['sign'] = 'a5c1d640b11348f224452f130e588386';


		$response = $this->_Httpful_post($url, $params);
// $this->utils->debug_log('64.response.raw_body', $response->raw_body);
		$decoded = $this->utils->json_decode_handleErr($response->raw_body, true);

		// $this->test($decoded['success'], true, 'In generateEventUrlWithToken api, success should be true.');
		$this->test($decoded['code'], 200, 'In useinfo api, the success code should be 200.');
		$this->return_list_by_method['useinfo']['uid'] = $decoded['result']['uid'];
	} // EOF useinfo


	/**
	 * Test invite_friend api
	 *
	 * @param string $shouldBe for test()
	 * - data The result should be data
	 * - empty The result.list should be empty
	 * - notFound The plater of token, thats should be not Found.
	 * @return void
	 */
	private function invite_friend($shouldBe = 'data', $token = null, $sign = null){

		$url = $this->api_uri. 'invite_friend';
		$params = [];
		// // $params['player_id'] = $this->player_id; // TODO, shoud form $this->return_list_by_method['generateEventUrlWithToken']['raw_token']
		if( is_null($token) ){
			$params['token'] = $this->return_list_by_method['generateEventUrlWithToken']['raw_token'];
		}else{
			$params['token'] = $token;
		}
		if( is_null($sign) ){
			$params['sign'] = $this->_genSign($this->api_key, $params['token']);
		}else{
			$params['sign'] = $sign;
		}


		// $params['player_id'] = 352272;
		// $params['token'] = 'c874d8766ad32b1ca4ecfaca171c5d64';
		// $params['sign'] = 'a5c1d640b11348f224452f130e588386';


		$response = $this->_Httpful_post($url, $params);
// $this->utils->debug_log('64.response.raw_body', $response->raw_body);
		$decoded = $this->utils->json_decode_handleErr($response->raw_body, true);

		switch ($shouldBe){ // the result should be...
			case 'data':
				$this->test($decoded['code'], 200, 'In invite_friend api, the success code should be 200.');
				$this->test( !empty($decoded['result']['uid']), true, 'In invite_friend api, the uid should be Not Empty.');
				$this->return_list_by_method['invite_friend']['uid'] = $decoded['result']['uid'];

				$remarks['notEmpty'] = 'In invite_friend api, the list should be Not Empty.';
				$remarks['count'] = 'In invite_friend api, the count(list) should be greater than Zero.';
				$this->_testListShoudBeHasData($decoded['result']['list'], $remarks);
				break;
			case 'empty':
				$remarks['empty'] = 'In invite_friend api, the list should be Empty.';
				$this->_testListShoudBeEmpty($decoded['result']['list'], $remarks);

				break;
			case 'notFound':// not found by token
				$remarks['successShouldBeFalse'] = 'In invite_friend method, the success should be FALSE.';
				$remarks['codeShouldBeERR_INVALID_SECURE'] = 'In invite_friend method, the code should be ERR_INVALID_SECURE,1010.';
				$this->_testTokenShoudBeNotFound($decoded['success'],$decoded['code'], $remarks);
				break;

		}

	} // EOF invite_friend

	function _testTokenShoudBeNotFound($success, $code, $remarks){
		if( empty($remarks['successShouldBeFalse']) ){
			$remarks['successShouldBeFalse'] = 'In _testTokenShoudBeNotFound method, the success should be FALSE.';
		}
		if( empty($remarks['codeShouldBeERR_INVALID_SECURE']) ){
			$remarks['codeShouldBeERR_INVALID_SECURE'] = 'In testListShoudBeHasData method, the code should be ERR_INVALID_SECURE,1010.';
		}
		$this->test( $success, false, $remarks['successShouldBeFalse']);
		// t1t_comapi_module_smash_promo_auth::errors['ERR_INVALID_SECURE'] = 1010
		$this->test( $code, 1010, $remarks['codeShouldBeERR_INVALID_SECURE']);
	}

	private function _testListShoudBeHasData($list, $remarks = []){
		if( empty($remarks['notEmpty']) ){
			$remarks['notEmpty'] = 'In testListShoudBeHasData method, the list should be Not Empty.';
		}
		if( empty($remarks['count']) ){
			$remarks['count'] = 'In testListShoudBeHasData method, the count(list) should be greater than Zero.';
		}
		$this->test( !empty($list), true, $remarks['notEmpty']);
		$this->test( count($list) > 0, true, $remarks['count']);
	}

	private function _testListShoudBeEmpty($list, $remarks = []){
		if( empty($remarks['empty']) ){
			$remarks['empty'] = 'In testListShoudBeHasData method, the list should be Not Empty.';
		}
		$this->test( empty($list), true, $remarks['empty']);
	}

	private function bet_info(){

		$url = $this->api_uri. 'bet_info';
		$params = [];
		// $params['player_id'] = $this->player_id; // TODO, shoud form $this->return_list_by_method['generateEventUrlWithToken']['raw_token']
		$params['token'] = $this->return_list_by_method['generateEventUrlWithToken']['raw_token'];
		$params['sign'] = $this->_genSign($this->api_key, $params['token']);
		$params['reveal_list'] = '1'; // for test()


		// // $params['player_id'] = 352272;
		// $params['token'] = 'c874d8766ad32b1ca4ecfaca171c5d64';
		// $params['sign'] = 'a5c1d640b11348f224452f130e588386';


		$response = $this->_Httpful_post($url, $params);
// $this->utils->debug_log('64.response.raw_body', $response->raw_body);
		$decoded = $this->utils->json_decode_handleErr($response->raw_body, true);

		// $this->test($decoded['success'], true, 'In generateEventUrlWithToken api, success should be true.');
		$this->test($decoded['code'], 200, 'In useinfo api, the success code should be 200.');
		$this->test( !empty($decoded['result']['uid']), true, 'In bet_info api, the uid should be Not Empty.');

		// @TODO: Zero date

	} // EOF bet_amount

	private function bet_amount(){

		$url = $this->api_uri. 'bet_amount';
		$params = [];
		// $params['player_id'] = $this->player_id; // TODO, shoud form $this->return_list_by_method['generateEventUrlWithToken']['raw_token']
		$params['token'] = $this->return_list_by_method['generateEventUrlWithToken']['raw_token'];
		$params['sign'] = $this->_genSign($this->api_key, $params['token']);
		$params['reveal_list'] = '1'; // for test()

		// // $params['player_id'] = 352272;
		// $params['token'] = 'c874d8766ad32b1ca4ecfaca171c5d64';
		// $params['sign'] = 'a5c1d640b11348f224452f130e588386';


		$response = $this->_Httpful_post($url, $params);
// $this->utils->debug_log('64.response.raw_body', $response->raw_body);
		$decoded = $this->utils->json_decode_handleErr($response->raw_body, true);

		// $this->test($decoded['success'], true, 'In generateEventUrlWithToken api, success should be true.');
		$this->test($decoded['code'], 200, 'In useinfo api, the success code should be 200.');
		$this->test( !empty($decoded['result']['uid']), true, 'In bet_info api, the uid should be Not Empty.');

		// @TODO: Zero date

	} // EOF bet_amount

	private function _Httpful_post($url, $params, $cookie = '' ){
		try{
			$addHeaders = [];
			if( !empty($cookie) ){
				$addHeaders['Cookie'] = $cookie;
			}

			$response=\Httpful\Request::post($url, $params)
				->sendsType(\Httpful\Mime::FORM)
				// ->useSocks5Proxy('127.0.0.1','1080')
			    ->expectsPlain()
				->addHeaders( $addHeaders )
			    ->send();
			    // ->expectsJson()
			$this->utils->debug_log('_Httpful_post.url', $url);
			$this->utils->debug_log('_Httpful_post.params', $params);
			$this->utils->debug_log('_Httpful_post.raw_body:',$response->raw_body);
			$success=!$response->hasErrors();
			$this->test($success, true, 'call api. '. var_export([ 'url:', $url
																			, 'params:', $params
																			], true));
		}catch(Exception $e){
			$this->utils->error_log($e, $response->raw_body);
		}
		return $response;
	} // EOF _Httpful_post

}
