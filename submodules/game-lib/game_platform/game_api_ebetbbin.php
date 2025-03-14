<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebetbbin extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $islive;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;
	private $logout_before_launch_game;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->channelId = $this->getSystemInfo('channelId');
		$this->islive = $this->getSystemInfo('live');
		$this->thirdParty = $this->getSystemInfo('thirdParty');
		$this->tag = $this->getSystemInfo('tag');
		$this->public_key = $this->getSystemInfo('public_key');
		$this->private_key = $this->getSystemInfo('private_key');
		$this->currency = $this->getSystemInfo('currency');

		$this->logout_before_launch_game=$this->getSystemInfo('logout_before_launch_game', true);

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');
	}

	public function getPlatformCode() {
		return EBET_BBIN_API;
	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url;
		return $url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
		return array("Accept" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		$action = $params["method_action"];
		unset($params["method_action"]); //unset action not need on params

		$postParams = array(
				"channelId" => $this->channelId,
				"thirdParty" => $this->thirdParty,
				"tag" => $this->tag,
				"action" => array(
						"command" => $action,
						"parameters" => $params
				),
				"live" => $this->islive,
				"timestamp" => time()
		);
		$postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = true;
		// $fullResp = json_decode($resultArr,true);
		// $response = json_decode($resultArr['result'],true);

		// if(isset($response['result']) && $response['result'] == false ){
		// 	$success = false;
		// }

		$cnt=count($resultArr['result']);
		if($cnt<1000){
			$this->CI->utils->debug_log('resultArr', $resultArr, $responseResultId, $playerName);
		}else{
			$this->CI->utils->debug_log('resultArr', count($resultArr), $responseResultId, $playerName);
		}


		// if(isset($response['isgamelogs'])){

		if(@$resultArr['status']!=200){

			$success = false;

		}else{

			$response = json_decode(@$resultArr['result'],true);
			if(isset($response['result']) && $response['result'] == false ){
				$success = false;
			}
		}

		// }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('EBET_BBIN_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	function getGameTimeToServerTime() {
		return '+12 hours'; #(GMT -4)
	}

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	function getServerTimeToGameTime() {
		return '-12 hours';
	}

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForCreatePlayer',
				'playerName' => $playerName
		);

		$params = array(
				'username'=> $playerName,
				'method_action'=> "createmember"
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$remitno = date("YmdHis").rand(1,1000);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForDepositToGame',
				'playerName' => $playerName,
				'sbe_playerName' => $userName,
				'amount' => $amount,
				'transaction_id'=>$remitno,
		);

		// $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
		// $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);

		$params = array(
				"username" => $playerName,
				"remitno" => $remitno,
				"action" => "IN", // IN - deposit , OUT - withdraw
				"remit" => $amount,
				"method_action" => "transfer"
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;

		$transaction_id=$this->getVariableFromContext($params, 'transaction_id');

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $transaction_id;
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//deposit
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}
		}

		return array($success, $result);

	}

	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$remitno = date("YmdHis").rand(1,1000);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForWithdrawFromGame',
				'playerName' => $playerName,
				'sbe_playerName' => $userName,
				'transaction_id'=>$remitno,
				'amount' => $amount
		);

		$params = array(
				"username" => $playerName,
				"remitno" => $remitno,
				"action" => "OUT", // IN - deposit , OUT - withdraw
				"remit" => $amount,
				"method_action" => "transfer"
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');

		$transaction_id=$this->getVariableFromContext($params, 'transaction_id');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			$playerBalance = $this->queryPlayerBalance($sbe_playerName);

			//for sub wallet
			$afterBalance = @$playerBalance['balance'];
			$result["external_transaction_id"] = $transaction_id;
			if(!empty($afterBalance)){
				$result["currentplayerbalance"] = $afterBalance;
			}
			$result["userNotFound"] = false;
			$success = true;
			//update
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			if ($playerId) {
				//withdraw
				$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
						$this->transTypeSubWalletToMainWallet());

			} else {
				$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	function queryPlayerBalance($userName) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryPlayerBalance',
				'playerName' => $playerName
		);

		$params = array(
				"username" => $playerName,
				"method_action" => "checkusrbalance"
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array();

		if ($success) {
			$resultArr = json_decode($resultArr["result"],true);
			if(isset($resultArr['Balance'])){
				$result['balance'] = @floatval($resultArr['Balance']);

				if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
					$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
				} else {
					$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				}
			}
		}

		return array($success, $result);

	}

	function queryForwardGame($playerName, $extra=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultQueryForwardGame',
				'playerName' => $playerName
		);

		//logout_before_launch_game

		// if($this->logout_before_launch_game){
		// 	$this->logout($playerName);
		// }

		$params = array(
				"username" => $playerName,
				"lang" => $extra["lang"],
				"method_action" => "getloginurl"
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultQueryForwardGame($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function syncOriginalGameLogs($token = false) {
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
		$return = $this->syncEbetBbinGamelogs($startDate,$endDate,$take,$page);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$skip += $take;
				return $this->_continueSync( $startDate, $endDate, $take, $page );
			}
		}
		return $return;
	}


	function syncEbetBbinGamelogs($startDate,$endDate,$take,$page){

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
			// "type" => "casinolive", // game type
				"pageNumber" => $page, // page number
				"pageSize" => $take, //page Size default is 5000
				"method_action" => "getrawbethistory"
		);
		$this->utils->debug_log('=====================> EBETBBIN syncOriginalGameLogs params', $params);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {

		$this->CI->load->model(array('ebetbbin_game_logs'));
		$resultArr = $this->getResultJsonFromParams($params);
		$resultArr['isgamelogs'] = true; // tag gamelogs for process boolean
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$count = 0;
		$this->utils->debug_log('=====================> EBETBBIN syncOriginalGameLogs result', count($resultArr));
		if ($success) {
			$rarr = json_decode($resultArr['result'],true);
			$gameRecords = $rarr["betHistories"];

			if ($gameRecords) {
				$gameRecordsPush = array();
				foreach ($gameRecords as $record) {
					$recordPush = array();
					$decode_record = json_decode($record,true); //decode json data
					$player_id = $this->getPlayerIdInGameProviderAuth($decode_record['UserName']);

					if(!$player_id){
						//still save it
						$player_id=0;
						// 	continue; # if not exist player continue
					}

					if($decode_record['type'] == 'sport') {
						if($decode_record['Result'] == "X") continue;
					} elseif($decode_record['type'] == 'lottery' || $decode_record['type'] == 'casinolive') {
						if($decode_record['Result'] == 0) continue;
					}
					
					$recordPush['PlayerId'] = $player_id;
					$recordPush['Username'] = isset($decode_record['UserName'])?$decode_record['UserName']:"";
					$recordPush['ebetbbinID'] = isset($decode_record['_id'])?json_encode($decode_record['_id']):"";
					$recordPush['tag'] = isset($decode_record['tag'])?$decode_record['tag']:"";
					$recordPush['thirdParty'] = isset($decode_record['thirdParty'])?$decode_record['thirdParty']:"";
					$recordPush['GameCode'] = isset($decode_record['GameCode'])?$decode_record['GameCode']:"";
					$recordPush['Origin'] = isset($decode_record['Origin'])?$decode_record['Origin']:"";
					$recordPush['RoundNo'] = isset($decode_record['RoundNo'])?$decode_record['RoundNo']:"";
					$recordPush['ExchangeRate'] = isset($decode_record['ExchangeRate'])?$decode_record['ExchangeRate']:"";
					$recordPush['BetAmount'] = isset($decode_record['BetAmount'])? $this->gameAmountToDB($decode_record['BetAmount']) : 0;
					$recordPush['GameType'] = isset($decode_record['GameType'])?$decode_record['GameType']:"";
					$recordPush['ResultType'] = isset($decode_record['ResultType'])?$decode_record['ResultType']:"";
					$recordPush['WagersID'] = isset($decode_record['WagersID'])?$decode_record['WagersID']:"";
					$recordPush['Result'] = isset($decode_record['Result'])?$decode_record['Result']:"";
					$recordPush['Card'] = isset($decode_record['Card'])?$decode_record['Card']:"";
					$recordPush['Commissionable'] = isset($decode_record['Commissionable'])? $this->gameAmountToDB($decode_record['Commissionable']) : 0;
					$recordPush['Payoff'] = isset($decode_record['Payoff'])?$decode_record['Payoff']:"";
					$recordPush['Currency'] = isset($decode_record['Currency'])?$decode_record['Currency']:"";
					$recordPush['WagersDate'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", substr($decode_record['WagersDate'], 0, 10)));
					$recordPush['WagerDetail'] = isset($decode_record['WagerDetail'])?$decode_record['WagerDetail']:"";
					$recordPush['SerialID'] = isset($decode_record['SerialID'])?$decode_record['SerialID']:"";
					$recordPush['type'] = isset($decode_record['type'])?$decode_record['type']:"";
					$recordPush['IsPaid'] = isset($decode_record['IsPaid'])?$decode_record['IsPaid']:"";
					//SBE use
					$recordPush['external_uniqueid'] = $decode_record['WagersID']; //add external_uniueid for og purposes
					$recordPush['response_result_id'] = $responseResultId;
					array_push($gameRecordsPush,$recordPush);
				}

				$availableRows = $this->CI->ebetbbin_game_logs->getAvailableRows($gameRecordsPush);
				$count = count($availableRows);

				if ($availableRows) {
					$this->CI->ebetbbin_game_logs->insertBatchGameLogs($availableRows);
				}

			}
		}

		return array($success,array('count'=>$count));
	}

	public function processGameBetDetail($rowArray){
		// {"bet": "Banker", "rate": 0.95, "bet_detail": ""}
		$bet=null;
		$rate=null;
		$bet_detail=null;

		$betType=[];
		$betOdd=[];
		$wagerDetail=@$rowArray['WagerDetail'];
		if(!empty($wagerDetail)){
			//"玩法,賠率,下注,派彩*玩法,賠率,下注,派彩*玩法,賠率,下注,派彩"
			//bet type, bet odd, bet amount, payout
			$arr=explode('*', $wagerDetail);
			if(!empty($arr)){
				foreach ($arr as $item) {
					$subArr=explode(',' , $item);
					if(!empty($subArr)){
						$betType[]=@$subArr[0];
						$betOdd[]=@$subArr[1];
					}
				}
			}
		}

		$this->CI->utils->debug_log('WagerDetail', $rowArray['WagerDetail'], 'game type',$rowArray['GameType'] );

		switch ($rowArray['GameType']) {
			case '3001':
				//baccarat
				if(!empty($betType)){
					//translate
					$betTypeNames=[];
					foreach ($betType as $t) {
						switch ($t) {
							case '1':
								$betTypeNames[]='banker';
								break;
							case '2':
								$betTypeNames[]='player';
								break;
							case '3':
								$betTypeNames[]='tie';
								break;
							case '4':
								$betTypeNames[]='bankerPair';
								break;
							case '5':
								$betTypeNames[]='playerPair';
								break;
							case '6':
								$betTypeNames[]='big';
								break;
							case '7':
								$betTypeNames[]='small';
								break;
							case '8':
								$betTypeNames[]='judgeResult-baccarat-bankerodd';
								break;
							case '9':
								$betTypeNames[]='judgeResult-baccarat-bankereven';
								break;
							case '10':
								$betTypeNames[]='judgeResult-baccarat-playerodd';
								break;
							case '11':
								$betTypeNames[]='judgeResult-baccarat-playereven';
								break;
							case '12':
								$betTypeNames[]='judgeResult-baccarat-anypair';
								break;
							case '13':
								$betTypeNames[]='judgeResult-baccarat-perfectpair';
								break;
							case '14':
								$betTypeNames[]='judgeResult-baccarat-bankernofee';
								break;
							case '15':
								$betTypeNames[]='judgeResult-baccarat-super6nofee';
								break;

						}
					}
					$rate=implode(',',$betOdd);
					$bet=implode(',',$betTypeNames);

					$this->CI->utils->debug_log('betTypeNames', $betTypeNames);
				}

				break;

			case '3002':
				//Mahjong Tiles 二八杠
				//  		if(!empty($betType)){
				// 	//translate
				// 	foreach ($betType as &$t) {
				// 		switch ($t) {
				// 			case '1':
				//             	$t='banker';
				// 				break;
				// 		}
				// 	}
				// }
				break;
			default:
				# code...
				break;
		}


		return ['bet'=>$bet, 'rate'=>$rate, 'bet_detail'=>$bet_detail];

	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ebetbbin_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		// $this->gameTimeToServerTime
		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->ebetbbin_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();
			// echo "<pre>";print_r($result);exit;

			foreach ($result as $data) {
				$player_id = $data->PlayerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;

				$bet_amount = $data->Commissionable;
				$realbet = $data->bet_amount;
				$result_amount = (float)$data->result_amount;

				$game_description_id = $data->game_description_id;
				$game_type_id = $data->game_type_id;

				//should use processGameDesction function
				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				$betDetail=$this->CI->utils->encodeJson($this->processGameBetDetail((array)$data));

				//added round no
				$round_no=!empty($data->external_uniqueid) ? $data->external_uniqueid : $data->RoundNo;
				$extra = array('table' => $round_no, 'trans_amount'=> $realbet, 'note'=>$betDetail);

				$this->syncGameLogs(
						$game_type_id,
						$game_description_id,
						$data->game_code,
						$data->game_type,
						$data->game,
						$player_id,
						$data->Username,
						$bet_amount,
						$result_amount,
						null, # win_amount
						null, # loss_amount
						null, # after_balance
						0, # has_both_side
						$data->external_uniqueid,
						$data->date_created, //start
						$data->date_created, //end
						$data->response_result_id,
						1,
						$extra

				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}


	function login($userName, $password = null) {
		return $this->queryForwardGame($userName, $password);
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultLogout',
				'playerName' => $playerName
		);

		//logout_before_launch_game

		// if($this->logout_before_launch_game){
		// 	$this->logout($playerName);
		// }

		$params = array(
				"username" => $playerName,
				"method_action" => "logout",
		);

		return $this->callApi(self::API_logout, $params, $context);

	}

	function processResultLogout($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

		$resultData=json_decode($resultJsonArr['result'], true);

		if(isset($resultData['data']['Code']) && $resultData['data']['Code']=='22000'){
			//User hasn't Login.
			$success=true;
		}

		return array($success, $resultJsonArr);
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	function isPlayerExist($userName) {

		// $result=parent::isPlayerExist($userName);

		// $result=[];

		// if($result['exists']){

		// $rlt=$this->queryPlayerBalance($userName);

		// $success=$rlt['success'];
		// $result['success']=$success;

		// if($success){
		// 	//only setup for balance exists
		// 	$result['exists']=isset($rlt['balance']);
		// 	if($result['exists']){
		// 		$player_id=$this->getPlayerIdInPlayer($userName);
		// 		//sync to game provider auth
		// 		$this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
		// 	}
		// }

		// }

		// return $result;

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForIsPlayerExists',
				'playerName' => $playerName,
				'userName'=>$userName,
		);

		$params = array(
				"username" => $playerName,
				"method_action" => "checkusrbalance"
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForIsPlayerExists($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$userName = $this->getVariableFromContext($params, 'userName');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array();

		if ($success) {
			$resultArr = json_decode($resultArr["result"],true);
			if(isset($resultArr['Balance'])){
				$result['exists'] = true; // @floatval($resultArr['Balance']);

				// if($result['exists']){
				$player_id=$this->getPlayerIdInPlayer($userName);
				//sync to game provider auth
				$this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
				// }

				// if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				// 	$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
				// } else {
				// 	$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
				// }
			}
		}else{
			if($resultArr['status']==200){

				$rlt=json_encode($resultArr['result']);
				if(@$rlt['data']['Code']=='22002'){
					$result['exists']=false;
				}
			}
		}

		return array($success, $result);
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

	public function convertTransactionAmount($amount){
		return floor($amount);
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return round(floatval($amount), 2);
	}

	public function onlyTransferPositiveInteger(){
		return true;
	}

	# HELPER ########################################################################################################################################

}

/*end of file*/