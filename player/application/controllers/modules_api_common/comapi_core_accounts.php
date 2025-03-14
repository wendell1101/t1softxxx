<?php

/**
 * Api_common core module: accounts
 * Separated 4/01/2020
 *
 * This trait has following member methods:
 * 		// add account
 * 		public function addPlayerDepositAccount()
 * 		public function addPlayerWithdrawAccount()
 * 		protected function _addPlayerAccount_general($mode)
 *   	// list account
 * 		public function queryDepositBank()
 * 		public function listPlayerDepositAccounts()
 * 		public function listPlayerWithdrawAccounts()
 * 		// remove account (not implemented)
 * 		public function removePlayerWithdrawAccount()
 * 		public function removePlayerDepositAccount()
 * 		// set default
 * 		public function setPlayerWithdrawAccountDefault()
 *
 * @see		api_common.php
 */
trait comapi_core_accounts {

    /**
     * Adds deposit account for player
     * See _addPlayerAccount_general() below for detailed POST arguments
     * @see 	comapi_core_accounts::_addPlayerAccount_general()
     * @return	JSON	General JSON return object [ success, code, message, result ]
     */
    public function addPlayerDepositAccount() {
    	$this->_addPlayerAccount_general('deposit');
    }

    /**
     * Adds withdrawal account for player
     * See _addPlayerAccount_general() below for detailed POST arguments
     * @see 	comapi_core_accounts::_addPlayerAccount_general()
     * @return	JSON	General JSON return object [ success, code, message, result ]
     */
    public function addPlayerWithdrawAccount() {
    	$this->_addPlayerAccount_general('withdraw');
    }

