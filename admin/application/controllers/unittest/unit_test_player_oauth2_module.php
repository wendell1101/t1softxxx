<?php

trait unit_test_player_oauth2_module{

	public function unit_test_player_oauth2_issue_token(){

        $testConfig=$this->unit_test_config['unit_test_player_oauth2'];
		$clientName=$testConfig['client_name'];
		$clientSecret=$testConfig['client_secret'];
		$username=$testConfig['test_player'];
		$password=$testConfig['test_password'];

		$libPlayerOauth2=$this->_player_oauth2_loadOauth2LibForTesting();
		$this->assertNotNull($libPlayerOauth2);
		$server=['REQUEST_METHOD'=>'POST', 'REQUEST_SCHEME'=>'http', 'HTTP_HOST'=>'localhost',
			'SERVER_PORT'=>80, 'REQUEST_URI'=>'/oauth/token', 'QUERY_STRING'=>null];
		$basicAuth='Basic '.base64_encode($clientName.':'.$clientSecret);
		$headers=['Content-type'=>'application/json', 'Authorization'=>$basicAuth];
		$cookie=[];
		$get=[];
		$post=['grant_type'=>'password', 'username'=>$username, 'password'=>$password];
		$files=[];
		$body=null;
		$request=$libPlayerOauth2->generatePsr7RequestFromArrays($server, $headers, $cookie,
			$get, $post, $files, $body);
		$success=$libPlayerOauth2->issueToken($request, $response);
		$this->utils->debug_log('response', $response, 'success', $success);

		$this->assertTrue($success);
		$this->assertNotNull($response);
		$this->assertTrue($response instanceof \Response);
		$respJson=$response->getBody()->__toString();
		$this->utils->debug_log('response result', $respJson);
		$this->assertTrue(!empty($respJson));
		$json=json_decode($respJson, true);
		$this->assertTrue(!empty($json));
		$this->utils->debug_log('response json', $json);
		$this->assertEquals('Bearer', $json['token_type']);
		$this->assertEquals('read write', $json['scopes']);
		$this->assertNotEmpty($json['access_token']);
		$this->assertNotEmpty($json['refresh_token']);

		return $json;
	}

	public function unit_test_player_oauth2_refresh_token(){

		$jsonToken=$this->unit_test_player_oauth2_issue_token();

		$libPlayerOauth2=$this->_player_oauth2_loadOauth2LibForTesting();
		$this->assertNotNull($libPlayerOauth2);
		$refreshToken=
		$server=['REQUEST_METHOD'=>'POST', 'REQUEST_SCHEME'=>'http', 'HTTP_HOST'=>'localhost',
			'SERVER_PORT'=>80, 'REQUEST_URI'=>'/oauth/token/refresh', 'QUERY_STRING'=>null];
		$clientName='testclient';
		$clientSecret='secret';
		$basicAuth='Basic '.base64_encode($clientName.':'.$clientSecret);
		$headers=['Content-type'=>'application/json', 'Authorization'=>$basicAuth];
		$cookie=[];
		$get=[];
		$post=['grant_type'=>'refresh_token', 'refresh_token'=>$jsonToken['refresh_token']];
		$files=[];
		$body=null;
		$request=$libPlayerOauth2->generatePsr7RequestFromArrays($server, $headers, $cookie,
			$get, $post, $files, $body);
		$success=$libPlayerOauth2->refreshToken($request, $response);
		$this->utils->debug_log('response', $response, 'success', $success);
		if(!$success){
			$this->utils->error_log('error response', $response->getBody()->__toString());
		}
		$this->assertTrue($success);
		$this->assertNotNull($response);
		$this->assertTrue($response instanceof \Response);
		$respJson=$response->getBody()->__toString();
		$this->utils->debug_log('response result', $respJson);
		$this->assertTrue(!empty($respJson));
		$json=json_decode($respJson, true);
		$this->assertTrue(!empty($json));
		$this->utils->debug_log('response json', $json);
		$this->assertEquals('Bearer', $json['token_type']);
		$this->assertEquals('read write', $json['scopes']);
		$this->assertNotEmpty($json['access_token']);
		$this->assertNotEmpty($json['refresh_token']);

        $this->printAssertionSummary();
	}

