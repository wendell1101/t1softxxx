<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting plat form code
 * * create a player
 * * Getting Player balance
 * * Deposit to game
 * * withdraw from game
 * * Getting the constant variable of the AG_FTP
 *
 * The functions implemented by child class:
 * * Populating game form parameters
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

/*
about agency credit wallet
I think we can add a game_api_agency for this
it's like sub-wallet
agent can transfer credit to this sub-wallet
this sub-wallet can transfer to main wallet
but main wallet can't transfer to this agent credit wallet
disable deposit/withdraw if credit mode is on
if all wallet is empty, credit mode is off
*/
class Game_api_agency extends Abstract_game_api {

	public function getPlatformCode() {
		return AGENCY_API;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$success = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		return array('success' => $success);
	}

	public function queryPlayerBalance($playerName) {
		$playerId = $this->getPlayerIdInPlayer($playerName);
		$subwallet = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
		return array('success' => TRUE, 'balance' => floatval($subwallet->totalBalanceAmount));
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id = NULL) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$afterBalance = $this->queryPlayerBalance($playerName)['balance'];
		$response_result_id = NULL;
		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $response_result_id, $this->transTypeMainWalletToSubWallet());
		// $this->CI->player_model->updatePlayer($playerId, array('credit_mode'=> TRUE));
		return array('success' => TRUE,'response_result_id' => $response_result_id);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = NULL) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$afterBalance = $this->queryPlayerBalance($playerName)['balance'];
		$response_result_id = NULL;
		$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $response_result_id, $this->transTypeSubWalletToMainWallet());
		return array('success' => TRUE,'response_result_id' => $response_result_id);
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = NULL) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = NULL, $dateTo = NULL) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = NULL) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function queryForwardGame($playerName, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token) {
		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		return $this->returnUnimplemented();
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = NULL, $extra = NULL, $resultObj = NULL) {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		return $this->returnUnimplemented();
	}

}