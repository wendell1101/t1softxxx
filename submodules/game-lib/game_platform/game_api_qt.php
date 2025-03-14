<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Process game lists
 * * Get access to token
 * * deposit to game
 * * revoke the token
 * * confirm transactions
 * * sync player accounts
 * * withdraw from game
 * * get player balance
 * * get player daily balance
 * * get forward game
 * * sync game logs
 * * getting player balance by batch using player name
 *
 * The functions implemented by child class:
 * * Populating payment form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_qt extends Abstract_game_api {
	private $api_url;
	private $api_key;
	private $api_secret;
	private $access_token;
	private $transferId;
	private $isMethod;
	private $gameCodeForGameLaunch;
	private $currency;
	const POST_METHOD = 1;
	const PUT_METHOD = 2;
	const GET_METHOD = 3;
	const DELETE_METHOD = 4;
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;
	const API_syncNextGameRecords = "next";
    const API_completeTransaction = 'completeTransaction';

    const STATUS_PENDING = 'PENDING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->qt_username = $this->getSystemInfo('qt_username');
		$this->qt_password = $this->getSystemInfo('qt_password');
		$this->qt_return_url = $this->getSystemInfo('qt_return_url');
		// $this->currency = $this->getSystemInfo('currency', 'CNY');
        $this->currency = $this->getSystemInfo('qt_currency', 'CNY');
        $this->country = $this->getSystemInfo('country','CN');
        $this->token_prefix = $this->getSystemInfo('token_prefix', '');
        $this->forward_sites = $this->getSystemInfo('forward_sites');
        $this->prefix_count = $this->getSystemInfo('prefix_count', 3);
        $this->mobile_player_cashier_url =  $this->getSystemInfo('mobile_player_cashier_url', '/player_center/mobile_transfer');
        $this->revoke_after_complete_request = $this->getSystemInfo('revoke_after_complete_request', false);
        $this->enable_cached_on_get_token = $this->getSystemInfo('enable_cached_on_get_token', false);
		$this->go_back_www = $this->getSystemInfo('go_back_www', true);
	}

	const URI_MAP = array(
		self::API_queryPlayerBalance => '/v1/wallet/ext',
		self::API_depositToGame => '/v1/fund-transfers',
		self::API_login => '/v1/auth/token',
        self::API_queryTransaction => '/v1/fund-transfers',
		self::API_withdrawFromGame => '/v1/fund-transfers',
		self::API_queryForwardGame => '/v1/games',
		self::API_checkLoginToken => '/v1/auth/token',
		self::API_syncGameRecords => '/v1/game-rounds',
		self::API_syncNextGameRecords => '',
        self::API_queryBetDetailLink => '/v1/players',
        self::API_getGameProviderGamelist => '/v1/games',
		self::API_completeTransaction => '/v1/fund-transfers',
	);

	function getAccessToken() {
		$this->isMethod = self::POST_METHOD;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultGetAccessToken',
		);
		$params = array(
			"grant_type" => "password",
			"response_type" => "token",
			"username" => $this->qt_username,
			"password" => $this->qt_password,
		);

		if($this->enable_cached_on_get_token){
			$token = $this->getCommonAvailableApiToken(function() use ($context, $params){
				return $this->callApi(self::API_login, http_build_query($params), $context);
			});
			$this->access_token = $token;
			return array("success" => true, "access_token" => $token);
		}

		$response = $this->callApi(self::API_login, http_build_query($params), $context);
		$this->access_token = isset($response['access_token']) ? $response['access_token'] : null;
		return $response;
	}

	function processResultGetAccessToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$result=['access_token' => null, 'api_token'=>null, 'api_token_timeout_datetime'=>null];
		$success = false;
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			if(isset($resultJson['access_token'])){
				$api_token = $resultJson['access_token'];
                $default_milliseconds = 21600000;
				$milliseconds = isset($resultJson['expires_in']) ? $resultJson['expires_in'] : $default_milliseconds;
				$seconds = round($milliseconds / 1000, 0, PHP_ROUND_HALF_UP);
				$hours = floor($seconds / 3600);
 				$date = new \DateTime('+'.$hours.' hours');
 				$api_token_timeout_datetime = $this->CI->utils->formatDateTimeForMysql($date);
                $result['hours'] = $hours;
                $result['access_token'] = $result['api_token']= $resultJson['access_token'];
                $result['api_token_timeout_datetime']=$api_token_timeout_datetime;
			}
			// $this->access_token = $resultJson['access_token'];
		}
		return array($success, $result);
	}

	public function getPlatformCode() {
		return QT_API;
	}

	function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];

		if ($apiName == self::API_login || $apiName == self::API_syncGameRecords) {
			$url = $this->api_url . $apiUri . '?' . $params;
		} elseif ($apiName == self::API_completeTransaction) {
			$url = $this->api_url . $apiUri . '/' . $this->transferId . '/status';
		} elseif ($apiName == self::API_queryPlayerBalance) {
			$url = $this->api_url . $apiUri . '/' . $params['playerId'];
		} elseif ($apiName == self::API_queryForwardGame) {
			$url = $this->api_url . $apiUri . '/' . $this->gameCodeForGameLaunch . '/launch-url';
		} elseif ($apiName == self::API_syncNextGameRecords) {
			$url = $this->api_url . $params;
		} elseif ($apiName == self::API_queryBetDetailLink) {
			$url = $this->api_url . $apiUri . '/' . $this->playerName . '/service-url';
		} elseif ($apiName == self::API_queryTransaction) {
            $transfer_id = isset($params['transferId']) ? $params['transferId'] : null;
			$url = $this->api_url . $apiUri . '/' . $transfer_id;
		} else {
			$url = $this->api_url . $apiUri;
		}

		return $url;
	}

	protected function getHttpHeaders($params) {
		return array("Accept" => "application/json",
			"Content-Type" => "application/json",
			"Authorization" => "Bearer " . $this->access_token,
			"timezone" => "UTC/GMT",
		);
	}

	public function processGameList($game) {
		$flash_enabled = $game['flash_enabled'];
		$html_five_enabled = $game['html_five_enabled'];

		$game = parent::processGameList($game);
		$game['fl'] = $flash_enabled;
		$game['h5'] = $html_five_enabled;
		$this->CI->load->model(array('game_type_model', 'game_description_model'));
		$gameTypeData = $this->CI->game_type_model->getGameTypeById($game['g']);
		$gameIsMini = $this->CI->game_description_model->getGameDescriptionByCode($game['c'])->attributes;

		if ($flash_enabled == self::FLAG_FALSE && $html_five_enabled == self::FLAG_TRUE) {
			$game['gp'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/real/html5"; //game param
			$game['gpd'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/demo/html5"; //game param
			$game['v'] = true;
		} elseif ($flash_enabled == self::FLAG_TRUE && $html_five_enabled == self::FLAG_TRUE) {
			$game['gp'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/real/html5"; //game param
			$game['gpd'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/demo/html5"; //game param
			$game['v'] = true;
		} elseif ($flash_enabled == self::FLAG_TRUE && $html_five_enabled == self::FLAG_FALSE) {
			$game['gp'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/real/flash"; //game param
			$game['gpd'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c'] . "/demo/flash"; //game param
			if ($gameTypeData->game_type == "Slot" && !$gameIsMini) {
				$game['v'] = false;
			} else {
				$game['v'] = true;
			}
		}
		return $game;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->isMethod == self::POST_METHOD) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		} elseif ($this->isMethod == self::PUT_METHOD) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		} elseif ($this->isMethod == self::DELETE_METHOD) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
	}

public function callback($method,$result,$platform = 'web') {
	// if ($platform != 'web') {
		$result = json_decode($result, true);
		switch($method){
		case 'login':

			if (isset($result['username']) && isset($result['password']) && isset($result['codeChallenge'])) {

				if ($this->forward_sites) {
							$prefix = substr($result['username'], 0, $this->prefix_count)."_";
							if (isset($this->forward_sites[$prefix])) {
								$url = $this->forward_sites[$prefix].$method;
								return $this->forwardCallback($url,$result);
							}
						}

				$this->CI->load->model(array('common_token', 'external_common_tokens'));
				$gameUsername = $result['username'];
				$password = $result['password'];
				# Get playerId in game_provider_auth via gameUsername
				$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
				$current_pass = $this->getPasswordByGameUsername($gameUsername);
				# Check password
				 if($password!=$current_pass || empty($playerId)){
				 	http_response_code(401);
					$output = array(
						'code'=> "INVALID_LOGIN",
						'message'=> "Invalid username or password."
					);
					$this->CI->utils->debug_log('login failed =============  ', $output);
					return array('header' => $output,'status' => http_response_code(401));
				}

				# Get playerId in game_provider_auth via gameUsername
				$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
				# Create token via playerId
				$token = $this->CI->common_token->createTokenBy($playerId, 'player_id');
				$codeChallenge = $result['codeChallenge'];
				# Insert / Update external_common_tokens
				$this->CI->external_common_tokens->setPlayerToken($playerId, $codeChallenge, QT_API);
				$prefix = substr($result['username'], 0, $this->prefix_count);
				$output = array(
					'accessToken' => $prefix.$token,
					'codeChallenge' => $codeChallenge
				);
				$this->CI->utils->debug_log('login success =============  ', $output);
				return array('header' => $output,'status' => http_response_code(200));
			}
		break;
		case 'verifytoken':
		if (isset($result['accessToken']) && isset($result['codeChallenge'])) {

			if ($this->forward_sites) {
				$prefix = substr($result['accessToken'], 0, $this->prefix_count)."_";
				if (isset($this->forward_sites[$prefix])) {
					$url = $this->forward_sites[$prefix].$method;
					$result['accessToken'] = substr($result['accessToken'], 3);
					return $this->forwardCallback($url,$result);
				}
			}

			$this->CI->load->model(array('common_token', 'external_common_tokens','game_provider_auth'));
			$this->CI->utils->debug_log('QT_RAW_RESULT ', $result);
			$token = $result['accessToken'];
			$codeChallenge = $result['codeChallenge'];

			$playerId = $this->CI->common_token->getPlayerIdByToken($token);
			$paircodeChallenge = $this->CI->external_common_tokens->getExternalToken($playerId, QT_API);

			if (empty($playerId) || $paircodeChallenge != $codeChallenge){
				http_response_code(400);
					$output = array(
						'code'=> "INVALID_TOKEN",
						'message'=> "Invalid token or token-codeChallenge pair."
					);
					$this->CI->utils->debug_log('verifytoken failed =============  ', $output);
					return array('header' => $output,'status' => http_response_code(400));
				}

				$playerUsername = $this->CI->game_provider_auth->getGameUsernameByPlayerId($playerId, QT_API);

				$playerInfo = $this->getPlayerInfoByToken($token);

				$playerDetails = $this->CI->player->getPlayerById($playerInfo['playerId']);
				$prefix = substr($playerUsername, 0, $this->prefix_count);
				$mobile_player_cashier_url = $this->utils->getSystemUrl('player', $this->mobile_player_cashier_url);

				$output = array(
					'playerId' => $playerUsername,
					'currency' => $this->currency,
					'country' => $this->country,
					'gender' => $playerDetails['gender'],
					'birthDate' => $playerDetails['birthdate'],
					'cashierUrl' => $mobile_player_cashier_url,
					'displayName' => $playerUsername
				);

				$this->CI->utils->debug_log('verifytoken success =============  ', $output);
				return array('header' => $output,'status' => http_response_code(200));
			}
		break;
	}
}

# HELPER ########################################################################################################################################

	// function forwardCallback($url, $params) {
	// 	list($header, $resultXml, $statusCode, $statusText, $errCode, $error) = $this->httpCallApi($url, $params);
	// 	$this->CI->utils->debug_log('forwardCallback', $header, $resultXml, $statusCode, $statusText, $errCode, $error);
	// 	// print_r($resultXml);
	// 	return json_decode($resultXml,true);
	// }

	function forwardCallback($url, $params) {
		$this->CI->utils->debug_log('forwardCallback =============  ', $url, $params);
		$data = json_encode($params);
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$response = curl_exec( $ch );
	 	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 	$errCode = curl_errno($ch);
        $error = curl_error($ch);
        $this->CI->utils->debug_log('forwardCallback result=============  ', $response , $statusCode, $errCode , $error);
		return array('header' => json_decode($response, true),'status' => $statusCode);
	}

	# HELPER ########################################################################################################################################

	function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		$this->isMethod = self::POST_METHOD;
		$sbe_playerName = $playerName;
		$result = $this->getAccessToken();
		if (isset($result['access_token'])) {
			$game_username = $this->getGameUsernameByPlayerUsername($playerName);
			$referenceId = $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T'); //string; max-length: 34
			
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForDepositToGame',
				'playerName' => $playerName,
				'game_username' => $game_username,
				'sbe_playerName' => $sbe_playerName,
				'referenceId' => $referenceId,
				'amount' => $amount,
			);
			$params = array(
				"type" => "CREDIT",
				"referenceId" => $referenceId,
				"playerId" => $game_username,
				"amount" => $this->dBtoGameAmount($amount),
				"currency" => $this->currency,
			);
			return $this->callApi(self::API_depositToGame, json_encode($params), $context);
		}
	}

	function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceId = $this->getVariableFromContext($params, 'referenceId');
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForDepositToGame resultJson: ', count($resultJson));
		$playerId = $this->getPlayerIdInGameProviderAuth($game_username);
		// $result = array();

        $result = [
            'external_transaction_id' => '',
            'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
        ];

		if ($success) {
			$success = false;
			if ($resultJson['type'] == 'CREDIT' && $resultJson['status'] == self::STATUS_PENDING) {
				$confirmResult = $this->confirmTransaction($resultJson['id'], $game_username, $referenceId);
				if (!empty($confirmResult)) {
                    //get current sub wallet balance
                    // $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                    // $afterBalance = $playerBalance['balance'];
                    // // save logs
                    // $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

                    if ($this->verify_transfer_using_query_transaction) {
                        $query_transaction = $this->queryTransaction($resultJson['id'], ["game_username" => $game_username]);

                        if (isset($query_transaction['status'])) {
                            $result['didnot_insert_game_logs'] = true;
                            $result['external_transaction_id'] = $resultJson['id'];
                            $result['transfer_status'] = $query_transaction['status'];
                            if($this->revoke_after_complete_request){ #not mandatory to revoke token every api call
                            	$this->revokeToken($game_username);
                            }

                            if ($query_transaction['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED) {
                                $success = true;
                            }
                        }
                    } else {
                        $result['didnot_insert_game_logs'] = true;
                        $result['external_transaction_id'] = $resultJson['id'];
                        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                        if($this->revoke_after_complete_request){ #not mandatory to revoke token every api call
                            $this->revokeToken($game_username);
                        }
                        $success = true;
                    }
				}
			}
		}

		return array($success, $result);
	}

	private function revokeToken($game_username = null) {
		$this->isMethod = self::DELETE_METHOD;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForRevokeToken',
		);
		if(!empty($game_username)){
			$context['game_username'] = $game_username;
		}

		return $this->callApi(self::API_checkLoginToken, array(), $context);
	}

	function processResultForRevokeToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForRevokeToken resultJson: ', $resultJson);
		$success = false;
		if ($resultJson) {
			$success = true;
		}
		return array($success, $resultJson);
	}

	function confirmTransaction($transferId, $game_username, $referenceId) {
		$this->transferId = $transferId;
		$this->isMethod = self::PUT_METHOD;

		$this->CI->utils->debug_log('confirmTransaction access_token: ', $this->access_token, ' transferId: ', $transferId);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultConfirmTransaction',
			'game_username' => $game_username,
			'referenceId' => $referenceId,
			'transferId' => $transferId,
		);
		$params = array(
			"status" => self::STATUS_COMPLETED,
		);
		return $this->callApi(self::API_completeTransaction, json_encode($params), $context);
	}

	function processResultConfirmTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultConfirmTransaction resultJson: ', $resultJson);
		// $playerName = $this->getVariableFromContext($params, 'playerName');
		$referenceId = $this->getVariableFromContext($params, 'referenceId');
		$transferId = $this->getVariableFromContext($params, 'transferId');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		// $result = array();
		$result = array('response_result_id' => $responseResultId);
		if ($success && @$resultJson['status'] == self::STATUS_COMPLETED) {
			$result = array(
				'referenceId' => $referenceId,
				'transferId' => $transferId,
			);
		}
		return array($success, $result);
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson);
		if ($this->CI->utils->notEmptyInArray('errorcode', $resultJson) || $this->CI->utils->notEmptyInArray('error', $resultJson)) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('QT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
			$success = false;
		}

		return $success;
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return array("success" => true);
	}

	//===start createPlayer=====================================================================================
	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$success=true;

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array("success" => $success, array("playerName" => $playerName));
	}

	//===end createPlayer=====================================================================================

	//===start isPlayerExist======================================================================================
	// function isPlayerExist($playerName) {
	// 	return array("success" => true);
	// }
	// function processResultForIsPlayerExist($params) {
	// 	return array("success" => true);
	// }
	//===end isPlayerExist========================================================================================

	//===start queryPlayerInfo=====================================================================================
	function queryPlayerInfo($playerName) {
		return array("success" => true);
	}
	function processResultForQueryPlayerInfo($params) {
		return array("success" => true);
	}
	//===end queryPlayerInfo=====================================================================================

	//===start changePassword=====================================================================================
	function changePassword($playerName, $oldPassword, $newPassword) {
		return array("success" => true);
	}

	function processResultForChangePassword($params) {
		return array("success" => true);
	}
	//===end changePassword=====================================================================================

	//===start withdrawFromGame=====================================================================================
	function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		$this->isMethod = self::POST_METHOD;
		$sbe_playerName = $playerName;
		$result = $this->getAccessToken();
		$this->CI->utils->debug_log('withdrawFromGame token: ', $result);
		if (isset($result['access_token'])) {
			$game_username = $this->getGameUsernameByPlayerUsername($playerName);
			$referenceId = $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T'); //string; max-length: 34

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForWithdrawFromGame',
				'playerName' => $playerName,
				'game_username' => $game_username,
				'sbe_playerName' => $sbe_playerName,
				'referenceId' => $referenceId,
				'amount' => $amount,
			);
			$params = array(
				"type" => "DEBIT",
				"referenceId" => $referenceId,
				"playerId" => $game_username,
				"amount" => $this->dBtoGameAmount($amount),
				"currency" => $this->currency,
			);
			return $this->callApi(self::API_withdrawFromGame, json_encode($params), $context);
		}
	}

	function processResultForWithdrawFromGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$game_username = $this->getVariableFromContext($params, 'game_username');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$referenceId = $this->getVariableFromContext($params, 'referenceId');
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForWithdrawFromGame resultJson: ', $resultJson);
		$playerId = $this->getPlayerIdInGameProviderAuth($game_username);

        $result = [
            'external_transaction_id' => '',
            'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
        ];

		if ($success) {
            $success = false;
			if ($resultJson['type'] == 'DEBIT' && $resultJson['status'] == self::STATUS_PENDING) {
				$confirmResult = $this->confirmTransaction($resultJson['id'], $game_username, $referenceId);
				if (!empty($confirmResult)) {
                    //get current sub wallet balance
                    // $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                    // $afterBalance = $playerBalance['balance'];
                    // // save logs
                    // $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
                    if ($this->verify_transfer_using_query_transaction) {
                        $query_transaction = $this->queryTransaction($resultJson['id'], ["game_username" => $game_username]);

                        if (isset($query_transaction['status'])) {
                            $result['didnot_insert_game_logs'] = true;
                            $result['external_transaction_id'] = $resultJson['id'];
                            $result['transfer_status'] = $query_transaction['status'];
                            if($this->revoke_after_complete_request){ #not mandatory to revoke token every api call
                            	$this->revokeToken($game_username);
                            }

                            if ($query_transaction['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED) {
                                $success = true;
                            }
                        }
                    } else {
                        $result['didnot_insert_game_logs'] = true;
                        $result['external_transaction_id'] = $resultJson['id'];
                        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                        if($this->revoke_after_complete_request){ #not mandatory to revoke token every api call
                            $this->revokeToken($game_username);
                        }
                        $success = true;
                    }
				}
			}
		}

		return array($success, $result);
	}
	//===end withdrawFromGame=====================================================================================

	//===start login=====================================================================================
	function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		return array("success" => true);
	}
	//===end login=====================================================================================

	//===start logout=====================================================================================
	function logout($playerName, $password = null) {
		return array("success" => true);
	}
	function processResultForLogout($params) {
		return array("success" => true);
	}
	//===end logout=====================================================================================

	//===start updatePlayerInfo=====================================================================================
	function updatePlayerInfo($playerName, $infos) {
		return array("success" => true);
	}
	function processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson) {
		return array("success" => true);
	}
	//===end updatePlayerInfo=====================================================================================

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function QueryBalanceGameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

	//===start queryPlayerBalance=====================================================================================
	function queryPlayerBalance($playerName) {
		$token = $this->getAccessToken();
		$this->isMethod = self::GET_METHOD;
		$this->CI->utils->debug_log('queryPlayerBalance token: ', $token);
		if ($token) {
			$playerName = $this->getGameUsernameByPlayerUsername($playerName);
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryPlayerBalance',
				'playerName' => $playerName,
			);
			$params = array(
				"playerId" => $playerName,
			);
			return $this->callApi(self::API_queryPlayerBalance, $params, $context);
		}
	}
	function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance resultJson: ', $resultJson);
		$result = array();
		if ($success && isset($resultJson['amount'])) {
			$result["balance"] = $this->QueryBalanceGameAmountToDB($resultJson['amount']);
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
				$playerName, 'balance', @$resultJson['amount']);
			// $this->revokeToken();
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryPlayerBalance=====================================================================================

	//===start queryPlayerDailyBalance=====================================================================================
	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);
		$result = array();
		if ($daily_balance != null) {
			foreach ($daily_balance as $key => $value) {
				$result[$value['updated_at']] = $value['balance'];
			}
		}

		return array_merge(array('success' => true, "balanceList" => $result));
	}
	//===end queryPlayerDailyBalance=====================================================================================
	//===start queryGameRecords=====================================================================================
	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}
	//===end queryGameRecords=====================================================================================
	//===start checkLoginStatus=====================================================================================
	function checkLoginStatus($playerName) {
		return array("success" => true);
	}

	function processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson) {
		return array("success" => true);
	}
	//===end checkLoginStatus=====================================================================================

	//===start checkLoginToken=====================================================================================
	public function checkLoginToken($playerName, $token) {
		return array("success" => true);
	}
	public function processResultForCheckLoginToken($params) {
		return array("success" => true);
	}
	//===end checkLoginToken=====================================================================================

	//===start totalBettingAmount=====================================================================================
	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return array("success" => true);
	}
	//===end totalBettingAmount=====================================================================================
	//===start queryTransaction=====================================================================================
	function queryTransaction($transactionId, $extra) {
        $this->getAccessToken();
        $this->isMethod = self::GET_METHOD;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'extra' => $extra,
        ];
		if(isset($extra['game_username'])){
			$context['game_username'] = $extra['game_username'];
		}

        $params = [
            'transferId' => $transactionId
        ];

        return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJson);

        $result = [
            'external_transaction_id'=> '',
            'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
        ];

        if ($success && !empty($resultJson) && isset($resultJson['status'])) {
            // $success = false;

            if (isset($resultJson['id'])) {
                $result['external_transaction_id'] = $resultJson['id'];
            }

            if ($resultJson['status'] == self::STATUS_COMPLETED) {
                // $success = true;
                $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            }

            if ($resultJson['status'] == self::STATUS_PENDING) {
                $result['status'] = self::COMMON_TRANSACTION_STATUS_PROCESSING;
            }

            if ($resultJson['status'] == self::STATUS_CANCELLED) {
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        } else {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('<---------- processResultForQueryTransaction ---------->', 'processResultForQueryTransaction_result', 'result: ' . json_encode($result));

        return array($success, $result);
	}
	//===end queryTransaction=====================================================================================
	//===start queryForwardGame=====================================================================================

    /*
        Current available language for QT is:
        English, Chinese, Japanese,Korean and Thailand
        We support the following languages, English, Chinese, Japanese, Korea, Vietnamese, Thai, Russian, Spanish, Portugese,Turkish
		英文，中文，日文，韩文，越南文，泰文，俄文，西班牙文，葡萄牙文，土耳其
		language codes: "en_US", "zh_CN", "ja_JP", "ko_KR", "vi_VN", "th_TH", "ru_RU", "es_ES", "pt_PT", "tr_TR"
     */
    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        // echo $lang;
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'id_ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi_VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko_KR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th_TH';
                break;
            default:
                $lang = 'en_US';
                break;
        }
        return $lang;
    }

	/*
	     * Received New Game API Docs 06/01/2017
		 * QT+Transfer+Wallet+API_1-8_zh
		 *
		 * Remarks: 06/02/2017 - return to old param since same behavior with new param
    */
	function queryForwardGame($playerName, $extra) {
		$this->isMethod = self::POST_METHOD;
		$result = $this->getAccessToken();
        $this->CI->utils->debug_log('gameLauncher token: ', $result);
		$this->gameCodeForGameLaunch = $extra['game_code'];
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultQueryForwardGame',
			'playerName' => $playerName,
		);

        $go_back_url=$this->qt_return_url;
        if($this->go_back_www){
        	if(isset($extra['is_mobile']) && $extra['is_mobile']){
        		$go_back_url=$this->CI->utils->getSystemUrl('m');
        	}else{
        		$go_back_url=$this->CI->utils->getSystemUrl('www');
        	}
        }

        if($extra['game_mode'] == 'trial'){
            $params = array(
                "currency" => $this->currency,
                "lang" => $this->getLauncherLanguage($extra['language']),
                "mode" => $extra['game_mode'],
                "device" => $extra['is_mobile'] ? 'mobile' : 'desktop', //Note: This is deprecated already
                "returnUrl" => $go_back_url,
            );
            $this->CI->utils->debug_log('QT gameLauncher params >----------------------------------------------------> ', $params);
            return $this->callApi(self::API_queryForwardGame, json_encode($params), $context);

        } else {
			if (isset($result['access_token'])) {
				$playerName = $this->getGameUsernameByPlayerUsername($playerName);
				$this->CI->utils->debug_log('gameLauncher playerName: ', $playerName);

				if (@$extra['game_mode'] == 'trial') {
					$extra['game_mode'] = 'demo';
				}

	            $params = array(
	                "playerId" => $playerName,
	                "currency" => $this->currency,
	                "country" => $this->country,
	                "lang" => $this->getLauncherLanguage($extra['language']),
	                "mode" => $extra['game_mode'],
	                "device" => (isset($extra['is_mobile']) && $extra['is_mobile']) ? 'mobile' : 'desktop', //Note: This is deprecated already
	                "clientType" => isset($extra['game_type']) ? $extra['game_type'] : null,
	                "returnUrl" => $go_back_url,
	            );
				$this->CI->utils->debug_log('gameLauncher params: ', $params);
				return $this->callApi(self::API_queryForwardGame, json_encode($params), $context);
			}else{
				return array("success" => false, []);
			}
		}
	}

	function processResultQueryForwardGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultLaunchGame resultJson: ', $resultJson);
		$result = array();
		if ($success && isset($resultJson['url'])) {
			$this->CI->utils->debug_log('Launch Game: ', 'playerName', $playerName, 'balance', @$resultJson['url']);
			$result['url'] = $resultJson['url'];
			$result['iframeName'] = 'QT_API';
		} else {
			$success = false;
		}

		return array($success, $result);
	}
	//===end queryForwardGame=====================================================================================
	//===start syncGameRecords=====================================================================================

	// const DEFAULT_DATETIME_ADJUST = '-5 minutes';
	const START_PAGE = 0;
	const ITEM_PER_PAGE = 100;
	function syncOriginalGameLogs($token) {
		//call report api
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		// $startDate->modify($this->getDatetimeAdjust());
		// $this->CI->utils->debug_log('from', $startDate, 'to', $endDate);

		$result = $this->getAccessToken();
		$this->isMethod = self::GET_METHOD;
		$this->CI->utils->debug_log('syncOriginalGameLogs token: ', $result);
		if (isset($result['access_token'])) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);

			$page = self::START_PAGE;
			$done = false;
			$success = true;

			$params = array(
				"from" => $startDate->format("Y-m-d\TH:i:s"),
				"end" => $endDate->format("Y-m-d\TH:i:s"),
				"size" => self::ITEM_PER_PAGE,
				"page" => $page,
				"status" => self::STATUS_COMPLETED,
			);

			$origResult = $this->callApi(self::API_syncGameRecords, http_build_query($params), $context);
			$nextUrlLink = isset($origResult['links']) ? @$origResult['links'] : null;
			$next = isset($origResult['next']) ? @$origResult['next'] : null;
			if ($next) {
				while (!$done) {
					$nextResult = $this->callApi(self::API_syncNextGameRecords, $nextUrlLink, $context);
					$done = true;
					if (isset($nextResult['next'])) {
						// $done = false;
						$nextUrlLink = isset($nextResult['links']) ? @$nextResult['links'] : null;
						$done = empty($nextUrlLink);
						$this->CI->utils->debug_log('=================>', 'done', $done, 'is next', @$nextResult['next'], 'links: ', @$nextResult['links']);
					}
					if ($done) {
						$success = true;
					}
					$this->CI->utils->debug_log('============> mydone!', $done);
				}
			}

			$this->CI->utils->debug_log('========> finish!', $done);
			// $this->revokeToken();
			return $success;
		}
	}

	function processResultForSyncGameRecords($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForSyncGameRecords resultJson: ', count($resultJson));
		$result = array();
		if ($success && isset($resultJson['items'])) {
			$gameRecords = $resultJson['items'];
			if (empty($gameRecords) || !is_array($gameRecords)) {
				$this->CI->utils->debug_log('No records', $gameRecords);
			}
			$this->CI->load->model(array('qt_game_logs'));
			$this->CI->utils->debug_log('QT =================> gameRecords', count($gameRecords));
			$availableRows = $this->CI->qt_game_logs->getAvailableRows($gameRecords);
			$this->CI->utils->debug_log('QT =================> availableRows', count($availableRows), 'responseResultId', $responseResultId);
			foreach ($availableRows as $record) {
				if ($record['status'] == self::STATUS_COMPLETED) {

					$completed = date('Y-m-d H:i:s', strtotime(substr($record['completed'],0,19)));
					$initiated = date('Y-m-d H:i:s', strtotime(substr($record['initiated'],0,19)));

					$qtGameData = array(
						'transId' => $record['id'],
						'status' => $record['status'],
						'totalBet' => $record['totalBet'],
						'totalPayout' => $record['totalPayout'],
						'currency' => $record['currency'],
						'initiated' => $this->gameTimeToServerTime($initiated),
						'completed' => $this->gameTimeToServerTime($completed),
						'operatorId' => $record['operatorId'],
						'playerId' => $record['playerId'],
						'device' => $record['device'],
						'gameProvider' => $record['gameProvider'],
						'gameId' => $record['gameId'],
						'gameCategory' => $record['gameCategory'],
						'gameClientType' => $record['gameClientType'],
						'external_uniqueid' => $record['id'],
						'response_result_id' => $responseResultId,
					);
					$result = $qtGameData;
					$this->CI->qt_game_logs->insertQtGameLogs($qtGameData);
				}
			}

			if (isset($resultJson['links'])) {
				$result['next'] = true;
				$result['links'] = $resultJson['links'][0]['href'];
			}

			$success = true;

		} else {
			$success = false;
		}

		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());

		$rlt = array('success' => true);
		$this->CI->load->model(array('game_logs', 'player_model', 'qt_game_logs'));
		$result = $this->CI->qt_game_logs->getQtGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

		$cnt = 0;
		if ($result) {
			$unknownGame = $this->getUnknownGame();
			foreach ($result as $qtdata) {
				$cnt++;
				$player = $this->CI->player_model->getPlayerById($qtdata->player_id);
				$gameDate = new \DateTime($qtdata->completed);
				$gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);

				$bet_amount = $qtdata->bet_amount;
				$result_amount = $qtdata->result_amount - $bet_amount;

				$game_description_id = $qtdata->game_description_id;
				$game_type_id = $qtdata->game_type_id;

				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($qtdata, $unknownGame);

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				$extra = array('table' => $qtdata->transId, 'sync_index' => $qtdata->id); //add round

				$this->syncGameLogs($game_type_id,
					$game_description_id,
					$qtdata->game_code,
					$qtdata->game_type,
					$qtdata->game,
					$qtdata->player_id,
					$qtdata->username,
					$this->gameAmountToDB($bet_amount),
					$this->gameAmountToDB($result_amount),
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$qtdata->external_uniqueid,
					$gameDateStr,
					$gameDateStr,
					$qtdata->response_result_id,
					1,
					$extra
				);
			}
		}
		return $rlt;
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$externalGameId = $row->game;
		$extra = array('game_code' => $row->gameId,
			'moduleid' => $row->response_result_id, 'clientid' => $row->gameId, 'external_game_id' => $row->gameId);
		return $this->processUnknownGame(
			$row->game_description_id, $row->game_type_id,
			$row->gameId, $row->gameId, $externalGameId, $extra,
			$unknownGame);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		if (!empty($playerNames)) {

			$token = $this->getAccessToken();
			if (!empty($token)) {
				foreach ($playerNames as $username) {
					$playerName = $this->getGameUsernameByPlayerUsername($username);
					$playerName = $username;
					if ($playerName) {
						$this->isMethod = self::GET_METHOD;
						$this->CI->utils->debug_log('QT queryPlayerBalance token: ', $token);
						if ($token) {
							$context = array(
								'callback_obj' => $this,
								'callback_method' => 'processBatchQueryPlayerBalance',
								'playerName' => $playerName,
							);
							$params = array(
								"playerId" => $playerName,
							);
							return $this->callApi(self::API_queryPlayerBalance, $params, $context);
						}
					}
				}
			} else {
				$this->CI->utils->error_log('empty token , get access token failed');
				return $this->returnFailed('empty token , get access token failed');
			}
		} else {
			$this->CI->utils->debug_log('empty player names');
			return ['success' => true];
		}
	}

	public function processBatchQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForBatchQueryPlayerBalance resultJson: ', $resultJson);

		$result = array();
		if ($success && isset($resultJson['amount'])) {
			$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			$this->CI->utils->debug_log('QT playerName: ', $playerName, ' playerId: ', $playerId, ' balance: ', $resultJson['amount']);
			$result["balances"][$playerId] = $this->QueryBalanceGameAmountToDB($resultJson['amount']);
			$this->updatePlayerSubwalletBalance($playerId, $this->QueryBalanceGameAmountToDB($resultJson['amount']));
			// $this->revokeToken();
		} else {
			$success = false;
		}

		return array($success, $result);
	}

	//===end syncGameRecords=====================================================================================
	//===start syncBalance=====================================================================================
	//===end syncBalance=====================================================================================

	public function queryBetDetailLink($playerUsername, $betId = null, $extra = null) {
		$result = $this->getAccessToken();
		$this->CI->utils->debug_log('queryPlayerBalance token: ', $result);
		if (isset($result['access_token'])) {
			$this->isMethod = self::POST_METHOD;
			$playerName = $this->getGameUsernameByPlayerUsername($playerUsername);
			$this->playerName = $playerName;
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryBetDetailLink',
			);
			$params = array(
				"currency" => $this->currency,
				"country" => $this->country,
			);
			return $this->callApi(self::API_queryBetDetailLink, json_encode($params), $context);
		}
	}

	public function processResultForQueryBetDetailLink($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultForQueryBetDetailLink resultJson: ', $resultJson);
		$result = array();
		if ($success && isset($resultJson['url'])) {
			$result['url'] = $resultJson['url'];
		} else {
			$success = false;
		}

		return array($success, $result);
	}

	public function getGameProviderGameList(){
		$token = $this->getAccessToken();
		$this->isMethod = self::GET_METHOD;
		$this->CI->utils->debug_log('getGameList token: ', $token);
		if ($token) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForGetGameProviderList',
			);
			$params = array(
				"Authorization" => $token,
			);
			return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
		}
	}

	public function processResultForGetGameProviderList($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);

		$result = [];
		$success = isset($resultJson['totalCount']) ? true:false;
		if ($success && $resultJson['totalCount'] > 0) {
			$list = [];
			$defaultCategory = 1;

			$gameTypeList =[
				'SLOT' => 'slots',
				'TABLEGAME' => 'table_and_cards',
				'BINGO' => 'others',
				'INSTANTWIN' => 'others',
			];

			$this->CI->load->model(['game_description_model','game_type_model']);
			$dbGameTypeList = $this->getDBGametypeList();
			foreach ($resultJson['items'] as $key => $gameDetails) {
				$gameTypeCategoryList = explode("/", $gameDetails['category']);
				$game_type_code = isset($gameTypeList[$gameTypeCategoryList[$defaultCategory]]) ? $gameTypeList[$gameTypeCategoryList[$defaultCategory]]:'others';
				$gameTypeId = $dbGameTypeList[$game_type_code]['id'];

				$lang_arr = [
					self::INT_LANG_ENGLISH => $gameDetails['name'],
					self::INT_LANG_CHINESE => $gameDetails['name'],
					self::INT_LANG_INDONESIAN => $gameDetails['name'],
					self::INT_LANG_VIETNAMESE => $gameDetails['name'],
					self::INT_LANG_KOREAN => $gameDetails['name']
				];

				$list[$key] = [
					'game_platform_id' 	 => $this->getPlatformCode(),
					'game_type_id' 	 => $gameTypeId,
					'game_code' 		 => $gameDetails['id'],
					'attributes' 		 => '{"game_launch_code":"'.$gameDetails['id'].'""}',
					'english_name' 		 => $gameDetails['name'],
					'external_game_id' 	 => $gameDetails['id'],
					'enabled_freespin' 	 => !empty($gameDetails['freeSpinTrigger']),
					'sub_game_provider'  => $gameDetails['provider'],
					'enabled_on_android' => $this->checkGameAttribute('versions','HTML5',$gameDetails['clientTypes']),
					'enabled_on_ios' 	 => $this->checkGameAttribute('versions','HTML5',$gameDetails['clientTypes']),
					'status' 			 => Game_description_model::DB_TRUE,
					'flash_enabled' 	 => $this->checkGameAttribute('versions','FLASH',$gameDetails['clientTypes']),
					'mobile_enabled' 	 => $this->checkGameAttribute('versions','HTML5',$gameDetails['clientTypes']),
					'html_five_enabled'  => $this->checkGameAttribute('versions','HTML5',$gameDetails['clientTypes']),
					'game_name' 		 => $this->processLanguagesToJson($lang_arr),
				];

			}
			$result = $this->CI->game_description_model->syncGameDescription($list,null, false, true, null, $this->getGameListAPIConfig());
		}

		return array($success, $result);
	}

	private function checkGameAttribute($type,$key,$data){
		switch ($type) {
			case 'versions':
				$result = array_search($key, $data) ? Game_description_model::DB_TRUE:Game_description_model::DB_FALSE;
		}
		return $result;
	}

}

/*end of file*/