    /**
     * General method for adding player bank account
     * Worker method for addPlayerWithdrawAccount/addPlayerDepositAccount, do not call directly
     * OGP-13942: Incorporate field checks set in Player Center Financial Account Settings of SBE
     * @see		comapi_core_accounts::addPlayerWithdrawAccount()
     * @see		comapi_core_accounts::addPlayerDepositAccount()
     * @see     SBE/payment_management/viewPlayerCenterFinancialAccountSettings
     *
     * @param	string	$mode			account mode, 'deposit' or 'withdraw'
     *
     * @uses	string	POST: api_key		api key given by system
     * @uses	string	POST: username		Player username
     * @uses	string	POST: token			Player's login token
     * @uses	int		POST: bankTypeId	bankTypeId listed in API queryDepositWithdrawalAvailableBank return
     * @uses 	numeric	POST: bankAccNum	Bank account number
     * @uses 	string	POST: bankAccName	Bank account name
     * @uses 	string	POST: bankAddress	Branch address
     * @uses 	string	POST: province		Province the branch is in
     * @uses 	string	POST: city			City the branch is in
     * @uses 	string	POST: branch		Branch name
     * @uses 	string	POST: phone			Branch phone
     *
     * @return	JSON	General JSON return object [ success, code, message, result ]
     */
    protected function _addPlayerAccount_general($mode) {
    	$api_key = $this->input->post('api_key');

		if (!$this->__checkKey($api_key)) { return; }

		$res = null;

		try {
			$this->load->model([ 'playerbankdetails' , 'banktype', 'operatorglobalsettings' ]);

			$token					= $this->input->post('token'		, true);
    		$username				= $this->input->post('username'		, true);

			$post_keys = [
				'bankTypeId'			=> 'bankTypeId'		,
				'bankAccountNumber'		=> 'bankAccNum'		,
				'bankAccountFullName' 	=> 'bankAccName'	,
				'bankAddress'			=> 'bankAddress'	,
				'province'				=> 'province' 		,
				'city'					=> 'city' 			,
				'branch'				=> 'branch' 		,
				'phone'					=> 'phone'
			];

			$args = [];
			foreach ($post_keys as $arg_key => $key) {
				$args[$arg_key] = trim($this->input->post($key, true));
			}

			$request = [ 'api_key' => $api_key, 'token' => $token, 'username' => $username, 'mode' => $mode, 'args' => $args ];
			$this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Username check
			$player_id	= $this->player_model->getPlayerIdByUsername($username);
    		if (empty($player_id)) {
    			throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
    		}

            // 0b: Token check
    		if (!$this->__isLoggedIn($player_id, $token)) {
    			throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
    		}

            // 1: Number of player's accounts

            $rules_fao = $this->comapi_lib->fin_acc_rules_others();

            $pa_mesg = null;
            $mesg_extra = [];
            // 1a: Withdraw mode, check for number of withdraw accounts
            if ($mode == 'withdraw') {
                $wd_accounts = $this->comapi_lib->player_withdraw_accounts($player_id, false, 'probe_only');
                $player_allowed_to_add = PlayerBankDetails::AllowAddBankDetail(PlayerBankDetails::WITHDRAWAL_BANK, $wd_accounts, $pa_mesg, null, $player_id, $mesg_extra);
                // AllowAddBankDetail return extra:
                //      limit (float), current (float),
                //      rule (fixed_limit|tiered_limits),
                //      rule_Type (1: withdraw sum|2: deposit sum |3: bet sum),
                //      rule_value (float) }
                $this->comapi_log(__METHOD__, 'AllowAddBankDetail return', [ 'mode' => $mode, 'result' => $player_allowed_to_add, 'mesg' => $pa_mesg, 'extra' => $mesg_extra ]);
                if (!$player_allowed_to_add) {
                    $res = [ 'max_num_of_withdraw_accounts' => $mesg_extra['limit'] ];
                    throw new Exception(sprintf(lang("Max number of withdraw accounts is %d, cannot add any more"), $mesg_extra['limit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }
            // 1b: Deposit mode, check for number of deposit accounts
            else if ($mode == 'deposit') {
                $dp_accounts = $this->playerbankdetails->getDepositBankDetails($player_id);
                $player_allowed_to_add = PlayerBankDetails::AllowAddBankDetail(PlayerBankDetails::DEPOSIT_BANK, $dp_accounts, $pa_mesg, null, $player_id, $mesg_extra);
                $this->comapi_log(__METHOD__, 'AllowAddBankDetail return', [ 'mode' => $mode, 'result' => $player_allowed_to_add, 'mesg' => $pa_mesg, 'extra' => $mesg_extra  ]);
                if (!$player_allowed_to_add) {
                    $res = [ 'max_num_of_withdraw_accounts' => $mesg_extra['limit'] ];
                    throw new Exception(sprintf(lang("Max number of deposit accounts is %d, cannot add any more"), $mesg_extra['limit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }

            // 1a: Withdraw mode, check for number of withdraw accounts
            // if ($mode == 'withdraw' && $rules_fao['account_max_count_withdraw'] > 0) {
            //     $this->comapi_log(__METHOD__, 'account_max_count_withdraw', $rules_fao['account_max_count_withdraw']);
            //     // Suppress exception CODE_LPWA_NO_WITHDRAW_ACCOUNT from Comapi_lib - 8/30/2019
            //     $wd_accounts = $this->comapi_lib->player_withdraw_accounts($player_id, false, 'probe_only');
            //     // OGP-15852: Add empty() tests for php 7.2
            //     if (!empty($wd_accounts) && count($wd_accounts) >= $rules_fao['account_max_count_withdraw']) {
            //         $res = [ 'max_num_of_withdraw_accounts' => $rules_fao['account_max_count_withdraw'] ];
            //         throw new Exception(sprintf(lang("Player already had max number (%d) of withdraw accounts, cannot add any more"), $rules_fao['account_max_count_withdraw']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
            //     }
            // }
            // // 1b: Deposit mode, check for number of deposit accounts
            // else if ($mode == 'deposit' && $rules_fao['account_max_count_deposit'] > 0) {
            //     $this->comapi_log(__METHOD__, 'account_max_count_deposit', $rules_fao['account_max_count_deposit']);
            //     $dp_accounts = $this->playerbankdetails->getDepositBankDetails($player_id);
            //     // OGP-15852: Add empty() tests for php 7.2
            //     if (!empty($dp_accounts) && count($dp_accounts) >= $rules_fao['account_max_count_deposit']) {
            //         $res = [ 'max_num_of_deposit_accounts' => $rules_fao['account_max_count_deposit'] ];
            //          throw new Exception(sprintf(lang("Player already had max number (%d) of deposit accounts, cannot add any more"), $rules_fao['account_max_count_deposit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
            //     }
            // }

    		// 2: bankTypeId
    		$bank = $this->banktype->getBankTypeById($args['bankTypeId']);
    		if (empty($bank)) {
    			throw new Exception(lang('bankTypeId invalid, not listed in system supported bank list'), self::CODE_PAO_BANKTYPEID_INVALID);
    		}

            // 3: Check for bankAccountNumber, use rules_faa['regex_accnum'] to match
            $rules_faa = $this->comapi_lib->fin_acc_rules_acc($args['bankTypeId']);

            $this->comapi_log(__METHOD__, 'fa rules-accounts', $rules_faa);
            if (!preg_match($rules_faa['regex_accnum'], $args['bankAccountNumber'])) {
                throw new Exception(sprintf(lang('bankAccNum empty or does not comply with the format.  The format is: %s'), $rules_faa['format_accnum']), self::CODE_PAO_BANKACCNUM_INVALID);
            }

    		// 4: Check for bankAccountFullName by $rules_faa options
    		$player_details = $this->player_model->getAllPlayerDetailsById($player_id);
            // 4a: if bankAccountFullName empty
            if (empty($args['bankAccountFullName'])) {
                throw new Exception(lang('bankAccName empty'), self::CODE_PAO_BANKACCNAME_INVALID);
            }
            // 4b: !account_name_modify_allowed && (player_details.first_name not set || player_details.first_name != bankAccountFullName)
            // else if (!$rules_faa['account_name_allow_modify_by_players'] &&
            //     ( empty($player_details['firstName']) || $player_details['firstName'] != $args['bankAccountFullName'] ) ) {
            else if (!$rules_faa['account_name_allow_modify_by_players']) {
                $player_fullname = $this->player_model->playerFullNameById($player_id);
                $this->comapi_log(__METHOD__, 'account_name', [ 'expected' => $player_fullname, 'args' => $args['bankAccountFullName'] ]);
                if ($player_fullname != $args['bankAccountFullName']) {
            		throw new Exception(lang('bankAccName invalid, must be exactly the same as your registered real name'), self::CODE_PAO_BANKACCNAME_INVALID);
                }
            }
            // 4c: account_name_modify_allowed
            else if ($rules_faa['account_name_allow_modify_by_players']) {
                // bankAccountFullName should be non-empty here; so do nothing
            }

            // 5: Check fields by $rules_faa['field_required'] (converted into 'required_fields')
            foreach ($rules_faa['required_fields'] as $rf_key) {
                if (!isset($args[$rf_key]) || empty($args[$rf_key])) {
                    throw new Exception(sprintf(lang("Required field (%s) absent"), $rf_key), self::CODE_PAO_BANKACC_FIELD_MISSING);
                }
            }

    		// Build insertset now - other fields are all optional
    		$dwBank = '';
    		$mesg_success = '';
    		switch ($mode) {
    			case 'deposit' :
    				$dwBank = Playerbankdetails::DEPOSIT_BANK;
    				$mesg_success = lang('Deposit account added successfully');
    				break;
    			case 'withdraw' : default :
    				$dwBank = Playerbankdetails::WITHDRAWAL_BANK;
    				$mesg_success = lang('Withdrawal account added successfully');
    				break;
    		}

    		// Check duplicate
    		// $not_unique = $this->playerbankdetails->checkUniqueBankAccountNumber($args['bankAccountNumber'], $dwBank, $player_id, $args['bankTypeId']);
            // OGP-20811: employ cross-player account duplicate check by system fin acc settings
            $not_unique = !$this->playerbankdetails->validate_bank_account_number($player_id, $args['bankAccountNumber'], $dwBank, null, $args['bankTypeId']);
    		if ($not_unique) {
    			throw new Exception(lang('Bank account already exists'), self::CODE_PAO_BANKACC_ALREADY_EXISTS);
			}

    		$args_full = array_merge($args , [
    			'playerId'		=> $player_id ,
    			'isRemember'	=> Playerbankdetails::REMEMBERED,
				'dwBank'		=> $dwBank,
				'status'		=> Playerbankdetails::STATUS_ACTIVE
    		]);

    		$new_pbd_id = $this->playerbankdetails->addBankDetailsByWithdrawal($args_full);

    		if ($new_pbd_id <= 0) {
    			throw new Exception(lang('Error adding new bank account'), self::CODE_PAO_ERROR_ADDING_ACCOUNT);
    		}

            $username_clean = $this->player_model->getUsernameById($player_id);

            $this->comapi_lib->save_history_player_account_event('add', $new_pbd_id, $username_clean);

    		$add_res = array_merge([ 'playerBankDetailsId' => $new_pbd_id], $args);

			$ret = [
			    'success'   => true,
			    'code'      => self::CODE_SUCCESS,
			    'mesg'      => $mesg_success,
			    'result'    => $add_res
			];

			$this->comapi_log(__METHOD__, 'response', $ret);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => $res
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
    }

    /**
     * removes player's deposit bank account
     * See _removePlayerAccount_general() for detailed POST arguments
     * @see		comapi_core_accounts::_removePlayerAccount_general()
     * @return	JSON	General JSON return object [ success, code, message, result ]
     */
    public function removePlayerWithdrawAccount() {
    	$this->_removePlayerAccount_general('withdraw');
    }

    /**
     * removes player's withdrawal bank account
     * See _removePlayerAccount_general() for detailed POST arguments
     * @see		comapi_core_accounts::_removePlayerAccount_general()
     * @return	JSON	General JSON return object [ success, code, message, result ]
     */
    public function removePlayerDepositAccount() {
    	$this->_removePlayerAccount_general('deposit');
    }

    /**
     * General method for adding player bank account
     * Worker method for removePlayerWithdrawAccount/removePlayerDepositAccount
     * OGP-16986
     * @see		comapi_core_accounts::addPlayerWithdrawAccount()
     * @see		comapi_core_accounts::addPlayerDepositAccount()
     *
     * @param	string	$mode			account mode, 'deposit' or 'withdraw'
     *
     * @uses	string	POST: api_key		api key given by system
     * @uses	string	POST: username		Player username
     * @uses	string	POST: token			Player's login token
     * @uses	int		POST: bankDetailsId	bankDetailsId listed in API listPlayerDepositAccount (queryDepositBank)/ listPlayerWithdrawAccount return
     * @return	JSON
     */
    protected function _removePlayerAccount_general($mode) {
    	$api_key = $this->input->post('api_key');

		if (!$this->__checkKey($api_key)) { return; }

		$res = null;
		$rmv_res = null;
		try {
			$this->load->model([ 'playerbankdetails' , 'banktype' ]);

			$token			= trim($this->input->post('token'			, true));
    		$username		= trim($this->input->post('username'		, true));
    		$bankDetailsId	= (int) $this->input->post('bankDetailsId'	, true);
    		$request = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'mode' => $mode, 'bankDetailsId' => $bankDetailsId ];
            $this->comapi_log(__METHOD__, 'request', $request);

    		// 1: Check player username
    		$player_id	= $this->player_model->getPlayerIdByUsername($username);
    		if (empty($player_id)) {
    			throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
    		}

    		// 2: Check token
    		if (!$this->__isLoggedIn($player_id, $token)) {
    			throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
    		}

    		// 3: Prevent malformed access
    		if (!in_array($mode, [ 'deposit', 'withdraw' ])) {
    			$this->comapi_log(__METHOD__, 'Exception', 'mode invalid');
    			throw new Exception(lang('Account removal failed'), self::CODE_PRO_ACC_REMOVAL_FAILED);
    		}

    		// 4: Check bankDetailsId
			$player_accs = $this->comapi_lib->player_bank_accounts($player_id, $mode, 'id_only');
			$this->comapi_log(__METHOD__, 'Removing bank account', [ 'bankDetailsId' => $bankDetailsId, 'player_accs' => $player_accs ]);

			if (!in_array($bankDetailsId, $player_accs)) {
				throw new Exception(lang('bankDetailsId invalid or does not belong to the player'), self::CODE_PRO_BANKDETAILSID_INVALID);
			}

			$acc_is_default = $this->comapi_lib->is_player_default_account($player_id, $bankDetailsId, $mode);

			$this->comapi_log(__METHOD__, [ 'acc_is_default' => $acc_is_default, 'player_id' => $player_id, 'bankDetailsId' => $bankDetailsId ]);

			// If all set
			$rmv_res = $this->playerbankdetails->deletePlayerBankInfo($bankDetailsId);

			if (!$rmv_res) {
				$this->comapi_log(__METHOD__, 'Exception', 'deletePlayerBankInfo failed', [ 'rmv_res' => $rmv_res ]);
				throw new Exception(lang('Account removal failed'), self::CODE_PRO_ACC_REMOVAL_FAILED);
			}

            $username_clean = $this->player_model->getUsernameById($player_id);

            $this->comapi_lib->save_history_player_account_event('remove', $bankDetailsId, $username_clean);

			// Set new default if available
			$player_accs = array_diff($player_accs, [ $bankDetailsId ]);
			if ($acc_is_default && count($player_accs) > 0) {
				sort($player_accs);
				$new_default_acc_id = reset($player_accs);
				$acc_flags = [ 'deposit' => Playerbankdetails::DEPOSIT_BANK, 'withdraw' => Playerbankdetails::WITHDRAWAL_BANK ];

				$sd_res = $this->playerbankdetails->setPlayerDefaultBank($player_id, $acc_flags[$mode], $new_default_acc_id);
				$this->comapi_log(__METHOD__, 'setting new default bank', [ 'player_id' => $player_id, 'mode' => $mode, 'bankDetailsId' => $new_default_acc_id, 'set_result' => $sd_res ]);
			}

			// Success return
    		$ret = [
			    'success'   => true,
			    'code'      => self::CODE_SUCCESS,
			    'mesg'      => lang('Account successfully removed') ,
			    'result'    => null
			];

			$this->comapi_log(__METHOD__, 'response', $ret);
		}
		catch (Exception $ex) {
			$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
			    'success'   => false,
			    'code'      => $ex->getCode(),
			    'mesg'      => $ex->getMessage(),
			    'result'    => null
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
    }

    public function queryDepositBank(){
        $api_key    = $this->input->post('api_key'  , 1);
        $username   = $this->input->post('username' , 1);
        $token      = $this->input->post('token'    , 1);

        if (!$this->__checkKey($api_key)) { return; }

        try {
            $request = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $this->comapi_log(__METHOD__, 'request', $request);

            $this->load->model(['player_model', 'playerbankdetails']);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_INVALID_USER);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            $playerBankDetails = $this->playerbankdetails->getDepositBankDetails($player_id);
            $result = array();

            foreach($playerBankDetails as $bankinfo) {
                $BankDetail = $this->playerbankdetails->getBankDetailsById($bankinfo['playerBankDetailsId']);
                $bankinfo['bankName'] = lang($bankinfo['bankName']);
                $bankinfo['bankAccountNumber'] = $BankDetail['bankAccountNumber'];
                $result[] = $bankinfo;
            }

            if (empty($result)) {
                throw new Exception(lang('Query deposit bank details empty'), self::CODE_QUERY_DEPOSITBANK_EMPTY);
            }

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => 'Successfully got deposit bank details',
                'result'    => $result
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
            // $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    } // End function queryDepositBank()

	public function listPlayerDepositAccounts() {
		$this->queryDepositBank();
	}

    /**
     * Lists player's existing withdrawal accounts.
     *
     * @uses	string	POST: api_key	api key given by system
     * @uses	string	POST: username	Player username
     * @uses	string	POST: token		Effective token
     *
     * @return	JSON	General JSON return object, with result = [  withdrawal account details ]
     */
	public function listPlayerWithdrawAccounts() {
    	$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

    	try {
    		$this->load->model([ 'player_model', 'playerbankdetails' ]);

    		// Read arguments
    		$token			= $this->input->post('token');
    		$username		= $this->input->post('username');
    		$std_creds 		= [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
    		$this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

    		// Check player username
    		$player_id	= $this->player_model->getPlayerIdByUsername($username);
    		if (empty($player_id)) {
    			throw new Exception('Player username invalid', self::CODE_LPWA_PLAYER_USERNAME_INVALID);
    		}

    		// Check player token
    		if (!$this->__isLoggedIn($player_id, $token)) {
                // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
    			// throw new Exception('Token invalid or player not logged in', self::CODE_LPWA_PLAYER_TOKEN_INVALID);
    		}

            $wd_accounts = $this->comapi_lib->player_withdraw_accounts($player_id);

	    	$ret = [
	    		'success'	=> true ,
	    		'code'		=> 0 ,
	    		'mesg'		=> 'Successfully got withdraw accounts for player' ,
	    		'result'	=> $wd_accounts
	    	];
    	}
    	catch (Exception $ex) {
	    	$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], ['username' => $username, 'token' => $token]);
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
	} // End function listPlayerWithdrawAccounts()

    /**
     * Sets default withdraw account for player
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    int     POST:bankDetailsId  Must match any playerBankDetailsId returned by listPlayerWithdrawAccounts
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     */
    public function setPlayerWithdrawAccountDefault() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'playerbankdetails' ]);
            // Read arguments
            $token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);
            $bankDetailsId  = (int) $this->input->post('bankDetailsId', true);

            $request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'bankDetailsId' => $bankDetailsId ];
            $this->utils->debug_log(__FUNCTION__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_INVALID_USER);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            $player_wd_account_ids = $this->comapi_lib->player_withdraw_accounts($player_id, 'return id only');

            $this->comapi_log(__METHOD__, 'player_wd_account_ids', $player_wd_account_ids);

            if (!in_array($bankDetailsId, $player_wd_account_ids)) {
                throw new Exception("bankDetailsId ({$bankDetailsId}) does not belong to player", self::CODE_PAO_NOT_PLAYERS_WD_ACCOUNT);
            }

            $set_res = $this->playerbankdetails->setPlayerDefaultBank($player_id, Playerbankdetails::WITHDRAWAL_BANK, $bankDetailsId);

            if (!$set_res) {
                throw new Exception("Error while setting default withdraw account", self::CODE_PAO_ERROR_SET_DEFAULT_WD_ACCOUNT);
            }

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Successfully set default withdraw account for player'),
                'result'    => null
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
    } // End function setPlayerWithdrawAccountDefault()


    /**
     * Adds USDT account for player, with SMS confirmation
     * OGP-19855
     * @see     addPlayerUsdtAccountRecv    (recv method for sms validation code)
     *
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @uses    int     POST: bankTypeId    bankTypeId.  Must be of crypto bank.
     * @uses    numeric POST: bankAccNum    Bank account number
     *
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function addPlayerUsdtAccountSend() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {
            $this->load->model([ 'playerbankdetails' , 'banktype', 'operatorglobalsettings', 'sms_verification' ]);

            $token                  = $this->input->post('token'        , true);
            $username               = $this->input->post('username'     , true);

            $post_keys = [
                'bankTypeId'            => 'bankTypeId'     ,
                'bankAccountNumber'     => 'bankAccNum'     ,
            ];

            $args = [];
            foreach ($post_keys as $arg_key => $key) {
                $args[$arg_key] = trim($this->input->post($key, true));
            }

            $request = [ 'api_key' => $api_key, 'token' => $token, 'username' => $username, 'args' => $args ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Username check
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Token check
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $isForceVerifiedPhone = $this->utils->getConfig('comapi_enable_force_verified_phone_in_add_player_usdt_account');
            if($isForceVerifiedPhone){
                // Check if player's phone is verified
                if ($this->player_model->isVerifiedPhone($player_id) == false) {
                    throw new Exception(lang('Player phone number is not verified yet'), self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED);
                }
            }

            // 1: check player phone
            $contact_number = $this->player_model->getPlayerContactNumber($player_id);
            if (empty($contact_number)) {
                throw new Exception(lang('Player phone number not set yet'), self::CODE_PUS_PLAYER_PHONE_NUMBER_NOT_SET);
            }

            // 2: Number of player's accounts
            $rules_fao = $this->comapi_lib->fin_acc_rules_others();
            // $this->comapi_log(__METHOD__, 'fa rules-others', $rules_fao);

            // 2a: Withdraw mode, check for number of withdraw accounts
            if ($rules_fao['account_max_count_withdraw'] > 0) {
                $this->comapi_log(__METHOD__, 'account_max_count_withdraw', $rules_fao['account_max_count_withdraw']);
                // Suppress exception CODE_LPWA_NO_WITHDRAW_ACCOUNT from Comapi_lib - 8/30/2019
                $wd_accounts = $this->comapi_lib->player_withdraw_accounts($player_id, false, 'probe_only');
                // OGP-15852: Add empty() tests for php 7.2
                if (!empty($wd_accounts) && count($wd_accounts) >= $rules_fao['account_max_count_withdraw']) {
                    $res = [ 'max_num_of_withdraw_accounts' => $rules_fao['account_max_count_withdraw'] ];
                    throw new Exception(sprintf(lang("Player already had max number (%d) of withdraw accounts, cannot add any more"), $rules_fao['account_max_count_withdraw']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }
            // 2b: Deposit mode, check for number of deposit accounts
            if ($rules_fao['account_max_count_deposit'] > 0) {
                $this->comapi_log(__METHOD__, 'account_max_count_deposit', $rules_fao['account_max_count_deposit']);
                $dp_accounts = $this->playerbankdetails->getDepositBankDetails($player_id);
                // OGP-15852: Add empty() tests for php 7.2
                if (!empty($dp_accounts) && count($dp_accounts) >= $rules_fao['account_max_count_deposit']) {
                    $res = [ 'max_num_of_deposit_accounts' => $rules_fao['account_max_count_deposit'] ];
                     throw new Exception(sprintf(lang("Player already had max number (%d) of deposit accounts, cannot add any more"), $rules_fao['account_max_count_deposit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }

            // 3: bankTypeId
            $bank = $this->banktype->getBankTypeById($args['bankTypeId']);
            if (empty($bank)) {
                throw new Exception(lang('bankTypeId invalid, not listed in system supported bank list'), self::CODE_PAO_BANKTYPEID_INVALID);
            }

            // 4: Make sure bank type is crypto
            if (!$this->comapi_lib->fin_acc_is_banktype_crypto($args['bankTypeId'])) {
                throw new Exception(lang('bank type invalid, must specify crypto'), self::CODE_PUS_BANKTYPE_NOT_CRYPTO);
            }

            // 5: Check for bankAccountNumber, use rules_faa['regex_accnum'] to match
            $rules_faa = $this->comapi_lib->fin_acc_rules_acc($args['bankTypeId']);

            $this->comapi_log(__METHOD__, 'fa rules-accounts', $rules_faa);
            if (!preg_match($rules_faa['regex_accnum'], $args['bankAccountNumber'])) {
                throw new Exception(sprintf(lang('bankAccNum empty or does not comply with the format.  The format is: %s'), $rules_faa['format_accnum']), self::CODE_PAO_BANKACCNUM_INVALID);
            }

            // 6: Check for bankAccountFullName by $rules_faa options
            $player_details = $this->player_model->getAllPlayerDetailsById($player_id);

            $args['bankAccountFullName'] = $player_details['firstName'];

            // 6a: if bankAccountFullName empty
            if (empty($args['bankAccountFullName'])) {
                throw new Exception(lang('bankAccName empty'), self::CODE_PAO_BANKACCNAME_INVALID);
            }
            // 6b: !account_name_modify_allowed && (player_details.first_name not set || player_details.first_name != bankAccountFullName)
            else if (!$rules_faa['account_name_allow_modify_by_players'] &&
                ( empty($player_details['firstName']) || $player_details['firstName'] != $args['bankAccountFullName'] ) ) {
                throw new Exception(lang('bankAccName invalid, must be exactly the same as your registered real name'), self::CODE_PAO_BANKACCNAME_INVALID);
            }
            // 6c: account_name_modify_allowed
            else if ($rules_faa['account_name_allow_modify_by_players']) {
                // bankAccountFullName should be non-empty here; so do nothing
            }

            // 7: Check fields by $rules_faa['field_required'] (converted into 'required_fields')
            foreach ($rules_faa['required_fields'] as $rf_key) {
                if (!isset($args[$rf_key]) || empty($args[$rf_key])) {
                    throw new Exception(sprintf(lang("Required field (%s) absent"), $rf_key), self::CODE_PAO_BANKACC_FIELD_MISSING);
                }
            }

            $dwBank = '';
            $mesg_success = '';

            // 8: Check for account duplicate by settings
            $not_unique_deposit = ! $this->playerbankdetails->validate_bank_account_number($player_id, $args['bankAccountNumber'], 'deposit', null, $args['bankTypeId']);
            $not_unique_withdrawal = ! $this->playerbankdetails->validate_bank_account_number($player_id, $args['bankAccountNumber'], 'withdraw', null, $args['bankTypeId']);
            if ($not_unique_deposit || $not_unique_withdrawal) {
                throw new Exception(lang('Bank account already exists'), self::CODE_PAO_BANKACC_ALREADY_EXISTS);
            }

            $restrict_area = null;
            if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
                $restrict_area = Sms_verification::USAGE_SMSAPI_BANKINFO;
            }
            $this->comapi_log(__METHOD__, 'contact_number', $contact_number, 'restrict_area', $restrict_area);

            // Send SMS
            $send_res = $this->comapi_lib->comapi_send_sms($player_id, $contact_number, Sms_verification::USAGE_COMAPI_SMS_VALIDATE, null, null, false, $restrict_area);

            if ($send_res['code'] != 0) {
                // $res = [ 'mesg' => $send_res['mesg'], 'mesg_debug' => $send_res['mesg_debug'] ];
                $this->comapi_log(__METHOD__, 'Exception while sending SMS', $send_res);
                throw new Exception($send_res['mesg'], $send_res['code']);
            }

            // ** Add deposit account

            $add_res_dep = $this->_addPlayerUsdtAccount_add('deposit', $player_id, $args);
            if ($add_res_dep['code'] != 0) {
                throw new Exception($add_res_dep['mesg'], $add_res_dep['code']);
            }

            $add_res_wdr = $this->_addPlayerUsdtAccount_add('withdraw', $player_id, $args);
            if ($add_res_wdr['code'] != 0) {
                throw new Exception($add_res_wdr['mesg'], $add_res_wdr['code']);
            }

            $add_usdt_acc_ids = [
                'deposit'   => $add_res_dep['res'] ,
                'withdraw'  => $add_res_wdr['res'] ,
                'player_id' => $player_id
            ];

            $add_usdt_event_key = "COMAPI_ADD_PLAYER_USDT_ACC_{$player_id}";

            $this->utils->writeRedis($add_usdt_event_key, json_encode($add_usdt_acc_ids));

            $mesg_success = lang('Request for adding USDT account received, confirmation SMS sent to player phone');

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => $mesg_success,
                'result'    => null
            ];

            $this->comapi_log(__METHOD__, 'response', $ret);
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            if( $ex->getCode() == self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED ){
                $res['tip'] = lang('Please update phone number to add bank account');
            }

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function addPlayerUsdtAccountSend()

    /**
     * Verifies SMS code for addPlayerUsdtAccountSend, activates added USDT accounts
     * OGP-19855
     * @see     addPlayerUserAccountSend    (add USDT accouns for player, with SMS confirmation)
     *
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @uses    string  POST: verify_code   SMS verification code
     *
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function addPlayerUsdtAccountRecv() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        // $this->load->model([ 'player_model', 'sms_verification' ]);

        $res = null;

        try {
            $this->load->model([ 'player_model', 'sms_verification' ]);

            $username       = trim($this->input->post('username', 1));
            $token          = trim($this->input->post('token', 1));
            $verify_code    = trim($this->input->post('verify_code', 1));

            $request = [ 'api_key' => $api_key, 'username' => $username , 'token' => $token, 'verify_code' => $verify_code ];
            $this->comapi_log(__FUNCTION__, 'request', $request);

            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player unknown', self::CODE_MPC_PLAYER_UNKNOWN);
            }

            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            if ($this->utils->isSMSEnabled() == false && $this->debug_level_sms < $this->debug_sms_service_enabled) {
                throw new Exception(lang('SMS service is disabled'), self::CODE_SMSVAL_SMS_SERVICE_DISABLED);
            }

            $contact_number = $this->player_model->getPlayerContactNumber($player_id);

            $isForceVerifiedPhone = $this->utils->getConfig('comapi_enable_force_verified_phone_in_add_player_usdt_account');
            if($isForceVerifiedPhone){
                // Check if player's phone is verified
                if ($this->player_model->isVerifiedPhone($player_id) == false) {
                    throw new Exception(lang('Player phone number is not verified yet'), self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED);
                }
            }

            if (empty($contact_number)) {
                throw new Exception(lang('Player phone number not set yet'), self::CODE_PUS_PLAYER_PHONE_NUMBER_NOT_SET);
            }

            if (empty($verify_code)) {
                throw new Exception(lang('Verify code empty'), self::CODE_SMSVAL_VERIFY_CODE_EMPTY);
            }

            // Retrieve playerbankdetailsid of added USDT accounts from redis
            $add_usdt_event_key = "COMAPI_ADD_PLAYER_USDT_ACC_{$player_id}";

            $add_usdt_acc_ids = json_decode($this->utils->readRedis($add_usdt_event_key), 'as_array');

            $this->comapi_log(__METHOD__, 'USDT acc activation', [ 'player_id' => $player_id, 'add_usdt_acc_ids' => $add_usdt_acc_ids ]);

            if (empty($add_usdt_acc_ids) || !is_array($add_usdt_acc_ids)) {
                throw new Exception(lang("Error activating USDT account"), self::CODE_PUS_CANNOT_ACT_PLAYER_USDT_ACCOUNT);
            }

            $code_verify_res = $this->sms_verification->validateVerificationCode($player_id, Sms_verification::SESSION_ID_DEFAULT, $contact_number, $verify_code, Sms_verification::USAGE_COMAPI_SMS_VALIDATE);

            if (!$code_verify_res) {
                $this->comapi_log(__METHOD__, 'Code verify failed in',  'Sms_verification::validateVerificationCode()');
                throw new Exception(lang('Code verification failed').' (-1)', self::CODE_SMSVAL_CODE_VERIFY_FAILED);
            }

            // activate accounts
            $act_res_dep = $this->_activateUsdtAccount($player_id, $add_usdt_acc_ids['deposit']);
            $act_res_wdr = $this->_activateUsdtAccount($player_id, $add_usdt_acc_ids['withdraw']);

            $this->comapi_log(__METHOD__, 'USDT acc activation results', [ 'deposit' => $act_res_dep, 'withdraw' => $act_res_wdr ]);

            if (!$act_res_dep || !$act_res_wdr) {
                throw new Exception(lang('Cannot activate USDT accounts'), self::CODE_PUS_CANNOT_ACT_PLAYER_USDT_ACCOUNT);
            }

            // store player account add event
            $username_clean = $this->player_model->getUsernameById($player_id);
            $this->comapi_lib->save_history_player_account_event('add', $add_usdt_acc_ids['deposit'], $username_clean);
            $this->comapi_lib->save_history_player_account_event('add', $add_usdt_acc_ids['withdraw'], $username_clean);

            $ret = [
                'success'   => true,
                'code'      => 0 ,
                'mesg'      => lang('USDT accounts successfully activated for player'),
                'result'    => null
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            if( $ex->getCode() == self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED ){
                $res['tip'] = lang('Please update phone number to add bank account');
            }

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function addPlayerUsdtAccountConf()

    /**
     * Changes status of given player bank account to STATUS_ACTIVE (0)
     * @param   int     $player_id  == player.playerId
     * @param   int     $pbd_id     == playerbankdetailsId
     * @return  int     1 if successful, otherwise failure
     */
    protected function _activateUsdtAccount($player_id, $pbd_id) {
        $this->load->model([ 'playerbankdetails' ]);

        $pbd = $this->playerbankdetails->getPlayerBankDetailsById($pbd_id);

        $bankAccountNumber_enabled = $pbd->bankAccountNumber;

        if (substr($bankAccountNumber_enabled, 0, 4) == 'del_') {
            $bankAccountNumber_enabled = substr($bankAccountNumber_enabled, 4);
        }

        $update_set = [
            'status'            => Playerbankdetails::STATUS_ACTIVE ,
            'bankAccountNumber' => $bankAccountNumber_enabled
        ];

        $this->playerbankdetails->updatePlayerBankDetails($player_id, $pbd_id, $update_set);

        $chk_row = $this->playerbankdetails->getPlayerBankDetailById($player_id, $pbd_id);

        $res = !empty($chk_row) && ($chk_row->status == Playerbankdetails::STATUS_ACTIVE);

        return $res;
    } // End function _activateUsdtAccount()

    /**
     * Creates deposit and withdrawal accounts simultaneously by given USDT details
     * @param   string  $mode           any of [ 'deposit', 'withdraw' ]
     * @param   int     $player_id      == player.playerId
     * @param   array   $args           $args from addPlayerUsdtAccountSend()
     * @param   int     $status_default default status, always STATUS_DELETED
     *
     * @see     addPlayerUsdtAccountSend()
     * @return  array   [ 'code', 'mesg' ]
     */
    protected function _addPlayerUsdtAccount_add($mode, $player_id, $args, $status_default = Playerbankdetails::STATUS_DELETED) {
        $this->load->model([ 'playerbankdetails' ]);

        $acc_modes = [
            'deposit'   => Playerbankdetails::DEPOSIT_BANK ,
            'withdraw'  => Playerbankdetails::WITHDRAWAL_BANK
        ];


        try {
            $acc_mode = $acc_modes[$mode];
            // Check duplicate
            $not_unique = $this->playerbankdetails->checkBankAccountNumberUnique($args['bankAccountNumber'], $acc_mode, $player_id, $args['bankTypeId']);
            if ($not_unique) {
                throw new Exception(lang("Bank account already exists for {$mode} -"), self::CODE_PAO_BANKACC_ALREADY_EXISTS);
            }

            $args_full = array_merge($args , [
                'playerId'      => $player_id ,
                'isRemember'    => Playerbankdetails::REMEMBERED,
                'dwBank'        => $acc_mode,
                'status'        => $status_default
            ]);

            $args_full['bankAccountNumber'] = 'del_' . $args_full['bankAccountNumber'];

            $new_pbd_id = $this->playerbankdetails->addBankDetailsBare($args_full);

            if ($new_pbd_id <= 0) {
                throw new Exception(lang("Error adding new {$mode} account"), self::CODE_PAO_ERROR_ADDING_ACCOUNT);
            }

            $ret = [
                'code'      => 0 ,
                'mesg'      => null ,
                'res'       => $new_pbd_id ,
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
            ];
        }
        finally {
            // $this->returnApiResponseByArray($ret);
            return $ret;
        }
    } // End function _addPlayerUsdtAccount_add()

    public function addPlayerDepositAccountSend() {
        $this->_addPlayerAccountSend_general('deposit');
    }

    public function addPlayerWithdrawAccountSend() {
        $this->_addPlayerAccountSend_general('withdraw');
    }

    public function addPlayerDepositAccountRecv() {
        $this->_addPlayerAccountRecv_general('deposit');
    }

    public function addPlayerWithdrawAccountRecv() {
        $this->_addPlayerAccountRecv_general('withdraw');
    }

    /**
     * returns redis key common to _addPlayerAccountSend/Recv methods
     * @param   int     $player_id  == player.playerId
     * @param   string  $mode       any of [ 'deposit', 'withdraw' ]
     */
    protected function _addPlayerAccountSendRecv_redis_key($player_id, $mode) {
        return "COMAPI_ADD_PLAYER_GEN_ACC_{$player_id}_{$mode}";
    }

    /**
     * General method for adding player bank account, with SMS confirmation
     * Worker method for addPlayerWithdrawAccountSend/addPlayerDepositAccountSend, do not call directly
     * OGP-20784
     * @see     comapi_core_accounts::addPlayerWithdrawAccountSend()
     * @see     comapi_core_accounts::addPlayerDepositAccountSend()
     * @see     SBE/payment_management/viewPlayerCenterFinancialAccountSettings
     *
     * @param   string  $mode           account mode, 'deposit' or 'withdraw'
     *
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @uses    int     POST: bankTypeId    bankTypeId listed in API queryDepositWithdrawalAvailableBank return
     * @uses    numeric POST: bankAccNum    Bank account number
     * @uses    string  POST: bankAccName   Bank account name
     * @uses    string  POST: bankAddress   Branch address
     * @uses    string  POST: province      Province the branch is in
     * @uses    string  POST: city          City the branch is in
     * @uses    string  POST: branch        Branch name
     * @uses    string  POST: phone         Branch phone
     *
     * @uses    string  playerdetails.contactNumber     player phone, stored in db
     *
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    protected function _addPlayerAccountSend_general($mode) {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {
            $this->load->model([ 'playerbankdetails' , 'banktype', 'operatorglobalsettings', 'sms_verification' ]);

            $token                  = $this->input->post('token'        , true);
            $username               = $this->input->post('username'     , true);

            $post_keys = [
                'bankTypeId'            => 'bankTypeId'     ,
                'bankAccountNumber'     => 'bankAccNum'     ,
                'bankAccountFullName'   => 'bankAccName'    ,
                'bankAddress'           => 'bankAddress'    ,
                'province'              => 'province'       ,
                'city'                  => 'city'           ,
                'branch'                => 'branch'         ,
                'phone'                 => 'phone'
            ];

            $args = [];
            foreach ($post_keys as $arg_key => $key) {
                $args[$arg_key] = trim($this->input->post($key, true));
            }

            $request = [ 'api_key' => $api_key, 'token' => $token, 'username' => $username, 'mode' => $mode, 'args' => $args ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Username check
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Token check
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            // 1: check player phone
            $contact_number = $this->player_model->getPlayerContactNumber($player_id);
            if (empty($contact_number)) {
                throw new Exception(lang('Player phone number not set yet'), self::CODE_PUS_PLAYER_PHONE_NUMBER_NOT_SET);
            }

            // 2: Number of player's accounts

            $rules_fao = $this->comapi_lib->fin_acc_rules_others();

            $pa_mesg = null;
            $mesg_extra = [];
            // 2a: Withdraw mode, check for number of withdraw accounts
            if ($mode == 'withdraw') {
                $wd_accounts = $this->comapi_lib->player_withdraw_accounts($player_id, false, 'probe_only');
                $player_allowed_to_add = PlayerBankDetails::AllowAddBankDetail(PlayerBankDetails::WITHDRAWAL_BANK, $wd_accounts, $pa_mesg, null, $player_id, $mesg_extra);
                // AllowAddBankDetail return extra:
                //      limit (float), current (float),
                //      rule (fixed_limit|tiered_limits),
                //      rule_Type (1: withdraw sum|2: deposit sum |3: bet sum),
                //      rule_value (float) }
                $this->comapi_log(__METHOD__, 'AllowAddBankDetail return', [ 'mode' => $mode, 'result' => $player_allowed_to_add, 'mesg' => $pa_mesg, 'extra' => $mesg_extra ]);
                if (!$player_allowed_to_add) {
                    $res = [ 'max_num_of_withdraw_accounts' => $mesg_extra['limit'] ];
                    throw new Exception(sprintf(lang("Max number of withdraw accounts is %d, cannot add any more"), $mesg_extra['limit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }
            // 2b: Deposit mode, check for number of deposit accounts
            else if ($mode == 'deposit') {
                $dp_accounts = $this->playerbankdetails->getDepositBankDetails($player_id);
                $player_allowed_to_add = PlayerBankDetails::AllowAddBankDetail(PlayerBankDetails::DEPOSIT_BANK, $dp_accounts, $pa_mesg, null, $player_id, $mesg_extra);
                $this->comapi_log(__METHOD__, 'AllowAddBankDetail return', [ 'mode' => $mode, 'result' => $player_allowed_to_add, 'mesg' => $pa_mesg, 'extra' => $mesg_extra  ]);
                if (!$player_allowed_to_add) {
                    $res = [ 'max_num_of_withdraw_accounts' => $mesg_extra['limit'] ];
                    throw new Exception(sprintf(lang("Max number of deposit accounts is %d, cannot add any more"), $mesg_extra['limit']), self::CODE_PAO_TOO_MANY_ACCOUNTS);
                }
            }

            // 3: bankTypeId
            $bank = $this->banktype->getBankTypeById($args['bankTypeId']);
            if (empty($bank)) {
                throw new Exception(lang('bankTypeId invalid, not listed in system supported bank list'), self::CODE_PAO_BANKTYPEID_INVALID);
            }

            // 4: Check for bankAccountNumber, use rules_faa['regex_accnum'] to match
            $rules_faa = $this->comapi_lib->fin_acc_rules_acc($args['bankTypeId']);

            $this->comapi_log(__METHOD__, 'fa rules-accounts', $rules_faa);
            if (!preg_match($rules_faa['regex_accnum'], $args['bankAccountNumber'])) {
                throw new Exception(sprintf(lang('bankAccNum empty or does not comply with the format.  The format is: %s'), $rules_faa['format_accnum']), self::CODE_PAO_BANKACCNUM_INVALID);
            }

            // 5: Check for bankAccountFullName by $rules_faa options
            $player_details = $this->player_model->getAllPlayerDetailsById($player_id);
            // 5a: if bankAccountFullName empty
            if (empty($args['bankAccountFullName'])) {
                throw new Exception(lang('bankAccName empty'), self::CODE_PAO_BANKACCNAME_INVALID);
            }
            // 5b:
            else if (!$rules_faa['account_name_allow_modify_by_players']) {
                $player_fullname = $this->player_model->playerFullNameById($player_id);
                $this->comapi_log(__METHOD__, 'account_name', [ 'expected' => $player_fullname, 'args' => $args['bankAccountFullName'] ]);
                if ($player_fullname != $args['bankAccountFullName']) {
                    throw new Exception(lang('bankAccName invalid, must be exactly the same as your registered real name'), self::CODE_PAO_BANKACCNAME_INVALID);
                }
            }
            // 5c: account_name_modify_allowed
            else if ($rules_faa['account_name_allow_modify_by_players']) {
                // bankAccountFullName should be non-empty here; so do nothing
            }

            // 6: Check fields by $rules_faa['field_required'] (converted into 'required_fields')
            foreach ($rules_faa['required_fields'] as $rf_key) {
                if (!isset($args[$rf_key]) || empty($args[$rf_key])) {
                    throw new Exception(sprintf(lang("Required field (%s) absent"), $rf_key), self::CODE_PAO_BANKACC_FIELD_MISSING);
                }
            }

            // Insert parameters
            $dwBank = '';
            $mesg_success = '';
            switch ($mode) {
                case 'deposit' :
                    $dwBank = Playerbankdetails::DEPOSIT_BANK;
                    $mesg_success = lang('Deposit account added successfully');
                    break;
                case 'withdraw' : default :
                    $dwBank = Playerbankdetails::WITHDRAWAL_BANK;
                    $mesg_success = lang('Withdrawal account added successfully');
                    break;
            }

            // 7: Check for account duplicate by settings
            $not_unique = ! $this->playerbankdetails->validate_bank_account_number($player_id, $args['bankAccountNumber'], $dwBank, null, $args['bankTypeId']);
            if ($not_unique) {
                throw new Exception(lang('Bank account already exists'), self::CODE_PAO_BANKACC_ALREADY_EXISTS);
            }

            $args_full = array_merge($args , [
                'playerId'      => $player_id ,
                'isRemember'    => Playerbankdetails::REMEMBERED,
                'dwBank'        => $dwBank,
                'status'        => Playerbankdetails::STATUS_ACTIVE
            ]);

            $restrict_area = null;
            if (!empty($this->utils->getConfig('use_new_sms_api_setting'))) {
                $restrict_area = Sms_verification::USAGE_SMSAPI_BANKINFO;
            }
            $this->comapi_log(__METHOD__, 'contact_number', $contact_number, 'restrict_area', $restrict_area);

            /// A. was creating account at this point, instead we send SMS here
            $send_res = $this->comapi_lib->comapi_send_sms($player_id, $contact_number, Sms_verification::USAGE_COMAPI_SMS_VALIDATE, null, null, false, $restrict_area);

            if ($send_res['code'] != 0 && $this->debug_level_sms < $this->debug_sms_service_enabled) {
                // $res = [ 'mesg' => $send_res['mesg'], 'mesg_debug' => $send_res['mesg_debug'] ];
                $this->comapi_log(__METHOD__, 'Exception while sending SMS', $send_res);
                throw new Exception($send_res['mesg'], $send_res['code']);
            }

            /// 2. if SMS is sent successfully, store the details in redis
            // $add_acc_args_key = "COMAPI_ADD_PLAYER_GEN_ACC_{$player_id}_{$mode}";
            $add_acc_args_key = $this->_addPlayerAccountSendRecv_redis_key($player_id, $mode);

            $this->utils->writeRedis($add_acc_args_key, json_encode($args_full));

            $this->comapi_log(__METHOD__, [ 'add_acc_args_key' => $add_acc_args_key, 'add_acc_args' => $args_full ]);

            $mesg_success = lang('Request for adding player account received, confirmation SMS sent to player phone');

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => $mesg_success,
                'result'    => null
            ];

            $this->comapi_log(__METHOD__, 'response', $ret);
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function _addPlayerAccountSend_general()

    protected function _addPlayerAccountRecv_general($mode) {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {
            $this->load->model([ 'playerbankdetails', 'player_model', 'sms_verification' ]);

            $username       = trim($this->input->post('username', 1));
            $token          = trim($this->input->post('token', 1));
            $verify_code    = trim($this->input->post('verify_code', 1));

            $request = [ 'api_key' => $api_key, 'username' => $username , 'token' => $token, 'verify_code' => $verify_code ];
            $this->comapi_log(__FUNCTION__, 'request', $request);

            // 0a: check player_id
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player unknown', self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: check player logged in
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            // 1: check SMS availability
            if ($this->utils->isSMSEnabled() == false && $this->debug_level_sms < $this->debug_sms_service_enabled) {
                throw new Exception(lang('SMS service is disabled'), self::CODE_SMSVAL_SMS_SERVICE_DISABLED);
            }

            // 2: check player contact number
            $contact_number = $this->player_model->getPlayerContactNumber($player_id);

            if (empty($contact_number)) {
                throw new Exception(lang('Player phone number not set yet'), self::CODE_PUS_PLAYER_PHONE_NUMBER_NOT_SET);
            }

            // 3: verify code
            if (empty($verify_code)) {
                throw new Exception(lang('Verify code empty'), self::CODE_SMSVAL_VERIFY_CODE_EMPTY);
            }

            // 4: Fetch stored account details from redis
            $add_acc_args_key = $this->_addPlayerAccountSendRecv_redis_key($player_id, $mode);

            $add_acc_args = json_decode($this->utils->readRedis($add_acc_args_key), 'as_array');

            $this->comapi_log(__METHOD__, [ 'add_acc_args_key' => $add_acc_args_key, 'add_acc_args' => $add_acc_args ]);

            if (empty($add_acc_args) || !is_array($add_acc_args)) {
                throw new Exception(lang("Error activating {$mode} account for player (-1)"), self::CODE_PAO_ERROR_ADDING_ACCOUNT);
            }

            //move from #3
            $code_verify_res = $this->sms_verification->validateVerificationCode($player_id, Sms_verification::SESSION_ID_DEFAULT, $contact_number, $verify_code, Sms_verification::USAGE_COMAPI_SMS_VALIDATE);

            if (!$code_verify_res) {
                $this->comapi_log(__METHOD__, 'Code verify failed in',  'Sms_verification::validateVerificationCode()');
                throw new Exception(lang('Code verification failed').' (-1)', self::CODE_SMSVAL_CODE_VERIFY_FAILED);
            }

            // A: Create account
            // $new_pbd_id = $this->playerbankdetails->addBankDetailsBare($add_acc_args);
            $new_pbd_id = $this->playerbankdetails->addBankDetailsByWithdrawal($add_acc_args);

            if ($new_pbd_id <= 0) {
                throw new Exception(lang('Error adding new bank account (-2)'), self::CODE_PAO_ERROR_ADDING_ACCOUNT);
            }

            // B: After successful creation, clear stored account details
            $this->utils->writeRedis($add_acc_args_key, null);

            // C: Store account update event
            $username_clean = $this->player_model->getUsernameById($player_id);
            $this->comapi_lib->save_history_player_account_event('add', $new_pbd_id, $username_clean);

            // Successful return
            // $add_res = array_merge([ 'playerBankDetailsId' => $new_pbd_id ], $add_acc_args);
            $add_res = $add_acc_args;
            $mesg_success = lang("Successfully added account(s) for player");

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => $mesg_success,
                'result'    => $add_res
            ];

            $this->comapi_log(__METHOD__, 'response', $ret);
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }

    } // End function _addPlayerAccountRecv_general()


} // End trait comapi_core_accounts