	public function unit_test_player_oauth2_validateOauth2Token(){
		$jsonToken=$this->unit_test_player_oauth2_issue_token();

		$libPlayerOauth2=$this->_player_oauth2_loadOauth2LibForTesting();
		$this->assertNotNull($libPlayerOauth2);
		$server=['REQUEST_METHOD'=>'POST', 'REQUEST_SCHEME'=>'http', 'HTTP_HOST'=>'localhost',
			'SERVER_PORT'=>80, 'REQUEST_URI'=>'/ping_auth', 'QUERY_STRING'=>null];
		$basicAuth='Bearer '.$jsonToken['access_token'];
		$headers=['Content-type'=>'application/json', 'authorization'=>$basicAuth];
		$cookie=[];
		$get=[];
		$post=[];
		$files=[];
		$body=null;
		$request=$libPlayerOauth2->generatePsr7RequestFromArrays($server, $headers, $cookie,
			$get, $post, $files, $body);
		$errorResponse=null;
		$success=$libPlayerOauth2->validateToken($request, $errorResponse, $username, $oauth_access_token_id);
		$this->utils->debug_log('errorResponse', $errorResponse, 'success', $success);
		if(!$success){
			$this->utils->error_log('error response', $errorResponse->getBody()->__toString());
		}
		$this->assertTrue($success);

		//test failed
		$headers=['Content-type'=>'application/json', 'authorization'=>'Bearer invalidtoken'];
		$request=$libPlayerOauth2->generatePsr7RequestFromArrays($server, $headers, $cookie,
			$get, $post, $files, $body);
		$errorResponse=null;
		$success=$libPlayerOauth2->validateToken($request, $errorResponse, $username, $oauth_access_token_id);
		$this->utils->debug_log('errorResponse', $errorResponse, 'success', $success);
		if(!$success){
			$this->utils->error_log('error response', $errorResponse->getBody()->__toString());
		}
		$this->assertFalse($success);
		$this->assertEquals(401, $errorResponse->getStatusCode());
		$respJson=$errorResponse->getBody()->__toString();
		$this->utils->debug_log('errorResponse result', $respJson);
		$this->assertTrue(!empty($respJson));
		$json=json_decode($respJson, true);
		$this->assertTrue(!empty($json));
		$this->utils->debug_log('errorResponse json', $json);
		$this->assertEquals('access_denied', $json['error']);

        $this->printAssertionSummary();
	}

	protected function _player_oauth2_loadOauth2LibForTesting(){
		$player_oauth2_settings=$this->utils->getConfig('player_oauth2_settings');
		require_once dirname(__FILE__).'/../../'.$player_oauth2_settings['lib_class_path'];
		$libPlayerOauth2=null;
		try{
			$libPlayerOauth2=\LibPlayerOauth2::generateInstance();
		}catch(Exception $e){
			$this->utils->error_log('get lib player oauth2 failed', $e);
			return null;
		}
		return $libPlayerOauth2;
	}

