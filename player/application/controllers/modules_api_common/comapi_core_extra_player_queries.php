<?php

/**
 * Api_common core module: extra player query methods
 * Built 4/22/2020
 * @see			api_common.php
 *
 * @author		Rupert Chen
 * @copyright   tot April 2020
 */
trait comapi_core_extra_player_queries {

	public function getPlayerTransferStatus() {
		$api_key = $this->input->post('api_key');
		if (!$this->__checkKey($api_key)) { return; }

		try {
			$token          = $this->input->post('token', true);
			$username       = $this->input->post('username', true);
			$transfer_id    = $this->input->post('transfer_id', true);

			$request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'transfer_id' => $transfer_id ];
			$this->comapi_log(__METHOD__, 'request', $request);

			$this->load->model([ 'comapi_reports' ]);

			// 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            //
            if (empty($transfer_id)) {
            	throw new Exception('Invalid transfer_id', self::CODE_GPXS_INVALID_XFER_ID);
            }

            $xfer_res = $this->comapi_reports->expq_player_single_transfer($player_id, $transfer_id);

            if (count($xfer_res) == 0) {
            	throw new Exception('No transfer record found by given transfer_id', self::CODE_GPXS_NO_XFER_FOUND_BY_GIVEN_ID);
            }

			// Point of success --------------------------------------------------------
			$ret = [
				'success'   => true,
				'code'      => 0,
				'mesg'      => lang('Successfully got transfer record for player'),
				'result'    => $xfer_res
			];
		}
		catch (Exception $ex) {
			$ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
			$this->comapi_log(__METHOD__, 'Exception', $ex_log);

			$ret = [
				'success'   => false,
				'code'      => $ex->getCode(),
				'mesg'      => $ex->getMessage(),
				'result'    => null
			];
		}
		finally {
			$this->comapi_log(__METHOD__, 'Response', $ret);
			$this->returnApiResponseByArray($ret);
		}
	}

	public function summaryPlayerWithdrawalConditions() {
		$api_key = $this->input->post('api_key');
		if (!$this->__checkKey($api_key)) { return; }

		try {
			$token          = $this->input->post('token', true);
			$username       = $this->input->post('username', true);
			$transfer_id    = $this->input->post('transfer_id', true);

			$request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'transfer_id' => $transfer_id ];
			$this->comapi_log(__METHOD__, 'request', $request);

			// 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $this->load->model([ 'withdraw_condition' ]);

            $res = $this->withdraw_condition->computePlayerWithdrawalConditions($player_id);

            $wd_summary = [
            	'required_bet'		=> $res['totalRequiredBet'] ,
            	'current_total_bet'	=> $res['totalPlayerBet'] ,
            	'unfinished'		=> $res['unfinished']
            ];

			// Point of success --------------------------------------------------------
			$ret = [
				'success'   => true,
				'code'      => 0,
				'mesg'      => lang('Successfully calculated withdrawal condition summary for player'),
				'result'    => $wd_summary
			];
		}
		catch (Exception $ex) {
			$ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
			$this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

			$ret = [
				'success'   => false,
				'code'      => $ex->getCode(),
				'mesg'      => $ex->getMessage(),
				'result'    => null
			];
		}
		finally {
			$this->comapi_log(__FUNCTION__, 'Response', $ret);
			$this->returnApiResponseByArray($ret);
		}
	} // End function summaryPlayerWithdrawalCondition

	/**
	 * Returns player's withdrawals with cancellable property, OGP-22728
	 *
	 * @uses	POST:api_key	string		The api_key, as md5 sum. Required.
	 * @uses	POST:token		string		Effective token for player.
	 * @uses	POST:username	string		Player username.  Required.
	 * @uses	POST:time_from	datetime	Start time of query
	 * @uses	POST:time_to    datetime	End time of query
	 * @uses	POST:limit		int			Paging.  Count of records to return.  Defaults to 10.
	 * @uses	POST:offset		int			Paging.  Starting point of records to return.
	 *
	 * @see		models/comapi_reports
	 *
	 * @return	JSON	Standard return object of [ success, code, mesg, result ]
	 *
	 */
	public function getPlayerWithdrawals() {
		$api_key    = $this->input->post('api_key'  , 1);
        if (!$this->__checkKey($api_key)) { return; }

		$this->load->model([ 'player_model', 'comapi_reports' ]);

		try {
			$username 	 = $this->input->post('username'	, true);
			$token 		 = $this->input->post('token'		, true);
			$time_start  = $this->input->post('time_from'	, true);
			$time_end 	 = $this->input->post('time_to'		, true);
			$limit 		 = intval($this->input->post('limit'		, true));
			$offset 	 = intval($this->input->post('offset'		, true));

			$player_id = $this->player_model->getPlayerIdByUsername($username);
			if (empty($player_id)) {
				throw new Exception(lang('Username invalid'), self::CODE_COMMON_INVALID_USERNAME);
			}

			// Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $limit_max = $this->utils->getConfig('comapi_get_player_reports_query_limit');

			if (empty($limit) || $limit < 0 || $limit > $limit_max) {
				$limit = $limit_max;
			}

			$result = $this->comapi_reports->player_withdrawals($player_id, $time_start, $time_end, $limit, $offset, 'with_cancel_info');

            $res = $result['rows'];
            if (empty($res)) { $res = []; }

            // point of successful return

			$ret = [
				'success'	=> true,
	    		'code'		=> 0,
	    		'mesg'		=> lang('Pending withdrawals of player retrieved successfully'),
	    		'result'	=> $res
			];

			$this->comapi_log(__METHOD__, 'successful', $ret);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
		}
		finally {
			$this->returnApiResponseByArray($ret, 'empty_array_allowed');
		}
	} // End function getPlayerWithdrawals()

	/**
	 * Cancels given withdrawal for player, OGP-22728
	 *
	 * @uses	POST:api_key	string		The api_key, as md5 sum. Required.
	 * @uses	POST:token		string		Effective token for player.
	 * @uses	POST:username	string		Player username.  Required.
	 * @uses	POST:tx_code	string		tx_code (secure_id) of withdrawal item
	 *
	 * @see		models/comapi_reports
	 *
	 * @return	JSON	Standard return object of [ success, code, mesg, result ]
	 *
	 */
	public function cancelPlayerWithdrawal() {
		$api_key    = $this->input->post('api_key'  , 1);
        if (!$this->__checkKey($api_key)) { return; }

		$this->load->model([ 'wallet_model' ]);

		try {
			$username 	 = $this->input->post('username'	, true);
			$token 		 = $this->input->post('token'		, true);
			$secure_id   = $this->input->post('tx_code'		, true);

			$plcapi_request = [ 'api_key' => $api_key, 'username' => $username, 'secure_id' => $secure_id ];
			$this->utils->debug_log(__FUNCTION__, 'request', $plcapi_request);

			$player_id = $this->player_model->getPlayerIdByUsername($username);
			if (empty($player_id)) {
				throw new Exception(lang('Username invalid'), self::CODE_COMMON_INVALID_USERNAME);
			}

			// Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            // 1. check if config is allowed
            if (!$this->utils->getConfig('enabled_player_cancel_pending_withdraw')) {
            	throw new Exception(lang('Withdrawal cancellation is disabled by system'), self::CODE_PWC_FUNCTION_DISABLED);
            }

            // 2. check if the withdrawal belongs to player
            $wx_item = $this->wallet_model->getWalletAccountByTransactionCode($secure_id);
            if ($player_id != $wx_item['playerId']) {
            	throw new Exception(lang('Withdrawal not found for player'), self::CODE_PWC_WX_NOT_FOUND_FOR_PLAYER);
            }

            // 3. check status of the withdrawal
            if (Wallet_model::REQUEST_STATUS != $wx_item['dwStatus']) {
            	throw new Exception(lang('Withdrawal not cancellable or already cancelled'), self::CODE_PWC_WX_STATUS_NOT_CANCELLABLE);
            }

			// 4. check locked of the withdrawal
			$lockedByUserId = $this->wallet_model->checkWithdrawLocked($wx_item['walletAccountId']);
			if ($lockedByUserId) {
            	throw new Exception(lang('Withdrawal has been locked'), self::CODE_PWC_WX_LOCKED_BY_ADMINUSER);
            }

            $cancel_res = $this->comapi_lib->cancel_player_withdraw($wx_item['walletAccountId'], $wx_item['playerId']);

            if ($cancel_res == false) {
            	throw new Exception(lang('Withdrawal cancellation failed'), self::CODE_PWC_WX_CANCELLATION_FAILED);
            }

            // point of successful return
			$ret = [
				'success'	=> true,
	    		'code'		=> 0,
	    		'mesg'		=> lang('Player withdrawal cancelled successfully'),
	    		'result'	=> null
			];

			$this->comapi_log(__METHOD__, 'successful', $ret);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	} // End function cancelPlayerWithdrawal()


	public function getPlayerSalesAgent() {
		$api_key    = $this->input->post('api_key'  , 1);
		if (!$this->__checkKey($api_key)) { return; }

		$this->load->model([ 'player_model', 'sales_agent' ]);

		try {
			$username 	 = $this->input->post('username'	, true);
			$token 		 = $this->input->post('token'		, true);

			if(!$this->utils->getConfig('enabled_sales_agent')){
				throw new Exception(lang('Not Enabled'), self::CODE_SUCCESS);
			}

			$player_id = $this->player_model->getPlayerIdByUsername($username);
			if (empty($player_id)) {
				throw new Exception(lang('Username invalid'), self::CODE_COMMON_INVALID_USERNAME);
			}

			// Check player token
			$logcheck = $this->_isPlayerLoggedIn($player_id, $token);
			if ($logcheck['code'] != 0) {
			    throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
			}

			/** @var \Sales_agent $sales_agent */
			$sales_agent = $this->{"sales_agent"};
			$player_sales_agent = $sales_agent->getPlayerSalesAgentDetailById($player_id);

			$this->comapi_log(__METHOD__, 'player_sales_agent', $player_sales_agent);

			$res = [
				'sales_agent_name' => !empty($player_sales_agent['realname']) ? $player_sales_agent['realname'] : '',
				'chat_platform1' => isset($player_sales_agent['chat_platform1']) ? $player_sales_agent['chat_platform1'] : '',
				'chat_platform2' => isset($player_sales_agent['chat_platform2']) ? $player_sales_agent['chat_platform2'] : ''
			];

			$ret = [
				'success'	=> true,
				'code'		=> 0,
				'mesg'		=> lang('sales_agent.get.player.sales.service.information.success'),
				'result'	=> $res
			];

			$this->comapi_log(__METHOD__, 'successful', $ret);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
				'success'	=> false,
				'code'		=> $ex->getCode(),
				'mesg'		=> $ex->getMessage(),
				'result'	=> null
			];
		}
		finally {
			$this->returnApiResponseByArray($ret, 'empty_array_allowed');
		}
	} // End function getPlayerSalesAgent()
} // End of trait comapi_core_extra_player_queries
