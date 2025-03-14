<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
 /*********
 API DOCS: FROM EBET
 API VER: Unkown
 sample extra info 
 {
    "channelId": "23",
    "live": "1",
    "thirdParty": "im_one",
    "tag": "im_one_mwg_dlcity",
    "public_key": "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQChsC0txmvOIYzo7hUHkHjZS0Gmpay35eS4iFmCk0GxL84myoJbPBoOd1iVVwyB1lRvTe46CSBbT7MAH4nsUmSawiWI0Glnx2iPV+zrTbO4pvT4GpTrF/P6O3pBoRQKofIETt00KcVI22kfsUNy3dAhgzrNX90uPMaWHsbbSm19gwIDAQAB",
    "private_key": "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKGwLS3Ga84hjOjuFQeQeNlLQaalrLfl5LiIWYKTQbEvzibKgls8Gg53WJVXDIHWVG9N7joJIFtPswAfiexSZJrCJYjQaWfHaI9X7OtNs7im9PgalOsX8/o7ekGhFAqh8gRO3TQpxUjbaR+xQ3Ld0CGDOs1f3S48xpYexttKbX2DAgMBAAECgYBAKP7kiTZCR8H4tHEX3VZ3M4vLqzaMiudl6oVKcVDCFOxDLFzqA0F7uBQovrAx3XEH8e60jOaQFWI8jEdYxaExvmQlOCnodIc8+rsPO6DJpfVIHbU/pYfBj0Hhn4Wosp5/ATjAcHmjN1MGy3zG4TKPpDS7xpZXBYsQUGLpCRgRQQJBANZw9boQHV6vzwUjaOKv1+xUz+JYiZBkZuxqE9slXZyGL/51O03civsJS0N+zt6MpYY0dzb1epDnbCGA0do4IHECQQDBBfy/nPOP4jfyURRqbJ+2cVG2NFXErXNij0Ew8UC3Tt9Sg999NdKE+zlh8KOfkR/msfR5zQxnwFCQJuWCEvczAkEAmPPfQURnPndVyQt1r2LPN0FTOdX+4N/MTcpnuULQn2lS6EOD9khdVStO5KiZM0HMeooHkkrLjnmStd7lT6oC8QJAcatW9ng1LqxnifmZbjrdqxD8r7IOOC503rvCBlJsbAa0mOE0AYZqnQlc94JAuT07bh2p/Ph1r7ufNeTSD1Gf5QJAF8x3YuF2qJQB1QlSIowNurScy4fCz9XJp4l5kxfsfLABYl2salZQc9FftWr6girslPcyzFE5kG7jYvGfVwDBbA==",
    "currency": "CNY",
    "prefix_for_username": "ebaa",
    "adjust_datetime_minutes": 20
}
 ******/
