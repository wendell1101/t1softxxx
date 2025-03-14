<?php
/**
 *   filename:   Getresponse_lib.php
 *   author:     ASRII
 *   date:       2020-05-04
 *   ogp-ticket: OGP-16855
 *   @brief:     library for get response system.
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Getresponse_lib {

	const API_getContacts = "getContacts";
    const API_getContactsByEmail = "getContactsByEmail";
	const API_getContact = "getContact";
    const API_addContact = "addContact";
    const API_updateContact = "updateContact";
    
	const URI_MAP = array(
		self::API_getContact => '/v3/contacts/',
		self::API_getContactsByEmail => '/v3/campaigns/',
		self::API_getContacts => '/v3/contacts',
		self::API_addContact => '/v3/contacts',
		self::API_updateContact => '/v3/contacts/',
	);

	const METHOD = array(
		self::API_getContacts => 'GET',
		self::API_getContactsByEmail => 'GET',
		self::API_addContact => 'POST',
		self::API_updateContact => 'POST'
	);

	const API_SUCCESS_RESPONSE = ['200', '202'];

	const SUCCESS = 0;
	const ERROR_INTERNAL = 1;
	const ERROR_GENERAL = 1000;

	public $iovation_result;

	public function __construct()
    {
    	$this->ci =& get_instance();
		$this->utils=$this->ci->utils;

        $this->config = $this->utils->getConfig('third_party_get_response') ?: [];
		$this->apiUrl = $this->getConfig('api_url','');
		$this->apiKey = $this->getConfig('api_key','');

		$this->result = null;
    }

    ########### START UTIL METHODS

    private function getConfig($configName,$defaultVal='')
    {
		if(isset($this->config[$configName]) && !empty($this->config[$configName])){
			return $this->config[$configName];
		}
    	return $defaultVal;
    }

    private function generateAuthorization(){
    	$authorization = $this->apiKey;
    	return $authorization;
    }

    private function processResponseResult($playerId,$params,$url,$result, $apiMethod)
    {
        $httpStatusCode = (array_key_exists("httpStatusCode", $result))?$result['httpStatusCode']:null;
        $errCode = (array_key_exists("errCode", $result))?$result['errCode']:null;
        $error = (array_key_exists("error", $result))?$result['error']:null;
        $response = (array_key_exists("response", $result))?$result['response']:null;
        $content = (array_key_exists("content", $result))?$result['content']:null;
        $statusText = $errCode.":".$error;

        $extras = ['player_id' => $playerId,
				   'full_url' => $url,
				   'extra' => $response];

		$apiResult = ['type' => $apiMethod,
        		   	  'url' => $url,
        		      'params' => $params,
						 'content' => $content];

		$this->ci->load->model('response_result');
		$flag = Response_result::FLAG_NORMAL;
		if(!in_array($httpStatusCode, self::API_SUCCESS_RESPONSE)){
			$flag = Response_result::FLAG_ERROR;
		}

		
        return $this->ci->response_result->saveResponseResult(IDENTITY_API,
														$flag,
        												$apiMethod,
        												$this->utils->encodeJson($params),
        												$this->utils->encodeJson($apiResult),
        												$httpStatusCode,
        												$statusText,
        												null,
        												$extras);
    }

    private function processApiResult($responseResultId, $params, $status,$response, $apiMethod)
    {
        //add post process here
        return true;
    }

    private function callApi($url, $apiMethod, $params, $extra = [])
    {
    	$success = false;
        try{

			//get method

			$curl_method = self::METHOD[$apiMethod];

	        $ch = curl_init();
	        //$url = $this->getApiUrl($apiMethod, $params);
	        curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, true);
            $header = ['Content-Type: application/json',
            'X-Auth-Token: api-key '.$this->generateAuthorization()];
			if($curl_method=='PUT'){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
            }elseif($curl_method=='POST'){                
				curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			}else{
			}

            curl_setopt($ch, CURLOPT_HTTPHEADER,$header);

	        $response = curl_exec($ch);
	        $errCode = curl_errno($ch);
	        $error = curl_error($ch);
	        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	        $content = substr($response, $header_size);
	        $httpStatusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $this->headers = getallheaders();

	        curl_close($ch);

            $responseCode = 0;
            if(isset($response['code'])){
                $responseCode = $response['code'];
            }

	        $this->utils->debug_log('callApi',
                                    'apiMethod  =========>', $apiMethod,
	        						'Url  =========>', $url,
	        						'Params =======>', $params ,
	        						'Response =====>', $response,
	        						'CURL Method =====>', $curl_method,
	        						'ErrCode ======>', $errCode,
	        						'Error ========>', $error,
	        						'httpStatusCode ===>', $httpStatusCode,
	        						'ResponseCode ===>', $responseCode,
	        						'content ===>', $content);

	        $this->result = $result = ['errCode' => $errCode,
					   'error' => $error,
					   'httpStatusCode' => $httpStatusCode,
					   'responseCode' => $responseCode,
					   'content' => $content,
					   'response' => $this->utils->encodeJson($response)];

			$id=isset($extra['playerId'])?$extra['playerId']:null;

	        $responseResultId = $this->processResponseResult($id,
	        												 $params,
	        												 $url,
															 $result,
															 $apiMethod);
            $successCodes = self::API_SUCCESS_RESPONSE;
            //if($apiMethod==self::API_addContact){
                //$successCodes[] = 409;
            //}


	        if($responseResultId && in_array($httpStatusCode, $successCodes)){
			$this->utils->debug_log('iovation_lib statusCode passed');
	        	if($this->processApiResult($responseResultId, $params, self::SUCCESS,json_decode($content,true), $apiMethod)){
	        		$success = true;
	        	}
	        }else{
				$this->utils->debug_log('getresponselib_lib httpStatusCode failed');
				$this->processApiResult($responseResultId, $params, $httpStatusCode, json_decode($content,true), $apiMethod);
			}
        }catch (Exception $e) {
            $this->utils->error_log('getResponse error', $e->getMessage());
        }
        return $success;
    }

   
    public function callApiGuzzle($apiMethod, $params, $extra = []){
        try{
            $curl_method = self::METHOD[$apiMethod];
            $baseUrl = $this->apiUrl;
            $path = $this->generateUrlNoBaseUrl($apiMethod, $params);
            $guzzle = new GuzzleHttp\Client([
                'headers' => [
                    'X-Auth-Token' => 'api-key '.$this->generateAuthorization(),
                ],
                'stream' => true,
                'timeout' => 0,
                'base_uri' => $baseUrl,
            ]);
            $response = $guzzle->request($curl_method, $path);
            $data = $response->getBody()->getContents();
            $this->result = json_decode($data, true);
            return $this->result;
        }catch (Exception $e) {
            $this->utils->error_log('getResponse callApiGuzzle error', $e->getMessage());
        }
        return false;
    }

    private function getApiUrl($apiMethod, $extra = []){
        
		$base_url = $this->apiUrl;
        $path = $this->generateUrlNoBaseUrl($apiMethod, $extra);
        return $base_url . $path;
    }

    private function generateUrlNoBaseUrl($apiMethod, $extra = []){
        $campaignId = isset($extra['campaignId'])?$extra['campaignId']:null;
        $contactId = isset($extra['contactId'])?$extra['contactId']:null;

		if($apiMethod == self::API_updateContact || $apiMethod == self::API_getContact){
			return self::URI_MAP[$apiMethod].$contactId;
        }elseif($apiMethod == self::API_getContacts){
            return self::URI_MAP[$apiMethod];
        }elseif($apiMethod == self::API_getContactsByEmail){
            unset($extra['campaignId']);
            return self::URI_MAP[$apiMethod].$campaignId.'/contacts?'.http_build_query($extra);
		}else{
			return self::URI_MAP[$apiMethod];
		}
    }

    ########### END UTIL METHODS

    public function addContact($playerId, $params = [])
    {
    	$playerInfo = $this->utils->get_player_info($playerId);
    	$success = false;
    	if($playerInfo){
            //$params = [];
            //$params['playerId'] = $playerId;
            $url = $this->getApiUrl(self::API_addContact, $params);
	    	if($this->callApi($url, self::API_addContact, $params)){
	    		$success = true;
	    	}
    	}else{
    		$this->utils->debug_log('callApi addContact no player details');
    	}
    	return ["success"=>$success, "result"=>$this->result];
	}

    public function updateContact($playerId, $params = [])
    {
    	$playerInfo = $this->utils->get_player_info($playerId);
    	$success = false;
    	if($playerInfo){
            
            $url = $this->getApiUrl(self::API_updateContact, $params);
            unset($params['contactId']);
            
            $this->utils->info_log('========= send_data_to_getreponse updateContact url ============================', 'reponse', $url);
	    	if($this->callApi($url, self::API_updateContact, $params)){
	    		$success = true;
	    	}
    	}else{
    		$this->utils->debug_log('callApi updateContact no player details');
    	}
    	return ["success"=>$success, "result"=>$this->result];
	}

    //https://medium.com/@matriphe/streaming-csv-using-php-46c717e33d87
    //https://www.binarytides.com/php-fetch-gzipped-content-over-http-with-file_get_contents/
    public function getContactByEmail($params = [])
    {
         $success = false;

        if($this->callApiGuzzle(self::API_getContactsByEmail, $params)){
            $success = true;
        }
    	return ["success"=>$success, "result"=>$this->result];
	}

    public function getContact($params = [])
    {
         $success = false;
        if($this->callApi(self::API_getContacts, $params)){
            $success = true;
        }
    	return ["success"=>$success, "result"=>$this->result];
	}

    /*public function testGetData()
    {
        
    $url = 'https://api.getresponse.com/v3/campaigns/T/contacts?query[email]=bermarbalibalos4@gmail.com';
        
    $opts = array(
		'http' => array(
			'method'=> "GET",
			'X-Auth-Token'=>"api-key uk9ug8254ux0vjz9p3oyzs6p1t5nkpun"
		)
	);

	$context = stream_context_create($opts);
	$content = file_get_contents($url ,false,$context); 
	
	//If http response header mentions that content is gzipped, then uncompress it
	foreach($http_response_header as $c => $h)
	{
		if(stristr($h, 'content-encoding') and stristr($h, 'gzip'))
		{
			//Now lets uncompress the compressed data
			$content = gzinflate( substr($content,10,-8) );
		}
	}
	
	var_dump($content);
    }

    public function testGetDataSocket(){

        $url = 'api.getresponse.com';
        $pullurl = '/v3/campaigns/T/contacts?query[email]=bermarbalibalos4@gmail.com';
        
   
        $fp = fsockopen($url, 80, $errno, $errstr, 20);
        if (!$fp) {
            echo "$errstr ($errno)<br>";
        } else {
            $out = "GET $pullurl HTTP/1.1\r\n";
            $out .= "Host: $url\r\n";
            $out .= "X-Auth-Token: api-key uk9ug8254ux0vjz9p3oyzs6p1t5nkpun\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            while (!feof($fp)) {
                echo '======'.fgets($fp, 128);
                //print fread($fp,256);
            }
        }

        fclose($fp);

    }*/



}