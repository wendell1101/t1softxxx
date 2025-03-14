<?php
/**
 *   filename:   iovation_lib.php
 *   author:     ASRII
 *   date:       2020-05-04
 *   ogp-ticket: OGP-16855
 *   @brief:     library for iovation system.
 *   			  - For New Player Registration.
 *   			  (Note: Won't do anything at exist players.)
 *   			  - To Send Username, Registered IP & Blackbox to IOVATION
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Iovation_lib {

    public $use_logs_monthly_table;

	const API_registration = "registration";
	const API_affiliateRegistration = "affiliateRegistration";
	const API_resendAffiliateRegistration = "resendAffiliateRegistration";
	const API_resendAffiliateLogin = "resendAffiliateLogin";
	const API_affiliateLogin = "affiliateLogin";
	const API_promotion = "promotion";
	const API_depositSelectPromotion = "depositSelectPromotion";
	const API_resendRegistration = "resendRegistration";
	const API_resendPromotion = "resendPromotion";
	const API_addEvidence = "addEvidence";
	const API_updateEvidence = "updateEvidence";
	const API_retractEvidence = "retractEvidence";
    const API_playerLogin = "playerLogin";
    const API_resendPlayerLogin = "resendPlayerLogin";

	const EVIDENCE_TYPES = array(
		'1-1' =>'iovation.evidencetype1.1',
		'1-2' => 'iovation.evidencetype1.2',
		'1-3' => 'iovation.evidencetype1.3',
		'1-4' => 'iovation.evidencetype1.4',
		'1-5' => 'iovation.evidencetype1.5',
		'1-6' => 'iovation.evidencetype1.6',
		'1-7' => 'iovation.evidencetype1.7',
		'1-8' => 'iovation.evidencetype1.8',
		'1-9' => 'iovation.evidencetype1.9',
		'1-10' => 'iovation.evidencetype1.10',
		'1-11' => 'iovation.evidencetype1.11',
		'1-12' => 'iovation.evidencetype1.12',
		'2-1' => 'iovation.evidencetype2.1',
		'2-2' => 'iovation.evidencetype2.2',
		'2-3' => 'iovation.evidencetype2.3',
		'2-4' => 'iovation.evidencetype2.4',
		'3-1' => 'iovation.evidencetype3.1',
		'3-2' => 'iovation.evidencetype3.2',
		'3-3' => 'iovation.evidencetype3.3',
		'3-4' => 'iovation.evidencetype3.4',
		'3-5' => 'iovation.evidencetype3.5',
		'3-51' => 'iovation.evidencetype3.51',
		'3-52' => 'iovation.evidencetype3.52',
		'3-53' => 'iovation.evidencetype3.53',
		'3-54' => 'iovation.evidencetype3.54',
		'3-55' => 'iovation.evidencetype3.55',
		'3-56' => 'iovation.evidencetype3.56',
		'3-57' => 'iovation.evidencetype3.57',
		'3-58' => 'iovation.evidencetype3.58',
		'3-59' => 'iovation.evidencetype3.59',
		'3-6' => 'iovation.evidencetype3.6',
		'3-7' => 'iovation.evidencetype3.7',
		'3-8' => 'iovation.evidencetype3.8',
		'3-9' => 'iovation.evidencetype3.9',
		'3-10' => 'iovation.evidencetype3.10',
		'3-11' => 'iovation.evidencetype3.11',
		'3-12' => 'iovation.evidencetype3.12',
		'4-1' => 'iovation.evidencetype4.1',
		'4-2' => 'iovation.evidencetype4.2',
		'4-3' => 'iovation.evidencetype4.3',
		'4-4' => 'iovation.evidencetype4.4',
		'4-5' => 'iovation.evidencetype4.5',
		'5-1' => 'iovation.evidencetype5.1',
		'5-2' => 'iovation.evidencetype5.2',
		'5-3' => 'iovation.evidencetype5.3',
		'5-4' => 'iovation.evidencetype5.4',
		'5-5' => 'iovation.evidencetype5.5',
		'5-6' => 'iovation.evidencetype5.6',
		'5-7' => 'iovation.evidencetype5.7',
		'10-1' => 'iovation.evidencetype10.1',
		'10-2' => 'iovation.evidencetype10.2',
		'10-3' => 'iovation.evidencetype10.3',
		'10-4' => 'iovation.evidencetype10.4',
		'10-5' => 'iovation.evidencetype10.5',
		'10-6' => 'iovation.evidencetype10.6',
		'99-1' => 'iovation.evidencetype99.1',
		'99-2' => 'iovation.evidencetype99.2',
		'99-3' => 'iovation.evidencetype99.3',
		'100-1' => 'iovation.evidencetype100.1',
	);

	const URI_MAP = array(
		self::API_registration => '/checks',
		self::API_affiliateRegistration => '/checks',
		self::API_resendAffiliateRegistration => '/checks',
		self::API_resendAffiliateLogin => '/checks',
		self::API_affiliateLogin => '/checks',
		self::API_promotion => '/checks',
		self::API_depositSelectPromotion => '/checks',
		self::API_addEvidence => '/evidence',
		self::API_updateEvidence => '/evidence',
		self::API_resendRegistration => "/checks",
		self::API_resendPromotion => "/checks",
		self::API_retractEvidence => '/evidence',
		self::API_playerLogin => '/checks',
		self::API_resendPlayerLogin => '/checks',
	);

	const METHOD = array(
		self::API_registration => 'POST',
		self::API_affiliateRegistration => 'POST',
		self::API_resendAffiliateRegistration => 'POST',
		self::API_resendAffiliateLogin => 'POST',
		self::API_affiliateLogin => 'POST',
		self::API_promotion => 'POST',
		self::API_depositSelectPromotion => 'POST',
		self::API_resendRegistration => 'POST',
		self::API_resendPromotion => 'POST',
		self::API_addEvidence => 'POST',
		self::API_updateEvidence => 'PUT',
		self::API_retractEvidence => 'POST',
		self::API_playerLogin => 'POST',
		self::API_resendPlayerLogin => 'POST',
	);

	const API_SUCCESS_RESPONSE = ['200', '201', '204'];

	const SUCCESS = 0;
	const ERROR = 1;

	const EVIDENCE_CREATED = 0;
	const EVIDENCE_UPDATED = 1;
	const EVIDENCE_RETRACTED = 2;

	public $iovation_result;

	public function __construct()
    {
    	$this->ci =& get_instance();
		$this->utils=$this->ci->utils;

        $this->config = $this->utils->getConfig('iovation') ?: [];
        $this->subscriberId = $this->getIovationConfig('subscriber_id','');
        $this->subscriberAccount = $this->getIovationConfig('subscriber_account','');
        $this->subscriberPasscode = $this->getIovationConfig('subscriber_passcode','');
		$this->endpointType = $this->getIovationConfig('endpoint_type','');
		$this->apiUrl = $this->getIovationConfig('api_url','');
		$this->prefix = $this->getIovationConfig('prefix','');
		$this->isReady = $this->getIovationConfig('is_api_ready',false);
		$this->use_mtls = $this->getIovationConfig('use_mtls',false);
		$this->tls_version = $this->getIovationConfig('tls_version',CURL_SSLVERSION_TLSv1_2);
		$this->certificate = $this->getIovationConfig('certificate','iovation.pem');
		$this->private_key = $this->getIovationConfig('private_key','iovation_private.key');
		$this->certificate_path = $this->getIovationConfig('certificate_path','../../secret_keys/');
		$this->use_logs_monthly_table = $this->getIovationConfig('use_logs_monthly_table', false);
		

		$this->defaultIntegrationPoint = $this->getIovationConfig('default_integration_point','registration');
		$this->registrationIntegrationPoint = $this->getIovationConfig('registration_integration_point','registration');
		$this->affiliateRegistrationIntegrationPoint = $this->getIovationConfig('registration_integration_point','registration');
		$this->promotionIntegrationPoint = $this->getIovationConfig('promotion_integration_point','promotion');

		$this->evidence_appliedto = $this->getIovationConfig('evidence_appliedto','account');
		$this->evidenceId = '';

		$this->iovation_result = null;

        if ($this->isReady) {
            $this->ci->load->model(['iovation_logs']);

            if ($this->use_logs_monthly_table) {
                $this->ci->iovation_logs->initializeIovationLogsYearMonthTables();
            }
        }
    }

    private function getIovationConfig($configName,$defaultVal='')
    {
		if(isset($this->config[$configName]) && !empty($this->config[$configName])){
			return $this->config[$configName];
		}
    	return $defaultVal;
    }

	public function getAccountCode($username, $type='player'){
		if($type=='affiliate'){
			return $this->prefix.'-aff-'.$username;
		}else{
			return $this->prefix.$username;
		}
	}

    public function registerToIovation($params, $type = self::API_registration)
    {
    	$playerInfo = $this->utils->get_player_info($params['player_id']);
    	$success = false;
    	if($playerInfo){
    		$playerCredentials = [
	    		"username" => $playerInfo['username'],
	    		"playerId" => $playerInfo['playerId'],
	    		"secureId" => $playerInfo['secure_id']?:null,
	    	];
	    	$_params = [
	    		"accountCode" => $this->prefix.$playerInfo['username'],
	    		"statedIp" => $params['ip'],
				"type" => $this->getIntegrationPoint($type),
	    		"blackbox" => $params['blackbox'],
			];

	    	if($this->callIovationApi($type, $playerCredentials,$_params,$params)){
	    		$success = true;
	    		$msg = lang("Iovation is Successfully Registered");
	    	}else{
	    		$msg = lang("Iovation Failed to Register, pls check response result logs!");
	    	}
    	}else{
    		$this->utils->debug_log('CallIovationApi registerToIovation no player details');
    		$msg = lang("No Player Id");
    	}
    	return ["success"=>$success, "iovation_result"=>$this->iovation_result,"msg"=>$msg];
	}

    public function registerPromotionToIovation($params, $type = self::API_promotion)
    {
    	$playerInfo = $this->utils->get_player_info($params['player_id']);
    	$success = false;
    	if($playerInfo){
    		$playerCredentials = [
	    		"username" => $playerInfo['username'],
	    		"playerId" => $playerInfo['playerId'],
	    		"secureId" => $playerInfo['secure_id']?:null,
	    	];
	    	$_params = [
	    		"accountCode" => $this->prefix.$playerInfo['username'],
	    		"statedIp" => $params['ip'],
				"type" => $this->getIntegrationPoint($type),
	    		"blackbox" => $params['blackbox'],
			];

	    	if($this->callIovationApi($type, $playerCredentials,$_params,$params)){
	    		$success = true;
	    		$msg = lang("Iovation join Promotion is Successfully Registered");
	    	}else{
	    		$msg = lang("Iovation join Promotion Failed to Register, pls check response result logs!");
	    	}
    	}else{
    		$this->utils->debug_log('CallIovationApi registerPromotionToIovation no player details');
    		$msg = lang("No Player Id");
    	}
    	return ["success"=>$success, "iovation_result"=>$this->iovation_result,"msg"=>$msg];
	}

    public function registerAffiliateToIovation($params, $type=self::API_affiliateRegistration)
    {
    	$affInfo = $this->utils->get_affiliate_info($params['affiliate_id']);
    	$success = false;
    	if($affInfo){
    		$affCredentials = [
	    		"username" => $affInfo['username'],	  
	    		"affId" => $affInfo['affiliateId'],
	    	];
	    	$_params = [
	    		"accountCode" => $this->prefix.'-aff-'.$affInfo['username'],
	    		"statedIp" => $params['ip'],
				"type" => $this->getIntegrationPoint($type),
	    		"blackbox" => $params['blackbox'],
				"affiliate_id" => $affInfo['affiliateId'],
			];

	    	if($this->callIovationApi($type, $affCredentials,$_params,$params)){
	    		$success = true;
	    		$msg = lang("Iovation is Successfully Registered");
	    	}else{
	    		$msg = lang("Iovation Failed to Register, pls check response result logs!");
	    	}
    	}else{
    		$this->utils->error_log('CallIovationApi registerToIovation no affiliate details', $affInfo , $params);
    		$msg = lang("No Affiliate Id");
    	}
    	return ["success"=>$success, "iovation_result"=>$this->iovation_result,"msg"=>$msg];
	}

	public function getIntegrationPoint($type=null){
		if($type==self::API_promotion || $type==self::API_resendPromotion || $type==self::API_depositSelectPromotion){
			return $this->promotionIntegrationPoint;
		}elseif($type==self::API_registration || $type==self::API_resendRegistration || $type==self::API_playerLogin || $type==self::API_resendPlayerLogin){
			return $this->registrationIntegrationPoint;
		}elseif($type==self::API_affiliateRegistration || $type==self::API_affiliateLogin || $type==self::API_resendAffiliateRegistration || $type==self::API_resendAffiliateLogin){
			return $this->affiliateRegistrationIntegrationPoint;
		}else{
			return $this->defaultIntegrationPoint;
		}
	}

	public function getIntegrationType($type=null){
	    if($type==self::API_depositSelectPromotion){
            return 'depositSelectPromotion';
        }elseif($type==self::API_promotion || $type==self::API_resendPromotion){
			return 'promotion';
		}elseif($type==self::API_registration || $type==self::API_resendRegistration){
			return 'registration';
		}elseif($type==self::API_affiliateRegistration){
			return 'affiliateRegistration';
		}elseif($type==self::API_resendAffiliateRegistration){
			return 'resendAffiliateRegistration';		
		}elseif($type==self::API_resendAffiliateLogin){
			return 'resendAffiliateLogin';		
		}elseif($type==self::API_affiliateLogin){
			return 'affiliateLogin';
		}elseif($type==self::API_playerLogin){
			return 'playerLogin';
		}elseif($type==self::API_resendPlayerLogin){
			return 'resendPlayerLogin';
		}else{
			return 'registration';
		}
	}

	public function resendToIovation($params, $apiMethod = self::API_resendRegistration)
    {
    	$playerInfo = $this->utils->get_player_info($params['player_id']);
    	$success = false;
    	if($playerInfo){
    		$playerCredentials = [
	    		"username" => $this->prefix.$playerInfo['username'],
	    		"playerId" => $playerInfo['playerId'],
	    		"secureId" => $playerInfo['secure_id']?:null,
	    	];
	    	$_params = [
	    		"accountCode" => $params['account_code'],
	    		"statedIp" => $params['ip'],
				"type" => $this->getIntegrationPoint($params['type']),
	    		"blackbox" => $params['blackbox'],
			];

			$result = false;

            if (empty($apiMethod)) {
				$apiMethod = self::API_resendRegistration;
			}

			if($params['type']=='promotion' || $params['type']=='depositSelectPromotion'){
				$apiMethod = self::API_resendPromotion;
			}
			$result = $this->callIovationApi($apiMethod, $playerCredentials,$_params,$params);

	    	if($result){
	    		$success = true;
	    		$msg = lang("Iovation is Successfully Resend");
	    	}else{
	    		$msg = lang("Iovation Failed to Resend, pls check response result logs!");
	    	}
    	}else{
    		$this->utils->debug_log('CallIovationApi sendToIvation no player details');
    		$msg = lang("No Player Id");
    	}
    	return ["success"=>$success,"msg"=>$msg];
    }

	public function resendAffiliateToIovation($params, $apiMethod = self::API_resendAffiliateRegistration)
    {
    	$affInfo = $this->utils->get_affiliate_info($params['affiliate_id']);
    	$success = false;
    	if($affInfo){
			if(empty($apiMethod)){
				$apiMethod = self::API_resendAffiliateRegistration;
			}
    		$playerCredentials = [
	    		"username" => $this->prefix.$affInfo['username'],
	    		"affId" => $affInfo['affiliateId'],
	    		
	    	];
	    	$_params = [
	    		"accountCode" => $params['account_code'],
	    		"statedIp" => $params['ip'],
				"type" => $this->getIntegrationPoint($apiMethod),
	    		"blackbox" => $params['blackbox'],
			];

			$result = false;
			$result = $this->callIovationApi($apiMethod, $playerCredentials,$_params,$params);

	    	if($result){
	    		$success = true;
	    		$msg = lang("Iovation is Successfully Resend");
	    	}else{
	    		$msg = lang("Iovation Failed to Resend, pls check response result logs!");
	    	}
    	}else{
    		$this->utils->debug_log('CallIovationApi sendToIvation no player details');
    		$msg = lang("No Player Id");
    	}
    	return ["success"=>$success,"msg"=>$msg];
    }

    public function sendEvidenceToIovation($params)
    {
		$playerCredentials = [
			"username" => null,#$this->prefix.$playerInfo['username'],
			"playerId" => null,#$playerInfo['playerId'],
			"secureId" => null,#$playerInfo['secure_id']?:null,
		];
		$_params = [
			"evidenceType" => $params['evidence_type'],
			"comment" => $params['comment'],
			"appliedTo" => $params['applied_to'],
		];
		if($this->callIovationApi(self::API_addEvidence, $playerCredentials,$_params,$params)){
			$success = true;
			$msg = lang("Iovation is Successfully added evidence");
		}else{
			$success = false;
			$msg = lang("Iovation Failed to add Evidence, pls check response result logs!");
		}

    	return ["success"=>$success,"msg"=>$msg];
	}

    public function updateEvidenceToIovation($params)
    {
    	$this->evidenceId = $params['evidence_id'];

		$playerCredentials = [
			"username" => null,#$this->prefix.$playerInfo['username'],
			"playerId" => null,#$playerInfo['playerId'],
			"secureId" => null,#$playerInfo['secure_id']?:null,
		];
		$_params = [
			"id" => $this->evidenceId,
			"evidenceType" => $params['evidence_type'],
			"comment" => $params['comment'],
			"appliedTo" => $params['applied_to'],
		];
		$this->utils->debug_log('iovation_lib processApiResult:_params',$_params);

		if($this->callIovationApi(self::API_updateEvidence, $playerCredentials,$_params,$params)){
			$success = true;
			$msg = lang("Iovation is Successfully updated evidence");
		}else{
			$msg = lang("Iovation Failed to updated Evidence, pls check response result logs!");
		}

    	return ["success"=>$success,"msg"=>$msg];
	}

	public function retractEvidence($params)
    {
    	$this->evidenceId = $params['evidence_id'];

		$playerCredentials = [
			"username" => null,#$playerInfo['username'],
			"playerId" => null,#$playerInfo['playerId'],
			"secureId" => null,#$playerInfo['secure_id']?:null,
		];
		$_params = [
			"comment" => $params['comment'],
		];
		$this->utils->debug_log('iovation_lib processApiResult:_params',$_params);

		if($this->callIovationApi(self::API_retractEvidence, $playerCredentials,$_params,$params)){
			$success = true;
			$msg = lang("iovation_evidence.iovation_is_successfully_retracting_evidence");
		}else{
			$msg = lang("iovation_evidence.iovation_is_failed_retracting_evidence");
		}

    	return ["success"=>$success,"msg"=>$msg];
	}

    private function getApiUrl($apiMethod, $extra = []){
		$base_url = $this->apiUrl."/".$this->subscriberId;

		if($apiMethod == self::API_updateEvidence){
			return $base_url.self::URI_MAP[$apiMethod].'/'.$this->evidenceId;
		}elseif($apiMethod == self::API_retractEvidence){
			return $base_url.self::URI_MAP[$apiMethod].'/'.$this->evidenceId.'/retractions';
		}else{
			return $base_url.self::URI_MAP[$apiMethod];
		}
    }

    private function callIovationApi($apiMethod, $playerCredentials,$params,$rawParams=[])
    {
    	$success = false;
        try{

			//get method

			$curl_method = self::METHOD[$apiMethod];


	        $ch = curl_init();
	        $url = $this->getApiUrl($apiMethod);
	        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl($apiMethod));
			curl_setopt($ch, CURLOPT_HEADER, true);
			if($curl_method=='PUT'){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			}else{
				curl_setopt($ch, CURLOPT_POST, true);
			}

			$cert = APPPATH . $this->certificate_path.$this->certificate;
			$key = APPPATH . $this->certificate_path.$this->private_key;

			$this->utils->debug_log('CallIovationApi use_mtls',
			'cert  =========>', $cert,
			'key =======>', $key ,
			'tls_version =====>', $this->tls_version);

			if($this->use_mtls){
				curl_setopt($ch, CURLOPT_SSLVERSION, $this->tls_version);
				curl_setopt($ch, CURLOPT_SSLCERT, $cert);
				curl_setopt($ch, CURLOPT_SSLKEY, $key);
			}

	        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_HTTPHEADER,['Content-Type: application/json',
	        									 'Authorization: Basic '.$this->generateAuthorization()]);



	        $response = curl_exec($ch);
	        $errCode = curl_errno($ch);
	        $error = curl_error($ch);
	        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	        $header = substr($response, 0, $header_size);
	        $content = substr($response, $header_size);
	        $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	        $statusText  = $errCode . ':' . $error;
	        curl_close($ch);

	        $this->utils->debug_log('CallIovationApi',
	        						'Url  =========>', $url,
	        						'Params =======>', $params ,
	        						'Response =====>', $response,
	        						'ErrCode ======>', $errCode,
	        						'Error ========>', $error,
	        						'StatusCode ===>', $statusCode);

	        $result = ['errCode' => $errCode,
					   'error' => $error,
					   'statusCode' => $statusCode,
					   'content' => $content,
					   'response' => $this->utils->encodeJson($response)];

			$id=isset($playerCredentials['playerId'])?$playerCredentials['playerId']:null;
			$secureId=isset($playerCredentials['secureId'])?$playerCredentials['secureId']:null;

	        $responseResultId = $this->processResponseResult($id,
															$secureId,
	        												 $params,
	        												 $url,
															 $result,
															 $apiMethod);

			$this->utils->debug_log('iovation_lib content',$content);
			$this->utils->debug_log('iovation_lib statusCode',$statusCode);
	        if($responseResultId && in_array($statusCode, self::API_SUCCESS_RESPONSE)){
			$this->utils->debug_log('iovation_lib statusCode passed');
	        	if($this->processApiResult($responseResultId, $params, self::SUCCESS,json_decode($content,true), $rawParams, $apiMethod)){
	        		$success = true;
	        	}
	        }else{
				$this->utils->debug_log('iovation_lib statusCode failed');
				$this->processApiResult($responseResultId, $params, self::ERROR, [], $rawParams, $apiMethod);
			}
        }catch (Exception $e) {
            $this->utils->error_log('error', $e);
        }
        return $success;
    }

    private function processResponseResult($playerId,$playerSecureId,$params,$url,$result, $apiMethod)
    {
        $statusCode = (array_key_exists("statusCode", $result))?$result['statusCode']:null;
        $errCode = (array_key_exists("errCode", $result))?$result['errCode']:null;
        $error = (array_key_exists("error", $result))?$result['error']:null;
        $response = (array_key_exists("response", $result))?$result['response']:null;
        $content = (array_key_exists("content", $result))?$result['content']:null;
        $statusText = $errCode.":".$error;

        $extras = ['player_id' => $playerId,
				   'related_id1' => $playerId,
				   'related_id2' => $playerSecureId,
				   'full_url' => $url,
				   'extra' => $response];

		$apiResult = ['type' => $apiMethod,
        		   	  'url' => $url,
        		      'params' => $params,
						 'content' => $content];
		//$apiResult = $content;
		$this->ci->load->model('response_result');
		$flag = Response_result::FLAG_NORMAL;
		if(!in_array($statusCode, self::API_SUCCESS_RESPONSE)){
			$flag = Response_result::FLAG_ERROR;
		}

		
        return $this->ci->response_result->saveResponseResult(IDENTITY_API,
														$flag,
        												$apiMethod,
        												$this->utils->encodeJson($params),
        												$this->utils->encodeJson($apiResult),
        												$statusCode,
        												$statusText,
        												null,
        												$extras);
    }

    private function processApiResult($responseResultId, $params, $status,$response,$rawParams=[], $apiMethod)
    {
		$this->utils->debug_log('iovation_lib processApiResult:responseResultId',$responseResultId);
		$this->utils->debug_log('iovation_lib processApiResult:params',$params);
		$this->utils->debug_log('iovation_lib processApiResult:status',$status);
		$this->utils->debug_log('iovation_lib processApiResult:response',$response);
		$this->utils->debug_log('iovation_lib processApiResult:rawParams',$rawParams);
		$this->utils->debug_log('iovation_lib processApiResult:apiMethod',$apiMethod);

		if($apiMethod==self::API_registration ||
		$apiMethod==self::API_resendRegistration ||
		$apiMethod==self::API_promotion ||
		$apiMethod==self::API_depositSelectPromotion ||
		$apiMethod==self::API_resendPromotion ||
		$apiMethod==self::API_affiliateRegistration ||
		$apiMethod==self::API_affiliateLogin ||
		$apiMethod==self::API_resendAffiliateRegistration ||
		$apiMethod==self::API_resendAffiliateLogin ||
		$apiMethod==self::API_playerLogin ||
        $apiMethod==self::API_resendPlayerLogin){

			if($status==1){
				$response = [
					'id'=>0,
					'result'=>'R',
					'statedIp'=>$params['statedIp'],
					'account_code'=>$params['accountCode']
				];
			}

			//save raw details except blackbox
			$details_arr = $response?(array)$response:[];
			$rawParamsTemp = $rawParams;
			if(isset($rawParamsTemp['blackbox'])){
				unset($rawParamsTemp['blackbox']);
			}
			$details_arr['raw_request_params'] = $rawParamsTemp;

			$this->iovation_result = isset($response['result'])?$response['result']:null;

			$data = [
				'response_id' => isset($response['id'])?$response['id']:null,
				'result' => isset($response['result'])?$response['result']:null,
				'stated_ip' => isset($response['statedIp'])?$response['statedIp']:null,
				'account_code' => isset($response['accountCode'])?$response['accountCode']:$params['accountCode'],
				'tracking_number' => isset($response['trackingNumber'])?$response['trackingNumber']:null,
				'response_result_id' => $responseResultId,
				'status' => $status,
				'type' => $this->getIntegrationType($apiMethod),
				'user_type' => 'player',
				'blackbox' => isset($params['blackbox'])?$params['blackbox']:null,
				'player_id' => isset($rawParams['player_id'])?$rawParams['player_id']:null,
				//'details' => $response?$this->utils->encodeJson($response):null,
				'details' => $this->utils->encodeJson($details_arr),
			];

			if($apiMethod==self::API_affiliateRegistration ||
			$apiMethod==self::API_affiliateLogin ||
			$apiMethod==self::API_resendAffiliateRegistration ||
			$apiMethod==self::API_resendAffiliateLogin){
				$data['user_type'] = 'affiliate';
				$data['player_id'] =  isset($rawParams['affiliate_id'])?$rawParams['affiliate_id']:null;
			}

            if ($this->use_logs_monthly_table) {
                $table_name = $this->ci->iovation_logs->getCurrentYearMonthTable();
            } else {
                $table_name = $this->ci->iovation_logs->tableName;
            }

			$this->ci->load->model('player_model');
			if($apiMethod==self::API_registration || 
			$apiMethod==self::API_promotion || 
			$apiMethod==self::API_depositSelectPromotion ||
			$apiMethod==self::API_affiliateRegistration ||
			$apiMethod==self::API_affiliateLogin ||
            $apiMethod==self::API_playerLogin){
				return $this->ci->player_model->insertData($table_name, $data);
			}else{
				if(isset($rawParams['log_id'])){
					$result = $this->ci->player_model->updateData('id', $rawParams['log_id'], $table_name, $data);

                    // check previous table
                    if (!$result && $this->use_logs_monthly_table) {
                        $result = $this->ci->player_model->updateData('id', $rawParams['log_id'], $this->ci->iovation_logs->getPreviousYearMonthTable(), $data);
                    }

                    return $result;
				}else{
					return false;
				}
			}
		}elseif($apiMethod==self::API_addEvidence || $apiMethod==self::API_updateEvidence){
			$this->utils->debug_log('iovation_lib',$response);
			$data = [
				'evidence_id' => isset($response['id'])?$response['id']:null,
				'evidence_type' => isset($rawParams['evidence_type'])?$rawParams['evidence_type']:null,
				'comment' => isset($response['comment'])?$response['comment']:null,
				'applied_to' => isset($response['appliedTo'])?json_encode($response['appliedTo']):null,
				'account_code' => isset($rawParams['account_code'])?$rawParams['account_code']:null,
				'device_alias' => isset($rawParams['device_alias'])?$rawParams['device_alias']:null,
				'applied_to_type' => isset($rawParams['applied_to_type'])?$rawParams['applied_to_type']:null,
				'player_id' => isset($rawParams['player_id'])?$rawParams['player_id']:null,
				'affiliate_id' => isset($rawParams['affiliate_id'])?$rawParams['affiliate_id']:null,
				'user_type' => isset($rawParams['user_type'])?$rawParams['user_type']:null,
				'response' => $response?$this->utils->encodeJson($response):null,
				'response_result_id' => $responseResultId,
				'status' => $status,
				'evidence_status' => self::EVIDENCE_CREATED,
			];
			$this->utils->debug_log('iovation_lib $data',$data);
			if($status == 0){
				$this->ci->load->model('player_model');
				if($apiMethod==self::API_addEvidence){
					$this->utils->debug_log('iovation_lib $data add',$data);
					return $this->ci->player_model->insertData("iovation_evidence",$data);
				}else{
					if(isset($rawParams['evidence_id'])){
						$data['evidence_status'] = self::EVIDENCE_CREATED;
						unset($data['user_type']);
						unset($data['affiliate_id']);
						unset($data['player_id']);
						$this->utils->debug_log('iovation_lib $data update',$data);
						return $this->ci->player_model->updateData('evidence_id', $rawParams['evidence_id'], 'iovation_evidence', $data);
					}else{
						return false;
					}
				}
			}

		}elseif($apiMethod==self::API_retractEvidence){
			$this->utils->debug_log('iovation_lib API_retractEvidence',$status);
			if($status == 0){
				$data = [];
				$data['evidence_status'] = self::EVIDENCE_RETRACTED;
				//$data['retract_comment'] = isset($rawParams['comment'])?$rawParams['comment']:null;

				$this->utils->debug_log('iovation_lib API_retractEvidence:$data',$data);
				return $this->ci->player_model->updateData('evidence_id', $rawParams['evidence_id'], 'iovation_evidence', $data);
			}else{
				return false;
			}
		}else{
			return false;
		}
    }

    /*
     *	Authorization
	 *	iovation uses Basic Access Authentication to authenticate API requests.
	 *	Format user names as follows:
	 *		<subscriber ID>/<subscriber account>
	 *	For example: 1000/OLTP
	 *
	 *  Pass the user name and password via the Authorization header.
	 *  The password is your subscriber passcode.
	 *
	 *  The header value should be formatted as follows:
	 *  Basic (Base64 encoded string of <user name>:<password>)
     */
    private function generateAuthorization(){
    	$account = $this->subscriberId.'/'.$this->subscriberAccount;
    	$authorization = base64_encode($account.':'.$this->subscriberPasscode);
    	$this->utils->debug_log('CallIovationApi Authorization Header ======>', $authorization);
    	return $authorization;
    }


	public function checkIovationParamsValid($params, $type){
		$isIovationEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', $type) && $this->isReady;
		if(!$isIovationEnabled){
			return true;
		}

		$ioBlackBox = isset($params['ioBlackBox'])?$params['ioBlackBox']:null;
		if(empty($ioBlackBox)){			
			return false;				
		}

		return true;
	}

    public function getEvidenceDesc($key){        
        foreach(self::EVIDENCE_TYPES as $eKey=> $type){
            $this->utils->error_log('bermar', 'eKey', $eKey, 'key', $key);
            if($eKey == $key){
                return $type;
            }
        }
        return null;
    }
}