class Game_api_ebet_ggfishing extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $islive;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;

	const ProductWallet = 2;
	const TRANSFER_IN = 'IN';
	const TRANSFER_OUT = 'OUT';
	const API_performTransfer = 'performTransfer';

	public function __construct() {
        parent::__construct();
		$this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
		$this->channelId      = $this->getSystemInfo('channelId');
		$this->islive         = $this->getSystemInfo('live');
		$this->thirdParty     = $this->getSystemInfo('thirdParty');
		$this->tag            = $this->getSystemInfo('tag');
		$this->public_key     = $this->getSystemInfo('public_key');
		$this->private_key    = $this->getSystemInfo('private_key');
		$this->currency       = $this->getSystemInfo('currency','CNY');

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

	}

    const SUCCESS_CODE = 200;

	public function getPlatformCode() {
		return EBET_GGFISHING_API;
	}

	public function onlyTransferPositiveInteger(){
        return true;
    }
    
	public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        // echo $url."<br>";
        return $url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		$action = $params["method_action"];
		unset($params["method_action"]); //unset action not need on params

		$postParams = array(
				"channelId"         => $this->channelId,
				"thirdParty"        => $this->thirdParty,
				"tag"               => $this->tag,
				"action" => array(
						"command"    => $action,
						"parameters" => $params
				),
				"live"              => $this->islive,
				"timestamp"         => time()
		);
		$postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);
		$this->CI->utils->debug_log('EBET GGFISHING PARAMS POST ',json_encode($postParams,true));
		// echo json_encode($postParams,true);exit;
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        if ($resultArr['status'] == self::SUCCESS_CODE) {
			$result = json_decode($resultArr['result'],true);
			if(isset($resultArr['isgamelogs'])&&$resultArr['isgamelogs']){
				$success = true;
			}else{
				if($result['Code']==0){
	           		$success = true;
				}else{
	        		$success = false;
				}
			}
        }else{
        	$success = false;
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('EBET GGFISHING got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function isPlayerExist($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForIsPlayerExists', 
			'playerName'	  => $playerName
		);

        $params = array(
            "PlayerId"     => $playerName,
            "method_action" => "checkexists"
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExists($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$userName = $this->getVariableFromContext($params, 'userName');
		$success = false;
		$result = array();

		if ($resultArr['status'] == self::SUCCESS_CODE) {
			$resultArr = json_decode($resultArr["result"],true);
			if(isset($resultArr['Code'])&&$resultArr['Code']==0){
				$success = true;
				$result['exists'] = true;
			}else{
				$success = true;
				$result['exists'] = false;
			}
		}else{
			$success = false;
			$result['exists'] = null;
		}

		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$userName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'userName'        => $userName,
			'sbe_userName'    => $playerName
		);

		$params = array(
			'PlayerId' 	=> $userName,
			'Currency' 	=> $this->currency,
			'Password' 	=> $password,
			'method_action' => 'register'
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$userName = $this->getVariableFromContext($params, 'userName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('EBET GGFISHING CREATE PLAYER resultArr ======>', $resultJsonArr);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $userName);

		$reuslt = array(
			"userName" => $userName
		);

		return array($success, $reuslt);

	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName'	  => $playerName
		);

        $params = array(
            "PlayerId"      => empty($playerName)?$userName:$playerName,
            "ProductWallet"	=> self::ProductWallet,
            "method_action" => "getbalance"
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);		
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		$this->CI->utils->debug_log('query balance result',$resultArr);
		if ($success) {
			$resultArr = json_decode($resultArr["result"],true);
			$result['balance'] = @floatval($resultArr['Balance']);

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} 

		return array($success, $result);
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_IN;
		return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);

	}

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){

		$type = self::TRANSFER_OUT;
		return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);

	}
	
	public function performTransfer($userName, $amount,$type, $transfer_secure_id=null){
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$transactionId = $this->tag.$playerName.date("YmdHis");

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForPerformTransfer',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'Type' => $type,
			'transactionId' => $transactionId
		);

		$params = array(
			'PlayerId' => $playerName,
			'ProductWallet' => self::ProductWallet,
			'TransactionId' => $transactionId,
			'Amount' => ($type==self::TRANSFER_IN)?$amount:('-'.$amount),
			"method_action" => "performtransfer"
		);

		return $this->callApi(self::API_performTransfer, $params, $context);
	}

	public function processResultForPerformTransfer($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'transactionId');
		$type = $this->getVariableFromContext($params, 'Type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result['external_transaction_id']=$external_transaction_id;

		if ($success) {

            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                $afterBalance = 0;

                if($type == self::TRANSFER_IN){ // Deposit
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= EBET GGFISHING AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	                }
	                // Deposit
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeMainWalletToSubWallet());
                }else{ // Withdraw
                	if ($playerBalance && $playerBalance['success']) {
	                    $afterBalance = $playerBalance['balance'];
	                    $this->CI->utils->debug_log('============= EBET GGFISHING AFTER BALANCE FROM API '.$type.' ######### ', $afterBalance);
	                } else {
	                    //IF GET PLAYER BALANCE FAILED
	                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
	                    $afterBalance = $rlt->totalBalanceAmount;
	                    $this->CI->utils->debug_log('============= EBET GGFISHING AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
	                }
	                // Withdraw
	                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
	                    $this->transTypeSubWalletToMainWallet());
                }

            } else {
                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
            }
        }

        return array($success, $result);

	}

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'ZH-CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'EN-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'EN-US';
                break;
            default:
                $lang = 'EN-US';
                break;
        }
        return $lang;
    }

    public function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName
		);

		$params = array(
			'PlayerId' => $playerName,
			'method_action' => 'terminatesession'
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$reuslt = array(
			'playerName' => $playerName
		);

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$ip = $this->CI->input->ip_address();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName
		);

		$params = array(
			'PlayerId' => $playerName,
			'GameCode' => $extra['gameCode'],
			'Language' => $this->getLauncherLanguage($extra['language']),
			'IpAddress' => $ip,
			'ProductWallet' => self::ProductWallet,
			'method_action' => 'launchgame'
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

	public function processResultForQueryForwardGame($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array();
		if($success){		
			$data = json_decode($resultArr['result'],true);
			$result = array('url' => $data['GameUrl']);
		}

		return array($success, $result);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$startDate=$startDate->format('Y-m-d H:i:s');
		$endDate=$endDate->format('Y-m-d H:i:s');
		$page = 1;
		$take = 5000; // max data get

		return $this->_continueSync( $startDate, $endDate, $take, $page );

	}

	function _continueSync( $startDate, $endDate, $take = 0, $page = 1){
		$return = $this->syncEbetGGFishingGamelogs($startDate,$endDate,$take,$page);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$page++;
				return $this->_continueSync( $startDate, $endDate, $take, $page );
			}
		}
		return $return;
	}


	function syncEbetGGFishingGamelogs($startDate,$endDate,$take,$page){

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'startDate' => $startDate,
				'endDate' => $endDate,
				'take' => $take,
				'skip' => $page
		);

		$params = array(
				"startDate" => $startDate,
				"endDate" => $endDate,
                "pageSize" => $take, //page Size default is 5000
				"pageNumber" => $page, // page number
				"method_action" => "getrawbethistory"
		);

		$this->utils->debug_log('=====================> GGFISHING syncOriginalGameLogs params', $params);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('ebetggfishing_game_logs'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$resultArr['isgamelogs'] = true;
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$this->utils->debug_log('=====================> EBET GGFISHING syncOriginalGameLogs result', count($resultArr));

        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

		if ($success) {
            if (!empty($gameRecords)) {
                $availableRows = $this->CI->ebetggfishing_game_logs->getAvailableRows($gameRecords);

                if (!empty($availableRows)) {
                    $gameRecordsPush = array();
                    foreach ($availableRows as $record) {
                    	if($record['status']!='Approved'){
                        	continue;
                        }

                        $record['dateCreated'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['dateCreated']/1000)));
                        $record['gameDate'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['gameDate']/1000)));
                        $record['lastUpdatedDate'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['lastUpdatedDate']/1000)));
                        $recordPush = array();

                        $recordPush['ebetgg_id'] = isset($record['id'])?$record['id']:null;
                        $recordPush['thirdParty'] = isset($record['thirdParty'])?$record['thirdParty']:null;
                        $recordPush['tag'] = isset($record['tag'])?$record['tag']:null;
                        $recordPush['provider'] = isset($record['provider'])?$record['provider']:null;
                        $recordPush['playerId'] = isset($record['playerId'])?$record['playerId']:null;
                        $recordPush['gameId'] = isset($record['gameId'])?$record['gameId']:null;
                        $recordPush['providerRoundId'] = isset($record['providerRoundId'])?$record['providerRoundId']:null;
                        $recordPush['currency'] = isset($record['currency'])?$record['currency']:null;
                        $recordPush['betAmount'] = isset($record['betAmount'])?$record['betAmount']:null;
                        $recordPush['winLoss'] = isset($record['winLoss'])?$record['winLoss']:null;
                        $recordPush['status'] = isset($record['status'])?$record['status']:null;
                        $recordPush['dateCreated'] = isset($record['dateCreated'])?$record['dateCreated']:null;
                        $recordPush['gameDate'] = isset($record['gameDate'])?$record['gameDate']:null;
                        $recordPush['lastUpdatedDate'] = isset($record['lastUpdatedDate'])?$record['lastUpdatedDate']:null;

                        //SBE use
                        $recordPush['external_uniqueid']   = $record['id']; //add external_uniueid for og purposes
                        $recordPush['response_result_id']  = $responseResultId;

                        $this->CI->ebetggfishing_game_logs->insertGameLogs($recordPush);
                        $count++; # add count inserted data
                    }
                }
            }
		}

		return array($success,array('count'=>count($gameRecords)));
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ebetggfishing_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
		//observer the date format
		$startDate->modify($this->getDatetimeAdjust());

		$startDate=$startDate->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		// $this->gameTimeToServerTime
		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->ebetggfishing_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;

		if ($result) {
			foreach ($result as $data) {
				$playerId = $this->getPlayerIdInGameProviderAuth($data->playerId);
				$username = $data->playerId;

				$cnt++;
				$bet_amount = $data->bet_amount;
				$realbet = $data->bet_amount;
				$result_amount = (float)$data->result_amount;

				$game_description_id = $data->game_description_id;
				$game_type_id = $data->game_type_id;

				//should use processGameDesction function
				if (empty($game_description_id)) {
					$unknownGame = $this->getUnknownGame();
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$extra = array('trans_amount'=> $realbet,"table"=>$data->providerRoundId);

				$this->syncGameLogs(
						$game_type_id,
						$game_description_id,
						$data->game_code,
						$data->game_type,
						$data->game,
						$playerId,
						$username,
						$bet_amount,
						$result_amount,
						null, # win_amount
						null, # loss_amount
						null, # after_balance
						0, # has_both_side
						$data->external_uniqueid,
						$data->gameDate, //start
						$data->gameDate, //end
						$data->response_result_id,
						1,
						$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
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


	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	# HELPER ########################################################################################################################################

	function verify($str, $signature) {
		$signature = base64_decode($signature);
		$this->rsa->loadKey($this->public_key);
		return $this->rsa->verify($str, $signature);
	}

	function encrypt($str) {
		$this->rsa->loadKey($this->private_key);
		$signature = $this->rsa->sign($str);
		$signature = base64_encode($signature);
		return $signature;
	}
}

/*end of file*/