	public function unit_test_player_oauth2_service(){
        $testConfig=$this->unit_test_config['unit_test_player_oauth2'];
		$clientName=$testConfig['client_name'];
		$clientSecret=$testConfig['client_secret'];
		$username=$testConfig['test_player'];
		$password=$testConfig['test_password'];
		$apiUrl=$testConfig['api_url'];
		$post=['grant_type'=>'password', 'username'=>$username, 'password'=>$password];

		//test ping noauth
		$resp = \Httpful\Request::get($apiUrl.'/ping_noauth')
			->expectsType('json')
			->send();
		$this->utils->debug_log('response of ping_noauth', $resp);
		$this->assertEquals(200, $resp->code);
		$jsonObj=$resp->body;
		$this->assertNotEmpty($jsonObj);
		$this->assertTrue($jsonObj->pong);

		//test failed
		$resp = \Httpful\Request::post($apiUrl.'/oauth/token',['grant_type'=>'password', 'username'=>'invaliduser', 'password'=>'invalidpass'], \Httpful\Mime::FORM)
			->authenticateWith($clientName, $clientSecret)
			->expectsType('json')
			->send();

		$this->utils->debug_log('response of failed', $resp);
		$this->assertEquals('invalid_credentials', $resp->body->error);

		//call http , get token
		$resp = \Httpful\Request::post($apiUrl.'/oauth/token',$post,\Httpful\Mime::FORM)
			->authenticateWith($clientName, $clientSecret)
			->expectsType('json')
			->send();

		$this->utils->debug_log('response of get first token', $resp);
		$jsonObj=$resp->body;
		$this->assertEquals('Bearer', $jsonObj->token_type);
		$this->assertNotEmpty($jsonObj->access_token);
		$this->assertNotEmpty($jsonObj->refresh_token);
		$this->assertNotEmpty($jsonObj->request_id);
		$this->assertNotEmpty($jsonObj->cost_ms);
		$this->assertEquals('read write', $jsonObj->scopes);
		$refreshToken=$jsonObj->refresh_token;

		//test failed for refresh token
		$resp = \Httpful\Request::post($apiUrl.'/oauth/token/refresh',
			['grant_type'=>'refresh_token', 'refresh_token'=>'invalidtoken'], \Httpful\Mime::FORM)
			->authenticateWith($clientName, $clientSecret)
			->expectsType('json')
			->send();

		$this->utils->debug_log('response of failed for refresh token', $resp);
		$this->assertEquals('invalid_request', $resp->body->error);
		// try refresh token
		$post=['grant_type'=>'refresh_token', 'refresh_token'=>$refreshToken];
		$resp = \Httpful\Request::post($apiUrl.'/oauth/token/refresh',$post,\Httpful\Mime::FORM)
			->authenticateWith($clientName, $clientSecret)
			->expectsType('json')
			->send();
		$this->utils->debug_log('response of refresh', $resp);
		$jsonObj=$resp->body;
		$this->assertEquals('Bearer', $jsonObj->token_type);
		$this->assertNotEmpty($jsonObj->access_token);
		$this->assertNotEmpty($jsonObj->refresh_token);
		$this->assertNotEmpty($jsonObj->request_id);
		$this->assertNotEmpty($jsonObj->cost_ms);
		$this->assertEquals('read write', $jsonObj->scopes);
		$accessToken=$jsonObj->access_token;

		// try ping auth
		$basicAuth='Bearer '.$accessToken;
		$resp = \Httpful\Request::get($apiUrl.'/ping_auth')
			->addHeader('authorization', $basicAuth)
			->expectsType('json')
			->send();
		$this->utils->debug_log('response of ping auth', $resp);
		$jsonObj=$resp->body;
		$this->assertNotEmpty($jsonObj);
		$this->assertTrue($jsonObj->pong);
		$this->assertTrue($jsonObj->logged);

		//try failed ping auth
		$basicAuth='Bearer invalidtoken';
		$resp = \Httpful\Request::get($apiUrl.'/ping_auth')
			->addHeader('authorization', $basicAuth)
			->expectsType('json')
			->send();
		$this->utils->debug_log('response of ping auth', $resp);
		$jsonObj=$resp->body;
		$this->assertNotEmpty($jsonObj);
		$this->assertEquals('access_denied', $jsonObj->error);
		$this->assertEquals(401, $resp->code);

		// try delete token
		$basicAuth='Bearer '.$accessToken;
		$resp = \Httpful\Request::delete($apiUrl.'/oauth/tokens/self')
			->addHeader('authorization', $basicAuth)
			->send();
		$this->utils->debug_log('response of delete', $resp);
		$this->assertEquals(200, $resp->code);
		// $jsonObj=$resp->body;
		// $this->utils->debug_log('json obj', $jsonObj);

        $this->printAssertionSummary();
	}

	public function unit_test_player_oauth2_service_delete_token(){
        $testConfig=$this->unit_test_config['unit_test_player_oauth2'];
		$clientName=$testConfig['client_name'];
		$clientSecret=$testConfig['client_secret'];
		$username=$testConfig['test_player'];
		$password=$testConfig['test_password'];
		$apiUrl=$testConfig['api_url'];
		$post=['grant_type'=>'password', 'username'=>$username, 'password'=>$password];
		//call http , get token
		$resp = \Httpful\Request::post($apiUrl.'/oauth/token',$post,\Httpful\Mime::FORM)
			->authenticateWith($clientName, $clientSecret)
			->expectsType('json')
			->send();

		$this->utils->debug_log('response of get first token', $resp);
		$jsonObj=$resp->body;
		$this->assertEquals('Bearer', $jsonObj->token_type);
		$this->assertNotEmpty($jsonObj->access_token);
		$this->assertNotEmpty($jsonObj->refresh_token);
		$this->assertNotEmpty($jsonObj->request_id);
		$this->assertNotEmpty($jsonObj->cost_ms);
		$this->assertEquals('read write', $jsonObj->scopes);
		$accessToken=$jsonObj->access_token;

		// try delete token
		$basicAuth='Bearer '.$accessToken;
		$resp = \Httpful\Request::delete($apiUrl.'/oauth/tokens/self')
			->addHeader('authorization', $basicAuth)
			->send();
		$this->utils->debug_log('response of delete', $resp);
		$this->assertEquals(200, $resp->code);
		// $jsonObj=$resp->body;
		// $this->utils->debug_log('json obj', $jsonObj);

        $this->printAssertionSummary();
	}

}
