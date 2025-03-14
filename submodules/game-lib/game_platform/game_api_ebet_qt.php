<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_qt extends Abstract_game_api {

	private $api_url;
	private $channelId;
	private $live;
	private $tag;
	private $public_key;
	private $private_key;
	private $thirdParty;
	private $currency;
	private $ebetqt_game_logs_model;

	const OPERATION_DEPOSIT = 'CREDIT';
	const OPERATION_WITHDRAW = 'DEBIT';

	public function __construct() {
		parent::__construct();

		$this->api_url 			= $this->getSystemInfo('api_url');

		$this->channelId 		= $this->getSystemInfo('channelId');
		$this->thirdParty 		= $this->getSystemInfo('thirdParty');
		$this->tag 				= $this->getSystemInfo('tag');
		$this->live 			= $this->getSystemInfo('live');

		$this->currency 		= $this->getSystemInfo('currency', 'CNY');
 		$this->country 			= $this->getSystemInfo('country', 'CN');
		$this->language 		= $this->getSystemInfo('language', 'zh_CN');

		$this->public_key 		= $this->getSystemInfo('public_key');
		$this->private_key 		= $this->getSystemInfo('private_key');
		$this->page_size 		= $this->getSystemInfo('page_size', 5);

		# init RSA
		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');

		$this->CI->load->model('ebetqt_game_logs');
		$this->ebetqt_game_logs_model = $this->CI->ebetqt_game_logs;
	}

	public function getPlatformCode() {
		return EBET_QT_API;
	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url;
		return $url;
	}

	public function getHttpHeaders($params){
		return array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		);
	}

	protected function customHttpCall($ch, $params) {

		$command = $params['command'];

		unset($params['command']);

		$postParams = array(
			'channelId' => $this->channelId,
			'thirdParty' => $this->thirdParty,
			'tag' => $this->tag,
			'action' => array(
				'command' => $command,
				'parameters' => $params
			),
			'live' => $this->live,
			'timestamp' => time()
		);

		$postParams['signature'] = $this->encrypt($this->channelId . $this->thirdParty . $this->tag . $postParams['timestamp']);

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,TRUE));
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = NULL, $extra = NULL, $resultObj = NULL) {
		return array(FALSE, NULL);
	}

	function processResultBoolean($responseResultId, $resultJsonArr, $playerName = NULL) {
		$success = isset($resultJsonArr['status']) && $resultJsonArr['status'] == 200;

		if ( ! $success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('EBET QT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJsonArr);
		}

		return $success;
	}

	function createPlayer($playerUsername, $playerId, $password, $email = NULL, $extra = NULL) {

		parent::createPlayer($playerUsername, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$referenceId = $this->tag . '_' . md5($gameUsername . time());
		$amount = 0.01;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerUsername' => $playerUsername,
			'amount' => $amount,
		);

		$params = array(
			'command' => 'fundtransfer',
			'type' => self::OPERATION_DEPOSIT,
			'referenceId' => $referenceId,
			'playerId' => $gameUsername,
			'amount' => $amount,
			'currency' => $this->currency,
		);

        return $this->callApi(self::API_createPlayer, $params, $context);

	}

	function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$result = array('deposit' => $resultArr);

		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername) && $resultArr['status'] == 'COMPLETED';

		if ($success) {
        	$withdraw_result = $this->withdrawFromGame($playerUsername, $amount, NULL, TRUE);
        	$result['withdraw'] = $withdraw_result['result'];
		}

		return array($success, $result);
	}

	function queryPlayerBalance($playerUsername) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerUsername' => $playerUsername,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'command' => 'retrieveplayerbalance',
			'playerId' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

		$result = array('balance' => 0);

		if ($success && isset($resultArr['amount'], $resultArr['currency']) && $resultArr['currency'] == $this->currency) {
			$result['balance'] = floatval($resultArr['amount']);
		}

		return array($success, $result);
	}

	function isPlayerExist($playerUsername) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
		$gamePassword = $this->getPasswordString($playerUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExists',
			'playerUsername' => $playerUsername,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'command' => 'memberdetail',
			'username' => $gameUsername,
			'password' => $gamePassword,
			'currencyCode' => $this->currency,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	function processResultForIsPlayerExists($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

        $result = array(
        	'exists' => isset($resultArr['credit-balance'])
        );

		return array($success, $result);
	}

	function depositToGame($playerUsername, $amount, $transfer_secure_id = NULL) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$referenceId = $this->tag . '_' . md5($gameUsername . time());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerUsername' => $playerUsername,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'referenceId' => $referenceId,
		);

		$params = array(
			'command' => 'fundtransfer',
			'type' => self::OPERATION_DEPOSIT,
			'referenceId' => $referenceId,
			'playerId' => $gameUsername,
			'amount' => $amount,
			'currency' => $this->currency,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	function processResultForDepositToGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceId = $this->getVariableFromContext($params, 'referenceId');

		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername) && $resultArr['status'] == 'COMPLETED';

		$result = array('result' => $resultArr);

		if ($success) {

			$result['external_transaction_id'] = $referenceId;

			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

			if ($playerId) {

				$playerBalance = $this->queryPlayerBalance($playerUsername);

				$afterBalance = @$playerBalance['balance'];

				if( ! empty($afterBalance)) {
					$result['currentplayerbalance'] = $afterBalance;
				}

				$this->insertTransactionToGameLogs($playerId, $playerUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

				$result['userNotFound'] = FALSE;
			} else {
				$result['userNotFound'] = TRUE;
			}
		}

		return array($success, $result);
	}

	function withdrawFromGame($playerUsername, $amount, $transfer_secure_id = NULL, $notRecordTransaction = FALSE) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$referenceId = $this->tag . '_' . md5($gameUsername . time());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerUsername' => $playerUsername,
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'referenceId' => $referenceId,
            'notRecordTransaction' => $notRecordTransaction,
		);

		$params = array(
			'command' => 'fundtransfer',
			'type' => self::OPERATION_WITHDRAW,
			'referenceId' => $referenceId,
			'playerId' => $gameUsername,
			'amount' => $amount,
			'currency' => $this->currency,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceId = $this->getVariableFromContext($params, 'referenceId');
        $notRecordTransaction = $this->getVariableFromContext($params, 'notRecordTransaction');

		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername) && $resultArr['status'] == 'COMPLETED';

		$result = array('result' => $resultArr);

		if ($success) {

			$result['external_transaction_id'] = $referenceId;

			$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

			if ($playerId) {

				$playerBalance = $this->queryPlayerBalance($playerUsername);

				$afterBalance = @$playerBalance['balance'];

				if( ! empty($afterBalance)) {
					$result['currentplayerbalance'] = $afterBalance;
				}

				if ( ! $notRecordTransaction) {
					$this->insertTransactionToGameLogs($playerId, $playerUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
				}

				$result['userNotFound'] = FALSE;
			} else {
				$result['userNotFound'] = TRUE;
			}
		}

		return array($success, $result);
	}

	function queryForwardGame($playerUsername, $extra = NULL) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerUsername' => $playerUsername,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'command' => 'getgamelauncher',
			'gameId' => $extra['gameId'],
			'playerId' => $gameUsername,
			'currency' => $this->currency,
			'country' => $this->country,
			'device' => isset($extra['device']) ? $extra['device'] : 'desktop',
			'mode' => isset($extra['mode']) ? $extra['mode'] : 'real',
			'lang' => isset($extra['lang']) ? $extra['lang'] : $this->language,
		);

		if (isset($extra['returnUrl'])) {
			$params['returnUrl'] = $extra['returnUrl'];
		}

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerUsername = $this->getVariableFromContext($params, 'playerUsername');
		$resultJsonArr = $this->getResultJsonFromParams($params);

		$resultArr = json_decode($resultJsonArr['result'], TRUE);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername) && isset($resultArr['url']);

		$result = array();

		if ($success) {
			$result['url'] = $resultArr['url'];
		}

		return array($success, $result);
	}

	function syncOriginalGameLogs($token = FALSE) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

    	$context = array(
    		'callback_obj' => $this,
    		'callback_method' => 'processResultForSyncGameRecords',
    	);

    	$params = array(
    		'command' => 'getrawbethistory',
    		'startDate' => $startDate->format('Y-m-d H:i:s'),
    		'endDate' => $endDate->format('Y-m-d H:i:s'),
    		'pageSize' => $this->page_size,
    		'pageNumber' => 1,
    		'dataType' => 'gameround',
    	);

    	do {

			$result = $this->callApi(self::API_syncGameRecords, $params, $context);

			if ( ! $result['success']) {
				return $result;
			}

    	} while (($params['pageNumber']++ * $this->page_size) < $result['totalCount']);

		return $result;
	}

	function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$resultArr = json_decode($resultJsonArr['result'], TRUE);

		if ($resultArr && isset($resultArr['gameRounds']) && ! empty($resultArr['gameRounds'])) {

			$responseResultId = $this->getResponseResultIdFromParams($params);

			$records = $resultArr['gameRounds'];

			foreach ($records as $record) {

				$playerId = $this->getPlayerIdInGameProviderAuth($record['playerId']);

				if ($playerId) {

					$data = array(
						'ebet_qt_id' => $record['id'],
						'gameUniqueId' => $record['gameUniqueId'],
						'status' => $record['status'],
						'totalBet' => $record['totalBet'],
						'totalPayout' => $record['totalPayout'],
						'currency' => $record['currency'],
						'initiated' => date('Y-m-d H:i:s', $record['initiated']/1000),
						'completed' => date('Y-m-d H:i:s', $record['completed']/1000),
						'playerId' => $record['playerId'],
						'operatorId' => $record['operatorId'],
						'device' => $record['device'],
						'gameProvider' => $record['gameProvider'],
						'gameId' => $record['gameId'],
						'gameCategory' => $record['gameCategory'],
						'gameClientType' => $record['gameClientType'],
						'comment' => $record['comment'],
						'thirdParty' => $record['thirdParty'],
						'tag' => $record['tag'],
						'ebet_qt_playerId' => $record['playerId'],
						'playerId' => $playerId,
						'uniqueid' => $record['id'],
						'external_uniqueid' => $record['id'],
						'response_result_id' => $responseResultId,
					);

					$this->ebetqt_game_logs_model->syncGameLogs($data);

				}

			}

		}

		return array(TRUE, array('totalCount' => $resultArr['totalCount']));

	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model('game_logs');

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$rows = $this->ebetqt_game_logs_model->getGameLogStatistics($startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'));

		foreach ($rows as $row) {

			$external_uniqueid 	 = $row['external_uniqueid'];
			$player_id 			 = $row['playerId'];
			$player_username 	 = $row['player_username'];
			$game 	 			 = $row['game'];
			$game_type 	 		 = $row['game_type'];
			$bet_amount 		 = $row['bet_amount'];
			$result_amount 		 = $row['win_amount'] - $row['bet_amount'];
			$start_at 			 = $row['start_at'];
			$end_at 			 = $row['end_at'];
			$response_result_id  = $row['response_result_id'];

            $game_description_id         = $row['game_description_id'];
            $game_type_id        = $row['game_type_id'];

            $has_both_side = $bet_amount >= $result_amount && $result_amount > 0 ? 1 : 0;

			// $game_description_id = NULL;
			// $game_type_id = NULL;

			list($game_description_id, $game_type_id) = $this->processUnknownGame(NULL, NULL, $game, $game_type, $game, array('game_code' => $game));

			$this->syncGameLogs(
				$game_type_id,  			# game_type_idx
				$game_description_id,		# game_description_id
				$game, 						# game_code
				$game_type_id, 				# game_type
				$game, 						# game
				$player_id, 				# player_id
				$player_username, 			# player_username
				$bet_amount, 				# bet_amount
				$result_amount, 			# result_amount
				null,						# win_amount
				null,						# loss_amount
				null,						# after_balance
				$has_both_side, 			# has_both_side
				$external_uniqueid, 		# external_uniqueid
				$start_at,					# start_at
				$end_at,					# end_at
				$response_result_id,		# response_result_id
				Game_logs::FLAG_GAME		# flag
			);

		}

		return array('success' => TRUE);
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => TRUE);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => TRUE);
	}

	function changePassword($playerName, $oldPassword = NULL, $newPassword) {
		return $this->returnUnimplemented();
	}

	function login($userName, $password = NULL) {
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

	function logout($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = NULL, $dateTo = NULL) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = NULL) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
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

	# HELPER ########################################################################################################################################

}

/*end of file*/