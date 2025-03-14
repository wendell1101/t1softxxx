<?php

/**
 *
 * big wallet is new wallet structure
 *
 * it's template : BIG_WALLET_TEMPLATE
 *
 * @see Wallet_model
 *
 */
trait big_wallet_module {

	//====balance history=========================================================
	/**
	 * actionType is transaction_type and BALANCE_ACTION_XXXXXX
	 */
	public function recordWalletBalanceHistory($userType, $recordType, $actionType,
		$playerId, $affId, $transactionId, $amount, $saleOrderId = null, $playerPromoId = null,
		$subWalletId = null, $walletAccountId = null, $gamePlatformId = null, $agentId=null) {

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}


		if (empty($transactionId)) {
			$transactionId = 0;
		}

		$data = array(
			'record_type' => $recordType,
			'action_type' => $actionType,
			'user_type' => $userType,
			'transaction_id' => $transactionId,
			'created_at' => $this->utils->getNowForMysql(),
			'updated_at' => $this->utils->getNowForMysql(),
			'playerpromo_id' => $playerPromoId,
			'sale_order_id' => $saleOrderId,
			'walletaccount_id' => $walletAccountId,
			'sub_wallet_id' => $subWalletId,
			'game_platform_id' => $gamePlatformId,
			'amount' => $amount,
		);
		if ($userType == self::USER_TYPE_PLAYER) {
			if (!empty($playerId)) {
				$bigWallet = $this->getBigWalletByPlayerId($playerId);
				//get from playeraccount
				$details = $this->getBalanceDetails($playerId);
				// $data['sub_wallet'] = json_encode($details);
				$data['big_wallet'] = $this->utils->encodeJson($bigWallet);
				$data['main_wallet'] = $details['main_wallet'];
				$data['total_balance'] = $details['total_balance'];
				$data['player_id'] = $playerId;
			} else {
				$this->utils->debug_log('wrong player id, transactionId is', $transactionId);
			}
		} else if ($userType == self::USER_TYPE_AFF) {
			if (!empty($affId)) {
				$this->load->model(array('affiliatemodel'));
				$walletBalance = $this->affiliatemodel->getWalletBalance($affId);
				$creditBalance = $this->affiliatemodel->getCreditBalance($affId);
				$total_balance = $walletBalance + $creditBalance;
				$data['main_wallet'] = $walletBalance;
				$data['sub_wallet'] = json_encode(array(
					'main_wallet' => $walletBalance,
					'sub_wallet' => array(
						"currency" => "CNY",
						'name' => 'credit',
						"totalBalanceAmount" => $creditBalance,
					),
					'total_balance' => $total_balance,
				));
				$data['total_balance'] = $total_balance;
				$data['aff_id'] = $affId;
			} else {
				$this->utils->debug_log('wrong aff id, transaction_id', $transactionId);
			}
		} else if ($userType == self::USER_TYPE_AGENT) {
			if (!empty($agentId)) {
				$this->load->model(array('agency_model'));
				$details = $this->agency_model->getBalanceDetails($agentId);

				$data['main_wallet'] = $details['main_wallet'];
				$data['sub_wallet'] = $this->utils->encodeJson($details);
				$data['total_balance'] = $details['total_balance'];
				$data['agent_id'] = $agentId;
			} else {
				$this->utils->debug_log('wrong agent id, transaction_id', $transactionId);
			}
		}

		$curr_database = $_database;
		$curr_balanceHistoryId = null; // ignore
		$curr_ActionType = $actionType;
		$detectActionType = 1001;
		$entraceLineNo = __LINE__;
		$entraceCallTrace = $this->utils->generateCallTrace();
		$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
												, $curr_balanceHistoryId
												, $curr_ActionType
												, $detectActionType
												, $entraceLineNo
												, $entraceCallTrace );
		$detectActionType = 6;
		$entraceLineNo = __LINE__;
		$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
												, $curr_balanceHistoryId
												, $curr_ActionType
												, $detectActionType
												, $entraceLineNo
												, $entraceCallTrace );



		return $this->insertData($_database. 'balance_history', $data);
	}

	# Query all balance history records between given dates, and delete the non-unique ones
	# Assumption:
	# * IDs are sequential when ordered by date;
	# * if big_wallet is updated it will be a completely different string due to the existence of updated_at in it;
	# * Do not operate on recent records as new records inserted may get deleted unexpectedly
	public function removeDuplicateBalanceHistory($startDate, $endDate) {

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}

		# Query the first record in the table with an updated big_wallet value
		$this->db->select('min(id) as unique_id')
			->from($_database. 'balance_history')
			->where("created_at BETWEEN '$startDate' AND '$endDate'")
			->group_by(array('player_id', 'big_wallet'));
		$records = $this->runMultipleRowArray();

		if(empty($records)) {
			$this->utils->debug_log("No duplicated records found from balance_history between [$startDate] and [$endDate]");
			return;
		}

		$uniqueIds = array();
		foreach($records as $record) {
			$uniqueIds[] = $record['unique_id'];
		}


		foreach($records as $record) {
			$curr_database = $_database;
			$curr_balanceHistoryId = $record['unique_id']; // ignore
			$curr_ActionType = null; // ignore
			$detectActionType = 1001;
			$entraceLineNo = __LINE__;
			$entraceCallTrace = $this->utils->generateCallTrace();
			$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
													, $curr_balanceHistoryId
													, $curr_ActionType
													, $detectActionType
													, $entraceLineNo
													, $entraceCallTrace );
			$detectActionType = 6;
			$entraceLineNo = __LINE__;
			$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
													, $curr_balanceHistoryId
													, $curr_ActionType
													, $detectActionType
													, $entraceLineNo
													, $entraceCallTrace );

		}

		# Delete the records within range and NOT selected in above query
		$this->db->where("created_at BETWEEN '$startDate' AND '$endDate'")
			->where_not_in("id", $uniqueIds);
		$this->db->delete($_database. 'balance_history');

		$this->utils->debug_log("Found [". count($uniqueIds) ."] unique records, deleted [".$this->db->affected_rows()."] duplicated records from balance_history between [$startDate] and [$endDate]");
	}

	public function getLastDayTotalBalance($playerId, $today = null) {
		if (empty($today)) {
			$today = $this->utils->getTodayForMysql();
		}
		$lastDay = $this->utils->getLastDay($today);
		return $this->getTotalBalanceByDate($playerId, $lastDay);
	}

	public function getTotalBalanceByDate($playerId, $today = null) {

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}

		if (empty($today)) {
			$today = $this->utils->getTodayForMysql();
		}
		$this->db->from($_database. 'balance_history')->where('player_id', $playerId)
			->where('created_at <=', $today . ' 23:59:59')->order_by('created_at', 'desc');

		$this->limitOneRow();


		$curr_database = $_database;
		$curr_balanceHistoryId = null; // ignore
		$curr_ActionType = null; // ignore
		$detectActionType = 'any';
		$entraceLineNo = __LINE__;
		$entraceCallTrace = $this->utils->generateCallTrace();
		$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
												, $curr_balanceHistoryId
												, $curr_ActionType
												, $detectActionType
												, $entraceLineNo
												, $entraceCallTrace );

		return $this->runOneRowOneField('total_balance');
	}

    public function getLastTotalBalanceByDate($playerId, $today = null) {

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}

        if (empty($today)) {
            $today = $this->utils->getTodayForMysql();
        }
        $this->db->from($_database. 'balance_history')->where('player_id', $playerId)
            ->where('created_at <=', $today . ' 23:59:59')->order_by('created_at', 'desc');

        $this->limitOneRow();

		$oneRow = $this->runOneRowArray();

		$curr_database = $_database;
		$curr_balanceHistoryId = $oneRow['id'];
		$curr_ActionType = null;// ignore
		$detectActionType = 1001;
		$entraceLineNo = __LINE__;
		$entraceCallTrace = $this->utils->generateCallTrace();
		$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
												, $curr_balanceHistoryId
												, $curr_ActionType
												, $detectActionType
												, $entraceLineNo
												, $entraceCallTrace );
		$detectActionType = 6;
		$entraceLineNo = __LINE__;
		$this->utils->scriptOGP26371_catch_action_type_source( $curr_database
												, $curr_balanceHistoryId
												, $curr_ActionType
												, $detectActionType
												, $entraceLineNo
												, $entraceCallTrace );



        return $oneRow;
    }
	//====balance history=========================================================

	/**
	 *
	 *
	 *
	 */
	public function getBigWalletByPlayerId($playerId) {
		$rules = $this->getBigWalletRules();
		$bigWallet = null;

		if (empty($bigWallet)) {
			//get from db
			$bigWallet = $this->readBigWalletFromDB($playerId);
			$bigWallet = $this->copyBigWalletToEmpty($bigWallet, $playerId);

		} else {
			$bigWallet = $this->copyBigWalletToEmpty($bigWallet, $playerId);
		}

		if ($rules['force_real']) {
			//set real and move to real
			$this->moveAllToRealByMainWallet($bigWallet);
        }

		return $bigWallet;
	}

	public function getBigWalletInclBlockedGameByPlayerId($playerId,$blockedGame=null){
		$rules = $this->getBigWalletRules();
		$bigWallet = null;
		$games = $this->utils->getAllCurrentGameSystemList();

		//add old blocked game
		if(!empty($blockedGame)){
			array_push($games,$blockedGame);
		}

		$uniqueGames = array_unique($games);

		if (empty($bigWallet)) {
			//get from db
			$bigWallet = $this->readBigWalletFromDB($playerId,$uniqueGames);

		} else {
			$bigWallet = $this->copyBigWalletToEmpty($bigWallet, $playerId,$uniqueGames);
		}

		if ($rules['force_real']) {
			//set real and move to real
			$this->moveAllToRealByMainWallet($bigWallet);
		}

		return $bigWallet;
	}


	public function readBigWalletFromDB($playerId,$games=null) {
		$bigWallet = $this->runOneRowJsonContentById('big_wallet', $playerId, 'playerId', 'player');
		return $this->copyBigWalletToEmpty($bigWallet, $playerId,$games);
	}

	public function writeBigWalletToDB($playerId, $bigWallet, $db=null, $checkLock=true) {
		if($checkLock){
			if(!$this->isResourceInsideLock($playerId, Utils::LOCK_ACTION_BALANCE)){
				return false;
			}
		}
		if (empty($bigWallet['last_update'])) {
			$bigWallet['last_update'] = $this->utils->getNowForMysql();
		}
		return $this->runUpdateJsonContentById($bigWallet, 'big_wallet', $playerId, 'playerId', 'player', $db);
	}

	public function decSubOnBigWallet($playerId, $subWalletId, $type, $amount, &$afterBalance=null) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		//update sub wallet
		if (!isset($bigWallet['sub'])) {
			$bigWallet['sub'][$subWalletId] = self::WALLET_STRUCTURE;
		}

		$bigWallet['sub'][$subWalletId][$type] -= $amount;
		$afterBalance=$bigWallet['sub'][$subWalletId][$type];

		//save back
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	/**
	 * Decrement Blocked Sub-wallet on Big wallet
	 * @param int $playerid
	 * @param int $subWalletId
	 * @param string $type
	 * @param double $amount
	 *
	 * @return boolean
	*/
	public function decBlockedSubOnBigWallet($playerId, $subWalletId, $type, $amount) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->wallet_model->getBigWalletInclBlockedGameByPlayerId($playerId, $subWalletId);

		//update sub wallet
		if (!isset($bigWallet['sub'])) {
			$bigWallet['sub'][$subWalletId] = self::WALLET_STRUCTURE;
		}

		$bigWallet['sub'][$subWalletId][$type] -= $amount;

		//save back
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function incSubOnBigWallet($playerId, $subWalletId, $type, $amount, &$afterBalance=null) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		//update sub wallet
		if (!isset($bigWallet['sub'])) {
			$bigWallet['sub'][$subWalletId] = self::WALLET_STRUCTURE;
		}

		$bigWallet['sub'][$subWalletId][$type] += $amount;
		$afterBalance=$bigWallet['sub'][$subWalletId][$type];
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function updateSubOnBigWalletByPlayerId($playerId, $subWalletId, $type, $amount) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		//update sub wallet
		if (!isset($bigWallet['sub'])) {
			$bigWallet['sub'][$subWalletId] = self::WALLET_STRUCTURE;
		}

		$bigWallet['sub'][$subWalletId][$type] = $amount;
		// $this->totalBigWallet($bigWallet, $playerId);

		//save back
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);

		// return true;
	}

	public function moveFrozenToRealOnBigWallet($playerId, $decAmount) {
		//check sub types
		if (empty($playerId)) {
			$this->utils->error_log('empty player id:' . $playerId);
			return false;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$bigWallet['main']['frozen'] = $this->utils->roundCurrencyForShow($bigWallet['main']['frozen'] - $decAmount);
		$bigWallet['main']['real'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real'] + $decAmount);
		$this->utils->debug_log('decFrozenOnBigWallet', $playerId, 'frozen', $bigWallet['main'], $decAmount);

		$success = $bigWallet['main']['frozen'] >= 0;

		if ($success) {
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}

		return $success;
	}

	public function decFrozenOnBigWallet($playerId, $decAmount) {
		//check sub types
		if (empty($playerId)) {
			$this->utils->error_log('empty player id:' . $playerId);
			return false;
		}

		$rules = $this->getBigWalletRules();
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$bigWallet['main']['frozen'] = $this->utils->roundCurrencyForShow($bigWallet['main']['frozen'] - $decAmount);
		$this->utils->debug_log('decFrozenOnBigWallet', $playerId, 'frozen', $bigWallet['main'], $decAmount);

		$success = $bigWallet['main']['frozen'] >= 0;

		if ($success) {
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}

		return $success;
	}

	public function incFrozenTypeByBigWallet(&$bigWallet, $amount) {

		$rules = $this->getBigWalletRules();
		$types = $rules['inc_frozen_order'];
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			$types = ['real'];
			// //set real
			// return true;
			// return $this->incMainOnBigWallet($playerId,self::BIG_WALLET_SUB_TYPE_REAL , $incAmount);
		}

		//dec by order
		// $rules=$this->getBigWalletRules();
		foreach ($types as $type) {

			$incAmount = $amount;

			//check enough money
			if ($this->utils->compareResultFloat($bigWallet['main'][$type], '<', $amount)) {
				$incAmount = $this->utils->roundCurrencyForShow($amount - $bigWallet['main'][$type]);
			}

			$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $incAmount);
			// $bigWallet['main']['frozen_detail'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main']['frozen_detail'][$type] + $incAmount);
			$bigWallet['main']['frozen'] = $this->utils->roundCurrencyForShow($bigWallet['main']['frozen'] + $incAmount);

			$amount = $this->utils->roundCurrencyForShow($amount - $incAmount);

			if ($amount <= 0) {
				break;
			}
		}

		return $amount == 0;
	}

	public function incFrozenOnBigWallet($playerId, $incAmount) {
		if (empty($playerId) || empty($incAmount)) {
			$this->utils->error_log('empty player id:' . $playerId);
			return false;
		}

		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$success = $this->incFrozenTypeByBigWallet($bigWallet, $incAmount);
		if ($success) {
			$this->utils->debug_log('incFrozenOnBigWallet', $playerId, 'frozen', $bigWallet['main']['frozen'], $incAmount);
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}

		return $success;
	}

	public function returnFrozenToMainOnBigWallet($playerId) {
		$this->utils->debug_log("Return frozen amount to main wallet for ", $playerId);

		//check sub types
		if (empty($playerId)) {
			$this->utils->error_log('Return frozen, empty player id:' . $playerId);
			return false;
		}

		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$frozenAmount = $bigWallet['main']['frozen'];

		$bigWallet['main']['frozen'] = 0;
		$bigWallet['main']['real'] += $frozenAmount;

		// $this->totalBigWallet($bigWallet, $playerId);
		$this->utils->debug_log("Returned frozen amount [$frozenAmount] to main wallet for ", $playerId);
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function decMainOnBigWallet($playerId, $type, $decAmount, &$afterBalance=null) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			$type = 'real';
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		//check if balance can be below 0
		$onlyAllowPositiveAmountInPlayerWallet = $this->utils->getConfig('onlyAllowPositiveAmountInPlayerWallet');

		if($onlyAllowPositiveAmountInPlayerWallet) {
			//validate enough balance
			if($this->utils->compareResultCurrency($bigWallet['main'][$type], '>=', $decAmount)){

				$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $decAmount);
				$afterBalance=$bigWallet['main'][$type];
				return $this->updateBigWalletByPlayerId($playerId, $bigWallet);

			}else{
				$this->utils->error_log('no enough balance, type:'.$type, $playerId, $bigWallet['main'][$type], $decAmount, $bigWallet);
				return false;
			}
		} else {
			$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $decAmount);
			$afterBalance=$bigWallet['main'][$type];
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}
	}

	public function decMainOnBigWalletAllowNegativeBalance($playerId, $type, $decAmount, &$afterBalance=null) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			$type = 'real';
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $decAmount);
		$afterBalance=$bigWallet['main'][$type];
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function incMainByBigWallet(&$bigWallet, $type, $incAmount) {
		$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] + $incAmount);
		return true;
	}

	public function incMainOnBigWallet($playerId, $type, $incAmount, &$afterBalance=null) {
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			$type = 'real';
		}

		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$this->incMainByBigWallet($bigWallet, $type, $incAmount);
		$afterBalance=$bigWallet['main'][$type];

		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function incMainOnBigWalletWithBlockedSub($playerId, $type, $incAmount,$subWalletId) {
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			$type = 'real';
		}

		$bigWallet = $this->wallet_model->getBigWalletInclBlockedGameByPlayerId($playerId, $subWalletId);
		$this->incMainByBigWallet($bigWallet, $type, $incAmount);

		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function decMainDepositOnBigWallet($playerId, $decAmount) {
		return $this->decMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $decAmount);
	}

	public function incMainDepositOnBigWallet($playerId, $incAmount) {

		return $this->incMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $incAmount);
	}

	public function incMainDepositOnBigWalletWithBlockedSub($playerId, $incAmount,$subWalletId) {

		return $this->incMainOnBigWalletWithBlockedSub($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $incAmount,$subWalletId);
	}

	public function incMainBonusOnBigWallet($playerId, $incAmount, $depositAmount = null) {

		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			// $type='real';
			// //set real
			// return true;
			return $this->incMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $incAmount);
		}

		$success = true;
		//move real to real bonus
		if ($depositAmount) {
			$success = $this->moveMainTypeByBigWallet($bigWallet, self::BIG_WALLET_SUB_TYPE_REAL, self::BIG_WALLET_SUB_TYPE_REAL_BONUS, $depositAmount);
			if (!$success) {
				$this->utils->error_log('move deposit amount failed', $playerId, $depositAmount);
			}
		}
		//always add to main bonus
		$success = $success && $this->incMainByBigWallet($bigWallet, self::BIG_WALLET_SUB_TYPE_BONUS, $incAmount);

		if ($success) {
			// $this->totalBigWallet($bigWallet, $playerId);

			//save back
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}
		return $success;
	}

	public function incMainCashbackOnBigWallet($playerId, $incAmount) {
		//incCashbackOnBigWallet($playerId, $amount)
		$type = $this->getBigWalletRules()['cashback_wallet_type'];
		// if($this->utils->isEnabledPromotionRule('only_real_when_release_bonus')){
		// 	$type='real';
		// }else{
		// 	$type='bonus';
		// }
		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			$type = 'real';
			// //set real
			// return true;
			// return $this->incMainOnBigWallet($playerId,self::BIG_WALLET_SUB_TYPE_REAL , $incAmount);
		}

		return $this->incMainOnBigWallet($playerId, $type, $incAmount);
	}

	public function incMainManuallyOnBigWallet($playerId, $incAmount) {
		//inc to real
		return $this->incMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $incAmount);
	}

	public function decMainManuallyByBigWallet(&$bigWallet, $amount) {
		$rules = $this->getBigWalletRules();
		//dec by order
		$types = $rules['big_wallet_manually_order'];

		if ($rules['force_real']) {
			$types = ['real'];
		}

		foreach ($types as $type) {
			if ($this->utils->compareResultFloat($amount, ">", 0)) {

				if ($amount > $bigWallet['main'][$type]) {
					//bigger than current type
					$amount = $this->utils->roundCurrencyForShow($amount - $bigWallet['main'][$type]);
					$bigWallet['main'][$type] = 0;
				} else {
					$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $amount);
					$amount = 0;
				}

				$this->utils->debug_log('dec main' . $type, $bigWallet['main'][$type], 'left', $amount);
			} else {
				$this->utils->debug_log('done transfer', $amount);
				break;
			}
		}

		return $this->utils->compareResultFloat($amount, "=", 0);
	}

	public function decMainManuallyOnBigWallet($playerId, $amount) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$rules = $this->getBigWalletRules();
		$changed = $this->decMainManuallyByBigWallet($bigWallet, $amount);

		if ($changed) {
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		} else {
			$bigWallet = $this->getBigWalletByPlayerId($playerId);
			$this->utils->error_log('donot have enough balance on main wallet', $bigWallet);
			return false;
		}
	}

	public function updateMainOnBigWalletByPlayerId($playerId, $type, $amount) {
		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			$type = 'real';
			// //set real
			// return true;
			// return $this->incMainOnBigWallet($playerId,self::BIG_WALLET_SUB_TYPE_REAL , $incAmount);
		}

		//update cache first
		//locked by outside

		$bigWallet['main'][$type] = $amount;
		// $this->totalBigWallet($bigWallet, $playerId);

		//save back
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);

		// return true;
	}

	public function moveMainTypeByBigWallet(&$bigWallet, $fromType, $toType, $amount) {
		if ($this->utils->compareResultFloat($bigWallet['main'][$fromType], ">=", $amount)) {
			//move all
			$bigWallet['main'][$fromType] -= $amount;
			$bigWallet['main'][$toType] += $amount;
		} else {
			$moveAmount = $this->utils->roundCurrencyForShow($amount - $bigWallet['main'][$fromType]);
			//move part
			$bigWallet['main'][$fromType] -= $moveAmount;
			$bigWallet['main'][$toType] += $moveAmount;
		}

		return true;
	}

	public function updateBigWalletByPlayerId($playerId, $bigWallet) {
		if (empty($playerId) || empty($bigWallet)) {
			$this->utils->error_log('wrong player id or bigWallet:' . $playerId);
			return false;
		}
		//always total
		$this->totalBigWallet($bigWallet, $playerId);

		$bigWallet['last_update'] = $this->utils->getNowForMysql();
		if($this->utils->getConfig('verbose_log')){
			$this->utils->debug_log($playerId . ' update big wallet', json_encode($bigWallet));
		}else{
			$this->utils->debug_log($playerId . ' update big wallet');
		}

		$success = true;
		//save to DB
		$success = $success && $this->writeBigWalletToDB($playerId, $bigWallet);
        $this->utils->debug_log(__METHOD__, "debug_transfer_player_id_{$playerId}", 'updateBigWalletByPlayerId', 'writeBigWalletToDB', 'success', $success, 'bigWallet', $bigWallet);

		//convert big wallet to old
		$success = $success && $this->convertBigWalletToOldWallet($playerId, $bigWallet);

		return $success;
	}

	public function getEmptyBigWallet($games=null) {
		$bigWallet = self::BIG_WALLET_TEMPLATE;
		//fill up sub wallets
		$games = empty($games) ? $this->utils->getAllCurrentGameSystemList() : $games;
		foreach ($games as $gameId) {
			$bigWallet['sub'][$gameId] = self::WALLET_STRUCTURE;
			$bigWallet['sub'][$gameId]['id'] = $gameId;
		}

		return $bigWallet;
	}

	/**
	 * This will Copy New Big Wallet Data into the Big Wallet Empty Structure
	 *
	 * @param array $bigWallet the new big wallet details, must be an array
	 * @param int $playerId the player ID
	 * @param array $games the game platform ID
	 *
	 * @return array $newWallet the new Wallet data
	 */
	public function copyBigWalletToEmpty($bigWallet, $playerId=null, $games=null) {

		if (!is_array($bigWallet)) {
			$bigWallet = (array) $bigWallet;
		}

		$emptyWallet = $this->getEmptyBigWallet($games);
		$newWallet = $emptyWallet;

		if (!empty($bigWallet)) {

			//for main
			if (isset($bigWallet['main'])) {
				foreach ($bigWallet['main'] as $key => $value) {
					$newWallet['main'][$key] = $value;
				}
			}
			//for sub
			if (isset($bigWallet['sub'])) {
				foreach ($bigWallet['sub'] as $subWalletId => $subWallet) {
					if (!empty($subWallet)) {
						if (!empty($newWallet['sub'][$subWalletId])) {
							foreach ($subWallet as $key => $value) {
								$newWallet['sub'][$subWalletId][$key] = $value;
							}
						} else {
							//disabled or miss game api
						}
					}
				}
			}

			//for frozen
			// if(isset($bigWallet['frozen'])){
			// 	$emptyWallet['frozen']=$bigWallet['frozen'];
			// }
			// $totals=['total', 'total_real', 'total_bonus', 'total_win_real', 'total_win_bonus', 'total_withdrawable', 'total_frozen'];
			// //for total
			// foreach ($totals as $key) {
			// 	if(isset($bigWallet[$key])){
			// 		$emptyWallet[$key]=$bigWallet[$key];
			// 	}
			// }

			if (isset($bigWallet['last_update'])) {
				$newWallet['last_update'] = $bigWallet['last_update'];
			}
			//recalc
			$this->totalBigWallet($newWallet, $playerId);
		}
		return $newWallet;
	}

	public function subtotalBigWallet($anyWallet) {
		return $this->utils->roundCurrencyForShow(doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_REAL])
			 + doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_REAL_BONUS])
			 + doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_BONUS])
			 + doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_WIN_REAL])
			 + doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_WIN_BONUS])
			 + doubleval(@$anyWallet[self::BIG_WALLET_SUB_TYPE_FROZEN]));
	}

	public function validateAnyWallet($anyWallet) {

		$success=!empty($anyWallet)
			&& isset($anyWallet['real'])
			&& isset($anyWallet['real_for_bonus'])
			&& isset($anyWallet['bonus'])
			&& isset($anyWallet['win_real'])
			&& isset($anyWallet['win_bonus'])
			&& isset($anyWallet['frozen'])
			&& isset($anyWallet['withdrawable'])
			&& isset($anyWallet['total_nofrozen'])
			&& isset($anyWallet['total'])
			;
		return $success;
	}

	public function validateBigWallet($bigWallet){
		//FIXME validate big wallet
		$success=!empty($bigWallet);

		//validate main
		$success=$success && !empty($bigWallet['main']) && $this->validateAnyWallet($bigWallet['main']);

		$success= $success && !empty($bigWallet['sub']);

		if($success){
			foreach ($bigWallet['sub'] as $subWalletId => $subWallet) {
				$success= $success && !empty($subWallet) && $this->validateAnyWallet($subWallet);
			}
		}

		return $success;
	}

	public function totalBigWallet(&$bigWallet, $playerId=null) {
		if(!$this->validateBigWallet($bigWallet)){
			$this->utils->error_log('validate big wallet failed', $playerId, $bigWallet);
			return $bigWallet;
		}
		//withdrawable = real + win real
		$bigWallet['main']['withdrawable'] = $bigWallet['main']['real'] + $bigWallet['main']['win_real'];

		//calc sub total
		$subtotal = $this->subtotalBigWallet($bigWallet['main']);
		$bigWallet['main']['total'] = $subtotal;
		$bigWallet['main']['total_nofrozen'] = $this->utils->roundCurrencyForShow($bigWallet['main']['total'] - $bigWallet['main']['frozen']);
		$total = doubleval($subtotal);

		$total_withdrawable = doubleval($bigWallet['main']['withdrawable']);
		$total_frozen = doubleval($bigWallet['main']['frozen']);
		$total_real = doubleval($bigWallet['main']['real']);
		$total_real_for_bonus = doubleval($bigWallet['main']['real_for_bonus']);
		$total_bonus = doubleval($bigWallet['main']['bonus']);
		$total_win_real = doubleval($bigWallet['main']['win_real']);
		$total_win_bonus = doubleval($bigWallet['main']['win_bonus']);

		// $this->utils->debug_log('check sub of bigWallet', $bigWallet['sub']);

		foreach ($bigWallet['sub'] as $subWalletId => $subWallet) {
			//calc sub total
			$subtotal = $this->subtotalBigWallet($subWallet);
			$bigWallet['sub'][$subWalletId]['total'] = $subtotal;
			$bigWallet['sub'][$subWalletId]['total_nofrozen'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId]['total'] - $bigWallet['sub'][$subWalletId]['frozen']);
			$bigWallet['sub'][$subWalletId]['withdrawable'] = $bigWallet['sub'][$subWalletId]['win_real'];
			//real + win real
			$bigWallet['sub'][$subWalletId]['withdrawable'] = $bigWallet['sub'][$subWalletId]['real'] + $bigWallet['sub'][$subWalletId]['win_real'];

			$total += doubleval($subtotal);
			$total_withdrawable += doubleval($bigWallet['sub'][$subWalletId]['withdrawable']);
			$total_frozen += doubleval($bigWallet['sub'][$subWalletId]['frozen']);
			$total_real += doubleval($bigWallet['sub'][$subWalletId]['real']);
			$total_real_for_bonus += doubleval($bigWallet['sub'][$subWalletId]['real_for_bonus']);
			$total_bonus += doubleval($bigWallet['sub'][$subWalletId]['bonus']);
			$total_win_real += doubleval($bigWallet['sub'][$subWalletId]['win_real']);
			$total_win_bonus += doubleval($bigWallet['sub'][$subWalletId]['win_bonus']);
		}
		$bigWallet['total'] = $this->utils->roundCurrencyForShow($total);
		$bigWallet['total_withdrawable'] = $this->utils->roundCurrencyForShow($total_withdrawable);
		$bigWallet['total_frozen'] = $this->utils->roundCurrencyForShow($total_frozen);
		$bigWallet['total_real'] = $this->utils->roundCurrencyForShow($total_real);
		$bigWallet['total_real_for_bonus'] = $this->utils->roundCurrencyForShow($total_real_for_bonus);
		$bigWallet['total_bonus'] = $this->utils->roundCurrencyForShow($total_bonus);
		$bigWallet['total_win_real'] = $this->utils->roundCurrencyForShow($total_win_real);
		$bigWallet['total_win_bonus'] = $this->utils->roundCurrencyForShow($total_win_bonus);

		$bigWallet['total_nofrozen'] = $this->utils->roundCurrencyForShow($bigWallet['total'] - $bigWallet['total_frozen']);

		return $bigWallet;
	}

	public function convertBigWalletToOldWallet($playerId, $bigWallet = null) {
		if (empty($bigWallet)) {
			$bigWallet = $this->readBigWalletFromDB($playerId);
		}
		$this->totalBigWallet($bigWallet, $playerId);

		$this->db->from('playeraccount')->where('playerId', $playerId);
		$rows = $this->runMultipleRowArray();
		$data = [];
		$is_main=false;
		$sub_update=[];

		$detectSmallestNegativeBalanceAndNotifyIntoMM = $this->utils->getConfig('detectSmallestNegativeBalanceAndNotifyIntoMM');
		if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){
			$detect_item_list = [];
			$tipString = <<<EOF
Caught the player's sub-wallet becoming %s. @ping.php.tw
EOF;
			$_warningAmount = $detectSmallestNegativeBalanceAndNotifyIntoMM['warningAmount'];
			$isDryRun = false;
		}// EOF if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){...

		if(!empty($rows)){
			foreach ($rows as $row) {
				$udpaterow = null;
				if ($row['type'] == self::TYPE_MAINWALLET) {
					$is_main=true;
					$udpaterow = [
						'playerAccountId' => $row['playerAccountId'],
						'totalBalanceAmount' => $bigWallet['main']['total_nofrozen'],
					];
				} elseif ($row['type'] == self::TYPE_SUBWALLET && isset($bigWallet['sub'][$row['typeId']])) {
					if(!isset($bigWallet['sub'][$row['typeId']]['total_nofrozen'])){
						$this->utils->error_log('lost total number on '.$playerId, $row['typeId'], $bigWallet['sub'][$row['typeId']]);
					}

					$udpaterow = [
						'playerAccountId' => $row['playerAccountId'],
						'totalBalanceAmount' => isset($bigWallet['sub'][$row['typeId']]['total_nofrozen']) ? $bigWallet['sub'][$row['typeId']]['total_nofrozen'] : 0,
					];
					$sub_update[]=$row['typeId'];

					if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){
						if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM['forcedNotify']) ){
							$forcedNotify = $detectSmallestNegativeBalanceAndNotifyIntoMM['forcedNotify'];
							if(	$row['playerAccountId'] == $forcedNotify['playerAccountId']
								&& $playerId == $forcedNotify['playerId']
							){
								$isDryRun = true;
								// $bigWallet['sub'][$row['typeId']]['total_nofrozen'] = -0.01;
							}
						}

						if( empty($detect_item_list[$row['typeId']]) ){
							$detect_item_list[$row['typeId']] = [];
						}
						$detect_item_list[$row['typeId']]['playerAccountId'] = $row['playerAccountId'];
						$detect_item_list[$row['typeId']]['playerId'] = $row['playerId'];
						$detect_item_list[$row['typeId']]['beforeAmount'] = $row['totalBalanceAmount'];
						$detect_item_list[$row['typeId']]['afterAmount'] = $udpaterow['totalBalanceAmount'];
						$detect_item_list[$row['typeId']]['isDryRun'] = $isDryRun;
					} // EOF if( ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){...
				}

				if (!empty($udpaterow)) {
					$data[] = $udpaterow;
				}
			}
		}

		if(!empty($data)){
			$this->db->update_batch('playeraccount', $data, 'playerAccountId');
		}
		$this->updateWalletInfoInPlayer($playerId, $bigWallet);

		if( ! empty($detect_item_list) && ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){
			$willNotify = false;
			// $isDryRun = false;
			$firstHitRow = null;
			/// count the sub-wallet, that has the Warning amount.
			$beforeWarningCount = 0;
			$afterWarningCount = 0;
			foreach($detect_item_list as $game_platform_id => $row){
				// to detect the issue, the sub-wallet has $_warningAmount.
				if($row['beforeAmount'] == $_warningAmount){
					$beforeWarningCount++;
				}
				if($row['afterAmount'] == $_warningAmount){
					$afterWarningCount++;
				}
				if( empty($firstHitRow) ){
					if(	$row['beforeAmount'] != $_warningAmount
						&& $row['afterAmount'] == $_warningAmount
					){
						$firstHitRow = $row;
					}else if($row['isDryRun']){
						$firstHitRow = $row;
					}
				}
			} // EOF foreach($detect_item_list as $game_platform_id => $row){...
			/// catch inconsistent count
			if( $beforeWarningCount < $afterWarningCount
				&& ! empty($beforeWarningCount)
				&& ! empty($afterWarningCount)
			){
				$willNotify = true;
			}
			if($firstHitRow['isDryRun']){
				$willNotify = true;
			}

			$this->utils->debug_log('982.OGP23595.beforeWarningCount:', $beforeWarningCount
				, 'afterWarningCount:', $afterWarningCount
				, 'firstHitRow:', $firstHitRow
				, 'willNotify:', $willNotify
				// , 'forcedNotify', empty($detectSmallestNegativeBalanceAndNotifyIntoMM['forcedNotify'])? '': $detectSmallestNegativeBalanceAndNotifyIntoMM['forcedNotify']
				// , '$detect_item_list:', $detect_item_list
			);
			if($willNotify && ! empty($firstHitRow) ){
				$isDryRun = $firstHitRow['isDryRun'];
				$theCallTrace = $this->utils->generateCallTrace();
				$beforeAmount = 0; // assign the value for send notice
				$afterAmount = $_warningAmount; // assign the value for send notice
				$theParams =[];
				$theParams['level'] = 'info';
				$theParams['pretext'] = '';
				$theParams['pretext'] .= sprintf($tipString, $_warningAmount). PHP_EOL;
				$theParams['pretext'] .= '```JSON'. PHP_EOL;
				$theParams['pretext'] .= substr(json_encode($detect_item_list), 0, 1000) . PHP_EOL;
				$theParams['pretext'] .= '```'. PHP_EOL;
				// $detect_item_list
				$theParams['title'] = sprintf('player_id=%s, playerAccountId=%s, theCallTrace:'
					, empty($firstHitRow['playerId'])? 'N/A': $firstHitRow['playerId']
					, empty($firstHitRow['playerAccountId'])? 'N/A': $firstHitRow['playerAccountId']
				);
				$theParams['message'] = '';
				$theParams['message'] .= '```'. PHP_EOL;
				$theParams['message'] .= substr($theCallTrace, 0, 2000). PHP_EOL;
				$theParams['message'] .= '```'. PHP_EOL;
				$this->utils->detectSmallestNegativeBalanceAndNotifyIntoMM($beforeAmount, $afterAmount, $theParams, $_warningAmount, $isDryRun);
			}
		}/// EOF if( ! empty($detect_item_list) && ! empty($detectSmallestNegativeBalanceAndNotifyIntoMM) ){...

		return true;
	}

	public function updateWalletInfoInPlayer($playerId, $bigWallet) {
		if ($playerId) {
			$this->db->where('playerId', $playerId)->set("frozen", $bigWallet['main']['frozen'])
				->set('main_real', $bigWallet['main']['real'])
				->set('main_bonus', $bigWallet['main']['bonus'])
			// ->set('main_cashback', $bigWallet['main']['cashback'])
				->set('main_win_real', $bigWallet['main']['win_real'])
				->set('main_win_bonus', $bigWallet['main']['win_bonus'])
				->set('main_withdrawable', $bigWallet['main']['withdrawable'])
				->set('main_total_nofrozen', $bigWallet['main']['total_nofrozen'])
				->set('main_total', $bigWallet['main']['total'])
				->set('total_real', $bigWallet['total_real'])
				->set('total_bonus', $bigWallet['total_bonus'])
			// ->set('total_cashback', $bigWallet['total_cashback'])
				->set('total_win_real', $bigWallet['total_win_real'])
				->set('total_win_bonus', $bigWallet['total_win_bonus'])
				->set('total_withdrawable', $bigWallet['total_withdrawable'])
				->set('total_frozen', $bigWallet['total_frozen'])
				->set('total_total_nofrozen', $bigWallet['total_nofrozen'])
				->set('total_total', $bigWallet['total'])
			;

			return $this->runAnyUpdateWithoutResult('player');

			// $this->db->where('playerId', $playerId)->set("frozen", $frozenAmount)
			// 	->set('main_balance', $mainBalance)->set('total_balance', $totalBalance);
			// $success=$this->runAnyUpdateWithoutResult('player');

			// return $success;
		}

		return null;
	}

	public function getPendingBalanceById($playerId, $onlyMain = true) {
		return $this->getFrozenOnBigWalletById($playerId, $onlyMain);
	}

	public function getFrozenOnBigWalletById($playerId, $onlyMain = true) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		if ($onlyMain) {
			return doubleval($bigWallet['main'][self::BIG_WALLET_SUB_TYPE_FROZEN]);
		} else {
			//total frozen
			return doubleval($bigWallet['total_frozen']);
		}
	}

	public function convertBigWalletToBalanceDetails($bigWallet) {
		// return array(
		// 	"main_wallet" => $mainBal,
		// 	"sub_wallet" => $subWalletInfo,
		// 	"frozen" => $frozen,
		// 	"total_balance" => $totalBal,
		// 	"big_wallet"=> $this->getBigWalletByPlayerId($playerId),
		// );

		$balanceDetails = self::BALANCE_DETAILS_TEMPLATE;
		$balanceDetails['frozen'] = $bigWallet['main']['frozen'];
		$balanceDetails['main_wallet'] = $bigWallet['main']['total_nofrozen'];
		$balanceDetails['total_balance'] = $bigWallet['total'];

		$currency = $this->utils->getDefaultCurrency();

        $seamless_main_wallet_reference_enabled = $this->utils->getConfig('seamless_main_wallet_reference_enabled');
        if($seamless_main_wallet_reference_enabled) {
            $gameMap = $this->utils->getNonSeamlessGameSystemMap();
        }
        else {
            $gameMap = $this->utils->getGameSystemMap();
        }
		$balanceDetails['sub_wallet'] = [];
		foreach ($bigWallet['sub'] as $subWalletId => $subWallet) {
            if(!array_key_exists($subWalletId, $gameMap)) {
                continue;
            }
			$balanceDetails['sub_wallet'][] = [
				'currency' => $currency,
				'game' => $gameMap[$subWalletId],
				'totalBalanceAmount' => $subWallet['total'],
			];
		}

		return $balanceDetails;
	}

	public function getSubWalletTotalOnBigWalletByPlayer($playerId, $subWalletId) {
		return $this->getSubWalletOnBigWalletByPlayer($playerId, 'total', $subWalletId);
	}

	public function getSubWalletTotalNofrozenOnBigWalletByPlayer($playerId, $subWalletId) {
		return $this->getSubWalletOnBigWalletByPlayer($playerId, 'total_nofrozen', $subWalletId);
	}

	public function getSubWalletOnBigWalletByPlayer($playerId, $type, $subWalletId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		if (isset($bigWallet['sub'][$subWalletId][$type])) {
			return doubleval($bigWallet['sub'][$subWalletId][$type]);
		}

		return 0;
	}

	public function getMainWalletTotalOnBigWalletByPlayer($playerId) {
		return $this->getMainWalletOnBigWalletByPlayer($playerId, 'total');
	}

	public function getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId) {
		return $this->getMainWalletOnBigWalletByPlayer($playerId, 'total_nofrozen');
	}

	public function getMainWalletOnBigWalletByPlayer($playerId, $type) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		return doubleval($bigWallet['main'][$type]);
	}

	public function getTotalOnBigWalletByPlayer($playerId) {
		return $this->getTotalTypeOnBigWalletByPlayer($playerId, 'total');
	}

	public function getTotalNofrozenOnBigWalletByPlayer($playerId) {
		return $this->getTotalTypeOnBigWalletByPlayer($playerId, 'total_nofrozen');
	}

	public function getTotalTypeOnBigWalletByPlayer($playerId, $type) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		return doubleval($bigWallet[$type]);
	}

	public function tryFreezeSubTypeOnBigWallet($bigWallet, $subWalletId, $amount) {
		//move to freeze
	}

	public function tryMainToSubTypeOnBigWallet(&$bigWallet, $type, $subWalletId, $amount) {
		//transfer
		//dec main, inc sub
		if ($amount > $bigWallet['main'][$type]) {
			$transferAmount = $bigWallet['main'][$type];
			// $amount = $amount - $bigWallet['main'][$type];
			// $bigWallet['main'][$type]=0;
		} else {
			$transferAmount = $amount;
			// $bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $amount);
			// $amount=0;
		}
		$this->utils->debug_log('main to sub ' . $type, 'amount', $amount, 'transferAmount', $transferAmount, $bigWallet['sub'][$subWalletId][$type], $bigWallet['main'][$type]);
		//inc sub
		$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $transferAmount);
		//=======transfer limit
		$bigWallet['sub'][$subWalletId][$type] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId][$type] + $transferAmount);
		// if( $type == self::BIG_WALLET_SUB_TYPE_REAL && $this->utils->compareResultFloat($bigWallet['main']['real_limit'],'>',0) ){
		// 	//same dec/inc real limit
		// 	$bigWallet['main']['real_limit'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real_limit'] - $transferAmount);
		// 	if($this->utils->compareResultFloat($bigWallet['main']['real_limit'], '<=', 0)){
		// 		$bigWallet['main']['real_limit'] = 0;
		// 	}
		// 	$bigWallet['sub'][$subWalletId]['real_limit'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId]['real_limit'] + $transferAmount);
		// }
		// if( $type == self::BIG_WALLET_SUB_TYPE_BONUS && $this->utils->compareResultFloat($bigWallet['main']['bonus_limit'],'>',0)){
		// 	//same dec/inc bonus limit
		// 	$bigWallet['main']['bonus_limit'] = $this->utils->roundCurrencyForShow($bigWallet['main']['bonus_limit'] - $transferAmount);
		// 	if($this->utils->compareResultFloat($bigWallet['main']['bonus_limit'], '<=', 0)){
		// 		$bigWallet['main']['bonus_limit'] = 0;
		// 	}
		// 	$bigWallet['sub'][$subWalletId]['bonus_limit'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId]['bonus_limit'] + $transferAmount);
		// }

		return $this->utils->roundCurrencyForShow($amount - $transferAmount);
	}

	public function trySubToMainTypeOnBigWallet(&$bigWallet, $type, $subWalletId, $amount) {

		//inc main, dec sub
		if ($amount > $bigWallet['sub'][$subWalletId][$type]) {
			$transferAmount = $bigWallet['sub'][$subWalletId][$type];
			// $amount = $amount - $bigWallet['main'][$type];
			// $bigWallet['main'][$type]=0;
		} else {
			$transferAmount = $amount;
			// $bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] - $amount);
			// $amount=0;
		}
		$this->utils->debug_log('sub to main ' . $type, $transferAmount, $bigWallet['sub'][$subWalletId][$type], $bigWallet['main'][$type]);
		//dec sub
		$bigWallet['sub'][$subWalletId][$type] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId][$type] - $transferAmount);
		if ($this->utils->getConfig('big_wallet_transfer_to_main_real')) {
			//always to real wallet
			$bigWallet['main']['real'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real'] + $transferAmount);
		} else {
			$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] + $transferAmount);
		}
		//=======transfer limit
		// if( $type == self::BIG_WALLET_SUB_TYPE_REAL && $this->utils->compareResultFloat($bigWallet['sub'][$subWalletId]['real_limit'],'>',0) ){
		// 	//same dec/inc real limit
		// 	$bigWallet['sub'][$subWalletId]['real_limit'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId]['real_limit'] - $transferAmount);
		// 	if($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId]['real_limit'], '<=', 0)){
		// 		$bigWallet['sub'][$subWalletId]['real_limit'] = 0;
		// 	}
		// 	$bigWallet['main']['real_limit'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real_limit'] + $transferAmount);
		// }
		// if( $type == self::BIG_WALLET_SUB_TYPE_BONUS && $this->utils->compareResultFloat($bigWallet['sub'][$subWalletId]['bonus_limit'],'>',0) ){
		// 	//same dec/inc bonus limit
		// 	$bigWallet['sub'][$subWalletId]['bonus_limit'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId]['bonus_limit'] - $transferAmount);
		// 	if($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId]['bonus_limit'], '<=', 0)){
		// 		$bigWallet['sub'][$subWalletId]['bonus_limit'] = 0;
		// 	}
		// 	$bigWallet['main']['bonus_limit'] = $this->utils->roundCurrencyForShow($bigWallet['main']['bonus_limit'] + $transferAmount);
		// }

		return $this->utils->roundCurrencyForShow($amount - $transferAmount);
	}

	public function isAvailTransfer($bigWallet, $transfer_to, $transfer_from) {
		$success = true;
		if ($transfer_to == 0) {
			//to main
			$success = $this->existsSubWalletOnBigWallet($bigWallet, $transfer_from);
			// $this->utils->debug_log('check existsSubWalletOnBigWallet',$success , $bigWallet, $transfer_from);
		} else if ($transfer_from == 0) {
			//from main
			$success = $this->existsSubWalletOnBigWallet($bigWallet, $transfer_to);
			// $this->utils->debug_log('check existsSubWalletOnBigWallet',$success , $bigWallet, $transfer_to);

		} else {
			$success = false;
		}

		return $success;
	}

	public function transferByBigWallet(&$bigWallet, $amount, $transfer_to, $transfer_from, $walletType = null) {
		// $changed=false;

		$rules = $this->getBigWalletRules();
		if ($walletType) {
			$types = [$walletType];
		}
		//by order
		if ($transfer_to == 0) {
			$types = $rules['big_wallet_transfer_to_main_order'];
		} else {
			$types = $rules['big_wallet_transfer_from_main_order'];
		}
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			//set real
			$types = ['real'];
			// return true;
		}

		if (empty($types)) {
			$types = [];
		}
		foreach ($types as $type) {
			if ($this->utils->compareResultFloat($amount, ">", 0)) {
				if ($transfer_to == 0) {
					$subWalletId = $transfer_from;
					//dec main, inc sub
					$amount = $this->trySubToMainTypeOnBigWallet($bigWallet, $type, $subWalletId, $amount);

				} else {
					$subWalletId = $transfer_to;
					//dec main, inc sub
					$amount = $this->tryMainToSubTypeOnBigWallet($bigWallet, $type, $subWalletId, $amount);

				}
				$this->utils->debug_log('transfer once', $subWalletId, $amount);
			} else {
				// $changed=true;
				//done
				$this->utils->debug_log('done transfer', $amount, $transfer_to, $transfer_from);
				break;
			}
		}
		//should retotal
		$this->totalBigWallet($bigWallet);

		return $this->utils->compareResultFloat($amount, "=", 0);

		// return $changed;
	}

	public function transferOnBigWallet($playerId, $amount, $transfer_to, $transfer_from, $walletType = null, &$err_code) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		if($this->utils->getConfig('verbose_log')){
			$this->utils->debug_log('transferOnBigWallet params', $playerId, $amount, $transfer_to, $transfer_from, $walletType,'bigWallet', $bigWallet);
		}else{
			$this->utils->debug_log('transferOnBigWallet params', $playerId, $amount, $transfer_to, $transfer_from, $walletType,'hide bigWallet');
		}

		if (empty($bigWallet) || !$this->isAvailTransfer($bigWallet, $transfer_to, $transfer_from)) {
			$this->utils->error_log ('not avail', $playerId, $amount, $transfer_to, $transfer_from, $bigWallet);
			$err_code = self::WALLET_FAILED_TRANSFER;
			return false;
		} elseif ($this->utils->compareResultFloat($amount, "<=", 0)) {
			$this->utils->error_log('empty amount', $playerId, $amount, $transfer_to, $transfer_from, $bigWallet);
			$err_code =  self::WALLET_INVALID_TRANSFER_AMOUNT;
			return false;
		} elseif ($transfer_from == 0 && isset($bigWallet['main']['real']) && ($this->utils->compareResultFloat($bigWallet['main']['real'], "<", $amount) || $this->utils->compareResultFloat($bigWallet['main']['real'], "<=", 0))) {
			$this->utils->error_log('empty balance', $playerId, $amount, $transfer_to, $transfer_from, $bigWallet);
			$err_code =  self::WALLET_NO_ENOUGH_BALANCE;
			return false;
		} elseif ($transfer_from != 0 && isset($bigWallet['sub'][$transfer_from]['real']) && ($this->utils->compareResultFloat($bigWallet['sub'][$transfer_from]['real'], "<", $amount) || $this->utils->compareResultFloat($bigWallet['sub'][$transfer_from]['real'], "<=", 0))) {
			$this->utils->error_log('empty balance', $playerId, $amount, $transfer_to, $transfer_from, $bigWallet);
			$err_code =  self::WALLET_NO_ENOUGH_BALANCE;
			return false;
		}

		$changed = $this->transferByBigWallet($bigWallet, $amount, $transfer_to, $transfer_from, $walletType);
        $this->utils->debug_log(__METHOD__, "debug_transfer_player_id_{$playerId}", 'transferByBigWallet', 'changed', $changed);

		if($this->utils->getConfig('verbose_log')){
			$this->utils->debug_log('transferOnBigWallet changed', $changed, 'bigWallet', $bigWallet);
		}else{
			$this->utils->debug_log('transferOnBigWallet changed', $changed);
		}

		if ($changed) {
            // mock transfer override big wallet
            if ($amount > 0) {
                $gamePlatformId = $transfer_to == 0 ? $transfer_from : $transfer_to;
                $gameApi = $this->utils->loadExternalSystemLibObject($gamePlatformId);

                if ($gameApi) {
                    $isMockPlayerTransferOverrideBigWallet = $gameApi->isMockPlayerTransferOverrideBigWallet($playerId);

                    if ($isMockPlayerTransferOverrideBigWallet) {
                        $mockAmount = $gameApi->getMockTransferOverrideSettingsForBigWallet('amount');
                        $bigWallet = $this->handleMockTransferOverrideBigWallet($bigWallet, $gamePlatformId, $transfer_to, $playerId, $mockAmount);
                    }
                }
            }

			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		} else {
			//drop bigWallet
			$this->utils->error_log('nothing changed', $playerId, $amount, $transfer_to, $transfer_from, $bigWallet);
			return false;
		}
	}

    public function handleMockTransferOverrideBigWallet($bigWallet, $gamePlatformId, $transfer_to, $playerId, $amount = 5) {
        $this->load->model(['transactions', 'wallet_model']);

        if ($transfer_to != Wallet_model::MAIN_WALLET_ID) {
            $transactionType = Transactions::TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET;

            $bigWallet['sub'][$gamePlatformId]['real'] = $amount;
            $bigWallet['sub'][$gamePlatformId]['withdrawable'] = $amount;
            $bigWallet['sub'][$gamePlatformId]['total_nofrozen'] = $amount;
            $bigWallet['sub'][$gamePlatformId]['total'] = $amount;
            $bigWallet['total_real'] = $amount;
            $bigWallet['total_withdrawable'] = $amount;
            $bigWallet['total_nofrozen'] = $amount;
            $bigWallet['total'] = $amount;
            
            $this->utils->debug_log(__METHOD__, "player_id_{$playerId}", 'TRANSFER_TO_SUB_WALLET_FROM_MAIN_WALLET', $bigWallet);
        } else {
            $transactionType = Transactions::TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET;

            $bigWallet['main']['real'] = $amount;
            $bigWallet['main']['withdrawable'] = $amount;
            $bigWallet['main']['total_nofrozen'] = $amount;
            $bigWallet['main']['total'] = $amount;
            $bigWallet['total_real'] = $amount;
            $bigWallet['total_withdrawable'] = $amount;
            $bigWallet['total_nofrozen'] = $amount;
            $bigWallet['total'] = $amount;

            $this->utils->debug_log(__METHOD__, "player_id_{$playerId}", 'TRANSFER_FROM_SUB_WALLET_TO_MAIN_WALLET', $bigWallet);
        }

        return $bigWallet;
    }

	public function transferInOneWallet($playerId, $subWalletId, $fromType, $toType, $originTransferAmount) {
		$success = true;
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		if (empty($bigWallet)
			|| $this->utils->compareResultFloat($bigWallet['sub'][$subWalletId][$fromType], '<', $originTransferAmount)
			|| $this->utils->compareResultFloat($originTransferAmount, "<=", 0)) {
			$this->utils->error_log('not avail or empty amount', $playerId, $subWalletId, $originTransferAmount, $fromType, $toType, $bigWallet);
			// $message=lang('Cannot transfer').' '.$amount;
			return false;
		}

		$bigWallet['sub'][$subWalletId][$fromType] -= $originTransferAmount;
		$bigWallet['sub'][$subWalletId][$toType] += $originTransferAmount;

		// if($changed){
		// $this->totalBigWallet($bigWallet, $playerId);
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		// }else{
		//drop bigWallet
		// $this->utils->error_log('transferInOneWallet nothing changed', $playerId, $subWalletId, $fromType, $toType, $originTransferAmount, $bigWallet);
		// return false;
		// }

		return $success;
	}

	public function isAvailWinType($type, $wallet) {
		$isAvail = false;
		switch ($type) {
		case 'real':
			$isAvail = $this->utils->compareResultFloat($wallet[$type], '>', 0);
			break;

		case 'bonus':
			$isAvail = $this->utils->compareResultFloat($wallet[$type], '>', 0);
			break;

		case 'win_real':
			$isAvail = $this->utils->compareResultFloat($wallet['real'], '>', 0);
			break;

		case 'win_bonus':
			$isAvail = $this->utils->compareResultFloat($wallet['bonus'], '>', 0);
			break;
		}

		return $isAvail;
	}

	public function getBigWalletRules() {
		return $this->utils->getConfig('big_wallet_rules');
	}

	public function tryWinSubOnBigWallet($bigWallet, $subWalletId, $amount) {
		//order by type
		$rules = $this->getBigWalletRules();
		$types = $rules['big_wallet_win_order'];

		$this->utils->debug_log('win on subWalletId', $subWalletId, 'amount', $amount, 'types', $types);

		foreach ($types as $type) {
			if ($this->utils->compareResultFloat($amount, ">", 0)) {
				$winAmount = 0;
				//check min, max limit
				// list($min, $max) = $this->getMinMaxLimit($bigWallet, $subWalletId, $type);
				//only for real and bonus
				if ($this->isAvailWinType($type, $bigWallet['sub'][$subWalletId]) || $type == 'real' || $type == 'bonus') {
					//should add to type, but < max
					// $amtType=$this->utils->roundCurrencyForShow($amount + $bigWallet['sub'][$subWalletId][$type]);

					// if($amtType > $max){
					// 	//bigger than

					// 	$winAmount= $this->utils->roundCurrencyForShow($max - $bigWallet['sub'][$subWalletId][$type]);
					// 	if($this->utils->compareResultFloat($winAmount, '>', 0 )){
					// 		$amount = $this->utils->roundCurrencyForShow($amount - $winAmount);
					// 		$bigWallet['sub'][$subWalletId][$type]=$max;
					// 	}else{
					// 		//ignore because reach max
					// 		$this->utils->debug_log('ignore win because reach max', $subWalletId, $type.' amount',
					// 			$bigWallet['sub'][$subWalletId][$type],'winAmount',$winAmount, 'min', $min, 'max', $max);
					// 	}

					// }else{
					//add to win
					$winAmount = $amount;
					$this->utils->debug_log('subWalletId', $bigWallet['sub'][$subWalletId][$type], $winAmount);
					$bigWallet['sub'][$subWalletId][$type] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId][$type] + $winAmount);
					$amount = 0;

					// }else{
					//ignore because reach max
					// $this->utils->debug_log('ignore win because reach max', $subWalletId, $type.' amount',
					// $bigWallet['sub'][$subWalletId][$type], 'min', $min, 'max', $max);
					// }

					// if($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId][$type], "=" ,0 )){
					//
					// }
				} else {
					// $this->utils->debug_log('ignore win subwallet type', $subWalletId, $type.' amount',
					// $bigWallet['sub'][$subWalletId][$type], 'min', $min, 'max', $max);
				}

				$this->utils->debug_log('win add ' . ($winAmount) . ' to subWalletId:' . $subWalletId . ' type:' . $type . ' left: ' . $amount);

				// $this->utils->debug_log('win subwallet', $subWalletId, $type, 'winAmount', $winAmount, 'left', $amount);

			} else {
				//done
				$this->utils->debug_log('win subwallet done');
				break;
			}
		}

		return $bigWallet;
	}

	public function getMinMaxLimit($bigWallet, $subWalletId, $type) {
		$min = 0;
		// if($type=='real' || $type=='bonus'){
		// 	$max=$bigWallet['sub'][$subWalletId][$type.'_limit'];
		// 	if($this->utils->compareResultFloat($max, "<=", 0)){
		// 		//unlimit
		// 		$max=PHP_INT_MAX;
		// 	}
		// }else{
		//no limit for win
		$max = PHP_INT_MAX;
		// }
		return [$min, $max];
	}

	public function tryLossSubOnBigWallet($bigWallet, $subWalletId, $amount) {
		//order by type
		$types = $this->getBigWalletRules()['big_wallet_loss_order'];

		foreach ($types as $type) {
			if ($this->utils->compareResultFloat($amount, ">", 0)) {
				$lossAmount = 0;
				//check min, max limit
				// list($min, $max) = $this->getMinMaxLimit($bigWallet, $subWalletId, $type);
				if ($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId][$type], '>', 0)) {

					if ($amount > $bigWallet['sub'][$subWalletId][$type]) {

						$lossAmount = $bigWallet['sub'][$subWalletId][$type];
						//bigger than
						$amount = $this->utils->roundCurrencyForShow($amount - $lossAmount);
						$bigWallet['sub'][$subWalletId][$type] = 0;

					} else {
						$lossAmount = $amount;

						$bigWallet['sub'][$subWalletId][$type] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$subWalletId][$type] - $amount);
						$amount = 0;
					}

					// if($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId][$type], "=" ,0 )){
					//
					// }
				} else {
					$this->utils->debug_log('ignore loss subwallet type', $subWalletId, $type . ' amount',
						$bigWallet['sub'][$subWalletId][$type]);
				}

				$this->utils->debug_log('loss minus ' . ($lossAmount) . ' from subWalletId:' . $subWalletId . ' type:' . $type . ' left: ' . $amount);
				// $this->utils->debug_log('loss subwallet', $subWalletId, $type, 'lossAmount', $lossAmount, 'left', $amount);

			} else {
				//done
				$this->utils->debug_log('loss subwallet done');
				break;
			}
		}

		return $bigWallet;
	}

	public function getAvailableRefreshWalletListByPlayerId($player_id, $force_ignore_0 = TRUE){
        $this->load->model(array('game_provider_auth'));

	    $result = [
	        'success' => TRUE
        ];

        // ! force_refresh_all_subwallets
        $force_refresh = $this->utils->isEnabledFeature('force_refresh_all_subwallets');

        $game_accounts = $this->CI->game_provider_auth->getAllGameAccountsKVByPlayerId($player_id);

        $bigWallet = $this->getOrderBigWallet($player_id);

        $this->utils->debug_log('big wallet', $bigWallet);

        if(empty($bigWallet)){
            $result['success'] = FALSE;
            $result['message'] = lang('No found wallet');
            return $result;
        }

		$seamless_main_wallet_reference_enabled = $this->utils->getConfig('seamless_main_wallet_reference_enabled');

        if(!$bigWallet['sub'] && empty($bigWallet['sub']) && !$seamless_main_wallet_reference_enabled){
            $result['success'] = FALSE;
            $result['message'] = lang('No found subwallet');
            return $result;
        }

        $sub = $bigWallet['sub'];

        $this->utils->debug_log('big wallet sub', $sub);

        $refresh_subwallet_list = [];
        foreach($sub as $subWalletId => $subWallet){
            if(empty($subWallet)){
                continue;
            }

            if(!isset($game_accounts[$subWalletId])){
                continue;
            }

            if(!$this->CI->game_provider_auth->isRegisterdByEntry($game_accounts[$subWalletId])){
                continue;
            }

            if($force_refresh){
                $refresh_subwallet_list[] = $subWalletId;
                continue;
            }

            if(!$force_ignore_0){
                $refresh_subwallet_list[] = $subWalletId;
                continue;
            }

            /** @var Abstract_game_api $api */
            $api = $this->CI->utils->loadExternalSystemLibObject($subWalletId);
            if(empty($api)){
                continue;
            }

            // don't show if seamless game
            if($api->isSeamLessGame() && $this->utils->getConfig('seamless_main_wallet_reference_enabled')) {
                continue;
            }

            //don't ignore 0 or really >0
            $total_nofrozen = (float)$subWallet['total_nofrozen'];
            if($api->isIgnoredZeroOnRefresh() && $total_nofrozen <= 0){
                continue;
            }

            $refresh_subwallet_list[] = $subWalletId;
        }
        $result['bigWallet'] = $bigWallet;
        $result['refresh_subwallet_list'] = $refresh_subwallet_list;

        return $result;
    }

	public function refreshSubWalletByBigWallet(&$bigWallet, $subWalletId, $balance) {
		// $this->utils->debug_log('refresh subwallet', $subWalletId, $balance);

		$changed = false;

        if($this->utils->getConfig('seamless_main_wallet_reference_enabled')) {
            return false;
        }

		$rules = $this->getBigWalletRules();
		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			$changed = $bigWallet['sub'][$subWalletId]['real'] != $balance;
			//set real
			$bigWallet['sub'][$subWalletId]['real'] = $balance;
			return $changed;
		}

		// if($this->utils->isEnabledPromotionRule('only_real_when_refresh')){

		// 	if(!$this->utils->compareResultFloat($balance, '=', $bigWallet['sub'][$subWalletId]['real'])){
		// 		$changed=true;
		// 		$bigWallet['sub'][$subWalletId]['real']=$balance;
		// 	}

		// }else{

		// if($this->utils->compareResultFloat($bigWallet['sub'][$subWalletId]['total_nofrozen'],'=',$balance)){
		// 	//don't change
		// }else{
		$adjustment = $this->utils->roundCurrencyForShow($balance - $bigWallet['sub'][$subWalletId]['total_nofrozen']);

		if ($this->utils->compareResultFloat($adjustment, '>', 0)) {
			$changed = true;
			//win
			$bigWallet = $this->tryWinSubOnBigWallet($bigWallet, $subWalletId, $adjustment);
		} elseif ($this->utils->compareResultFloat($adjustment, '<', 0)) {
			$changed = true;
			//loss
			$bigWallet = $this->tryLossSubOnBigWallet($bigWallet, $subWalletId, abs($adjustment));
		}
		//add to subwallet
		// $type=self::BIG_WALLET_SUB_TYPE_WIN_REAL;
		// $bigWallet['sub'][$subWalletId][$type] += $adjustment;
		// }

		// }
		return $changed;
	}

	/**
	 * Refresh Sub-wallet structure inside Big wallet structure
	 * @param int $playerId
	 * @param int $subWalletId
	 * @param double $balance
	 *
	 * @return boolean
	 */
	public function refreshSubWalletOnBigWalletIncludingBlockedApi($playerId, $subWalletId, $balance) {
		$bigWallet = $this->wallet_model->getBigWalletInclBlockedGameByPlayerId($playerId, $subWalletId);

		if (!$this->existsSubWalletOnBigWallet($bigWallet, $subWalletId)) {
			$this->utils->error_log('refresh subwallet failed', $playerId, $subWalletId, $balance, $bigWallet);
			return false;
		}

		$changed = $this->refreshSubWalletByBigWallet($bigWallet, $subWalletId, $balance);

		if ($changed) {
			$this->utils->debug_log('refresh subwallet', $playerId, $subWalletId, $balance);
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}

		return true;
	}

	public function refreshSubWalletOnBigWallet($playerId, $subWalletId, $balance) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		if (!$this->existsSubWalletOnBigWallet($bigWallet, $subWalletId)) {
			$this->utils->error_log('refresh subwallet failed', $playerId, $subWalletId, $balance, $bigWallet);
			return false;
		}

		$changed = $this->refreshSubWalletByBigWallet($bigWallet, $subWalletId, $balance);

		if ($changed) {
			$this->utils->debug_log('refresh subwallet', $playerId, $subWalletId, $balance);
			// $this->totalBigWallet($bigWallet, $playerId);
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		} else {
			// $this->utils->debug_log('same balance, nothing changed');
		}

		return true;
	}

	public function updateSubWalletBalanceToZeroOnBigWallet($playerId, $subWalletId, $balance) {
		$bigWallet = $this->getBigWalletInclBlockedGameByPlayerId($playerId, $subWalletId);

		if (!$this->existsSubWalletOnBigWallet($bigWallet, $subWalletId)) {
			$this->utils->error_log('refresh subwallet failed', $playerId, $subWalletId, $balance, $bigWallet);
			return false;
		}

		$changed = $this->refreshSubWalletByBigWallet($bigWallet, $subWalletId, $balance);

		if ($changed) {
			$this->utils->info_log('refresh subwallet of player with ID of: >>>>>>>>', $playerId,'sub wallet ID:', $subWalletId,'balance:', $balance);
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		} else {
			// $this->utils->debug_log('same balance, nothing changed');
		}
		return true;
	}

	public function applyResultToSubWalletByBigWallet($playerId, $subWalletId, $adjustment) {

		if ($this->utils->compareResultFloat($adjustment, '>', 0)) {
			$changed = true;
			//win
			$bigWallet = $this->tryWinSubOnBigWallet($bigWallet, $subWalletId, $adjustment);
		} else if ($this->utils->compareResultFloat($adjustment, '<', 0)) {
			$changed = true;
			//loss
			$bigWallet = $this->tryLossSubOnBigWallet($bigWallet, $subWalletId, abs($adjustment));
		}

		return $changed;
	}

	public function applyResultToSubWalletOnBigWallet($playerId, $subWalletId, $adjustment) {
		//ignore 0
		if ($adjustment == 0) {
			return true;
		}

		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$this->utils->debug_log('applyResultToSubWalletOnBigWallet', $playerId, $subWalletId, $adjustment);
		if (!$this->existsSubWalletOnBigWallet($bigWallet, $subWalletId)) {
			$this->utils->error_log('applyResultToSubWalletOnBigWallet failed', $playerId, $subWalletId, $adjustment, $bigWallet);
			return false;
		}

		$changed = $this->applyResultToSubWalletByBigWallet($bigWallet, $subWalletId, $adjustment);

		if ($changed) {
			// $this->totalBigWallet($bigWallet, $playerId);
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		} else {
			$this->utils->error_log('same balance, nothing changed', $playerId, $subWalletId, $adjustment, $bigWallet);
		}

		return $changed;
	}

	public function existsSubWalletOnBigWallet($bigWallet, $subWalletId) {
		return isset($bigWallet['sub'][$subWalletId]) && !empty($bigWallet['sub'][$subWalletId]);
	}

	/**
	 * @param walletId 0=main
	 */
	public function getTargetTransferBalanceOnMainWallet($playerId, $walletId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$this->totalBigWallet($bigWallet, $playerId);

		if ($walletId == self::MAIN_WALLET_ID) {
			//can transfer all
			$targetBalance = $bigWallet['main']['total_nofrozen'];
		} else {
			//sub wallet, only real and win_real
			$targetBalance = $bigWallet['sub'][$walletId]['withdrawable'];
		}

		return $targetBalance;
	}

	public function getTargetTotalBalanceOnMainWallet($playerId, $walletId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$this->totalBigWallet($bigWallet, $playerId);

		if ($walletId == self::MAIN_WALLET_ID) {
			//total
			$total = $bigWallet['main']['total_nofrozen'];
		} else {
			//sub wallet, total
			$total = $bigWallet['sub'][$walletId]['total_nofrozen'];
		}

		return $total;
	}

	public function moveAllToRealOnMainWallet($playerId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		//record history before
		// $this->recordPlayerBeforeActionWalletBalanceHistory(self::BALANCE_ACTION_REFRESH,
		// 	$playerId, $affId, $transactionId, $amount, $saleOrderId = null, $playerPromoId = null,
		// 	$subWalletId = null, $walletAccountId = null, $gamePlatformId = null);

		$this->recordPlayerBeforeActionWalletBalanceHistory(Wallet_model::BALANCE_ACTION_MOVE_TO_REAL,
			$playerId, null, -1, 0, null, null, null, null, null);

		// $subwallets=$bigWallet['sub'];
		// $allTypes=self::BIG_WALLET_SUB_TYPE_ALL;
		// foreach ($subwallets as $id => $sub) {
		// 	$bigWallet['sub'][$id]['real'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$id]['real'] + $bigWallet['sub'][$id]['real_for_bonus'] + $bigWallet['sub'][$id]['bonus'] + $bigWallet['sub'][$id]['win_real'] + $bigWallet['sub'][$id]['win_bonus']);
		// 	$bigWallet['sub'][$id]['real_for_bonus']=0;
		// 	$bigWallet['sub'][$id]['bonus']=0;
		// 	$bigWallet['sub'][$id]['win_real']=0;
		// 	$bigWallet['sub'][$id]['win_bonus']=0;
		// }
		// //merge to real
		// $bigWallet['main']['real'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real'] + $bigWallet['main']['real_for_bonus'] + $bigWallet['main']['bonus'] + $bigWallet['main']['win_real'] + $bigWallet['main']['win_bonus']);
		// $bigWallet['main']['real_for_bonus']=0;
		// $bigWallet['main']['bonus']=0;
		// $bigWallet['main']['win_real']=0;
		// $bigWallet['main']['win_bonus']=0;

		$this->moveAllToRealByMainWallet($bigWallet);

		// $this->totalBigWallet($bigWallet, $playerId);

		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	/**
	 * Process Real of sub wallet and main wallet
	 *
	 * @param array $bigWallet the big wallet data, must be an array
	 *
	 * @return array $bigWallet the processes big wallet including sub wallet
	 */
	public function moveAllToRealByMainWallet(&$bigWallet) {

		if(! is_array($bigWallet)){
			$bigWallet = (array) $bigWallet;
		}

		$bigWalletSub = (is_array($bigWallet['sub']) && count($bigWallet['sub']) > 0) ? $bigWallet['sub'] : [];

		$subwallets = $bigWalletSub;

		$allTypes = self::BIG_WALLET_SUB_TYPE_ALL;

		# check first if bigWallet array have data
		if(count($bigWalletSub) > 0){

			foreach ($subwallets as $id => $sub) {
				$bigWallet['sub'][$id]['real'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$id]['real'] + $bigWallet['sub'][$id]['real_for_bonus'] + $bigWallet['sub'][$id]['bonus'] + $bigWallet['sub'][$id]['win_real'] + $bigWallet['sub'][$id]['win_bonus']);
				$bigWallet['sub'][$id]['real_for_bonus'] = 0;
				$bigWallet['sub'][$id]['bonus'] = 0;
				$bigWallet['sub'][$id]['win_real'] = 0;
				$bigWallet['sub'][$id]['win_bonus'] = 0;
			}

			//merge to real
			$bigWallet['main']['real'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real'] + $bigWallet['main']['real_for_bonus'] + $bigWallet['main']['bonus'] + $bigWallet['main']['win_real'] + $bigWallet['main']['win_bonus']);
			$bigWallet['main']['real_for_bonus'] = 0;
			$bigWallet['main']['bonus'] = 0;
			$bigWallet['main']['win_real'] = 0;
			$bigWallet['main']['win_bonus'] = 0;

		}

		return $bigWallet;
	}

	public function moveAnyBigWallet($playerId, $from, $from_type, $to, $to_type, $subwallet_id, $amount) {
		$success = true;
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		if ($from == 'main') {
			$bigWallet[$from][$from_type] = $this->utils->roundCurrencyForShow($bigWallet[$from][$from_type] - $amount);
		} else {
			$bigWallet[$from][$subwallet_id][$from_type] = $this->utils->roundCurrencyForShow($bigWallet[$from][$subwallet_id][$from_type] - $amount);
		}
		if ($to == 'main') {
			$bigWallet[$to][$to_type] = $this->utils->roundCurrencyForShow($bigWallet[$to][$to_type] + $amount);
		} else {
			$bigWallet[$to][$subwallet_id][$to_type] = $this->utils->roundCurrencyForShow($bigWallet[$to][$subwallet_id][$to_type] + $amount);
		}

		// $this->totalBigWallet($bigWallet, $playerId);

		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
	}

	public function moveSubWalletToReal(&$bigWallet, $walletType) {

		$bigWallet['sub'][$walletType]['real'] = $this->utils->roundCurrencyForShow($bigWallet['sub'][$walletType]['real']
			 + $bigWallet['sub'][$walletType]['real_for_bonus']
			 + $bigWallet['sub'][$walletType]['bonus']
			 + $bigWallet['sub'][$walletType]['win_real']
			 + $bigWallet['sub'][$walletType]['win_bonus']);
		$bigWallet['sub'][$walletType]['real_for_bonus'] = 0;
		$bigWallet['sub'][$walletType]['bonus'] = 0;
		$bigWallet['sub'][$walletType]['win_real'] = 0;
		$bigWallet['sub'][$walletType]['win_bonus'] = 0;

		return true;
	}

	public function moveMainWalletToReal(&$bigWallet) {

		$bigWallet['main']['real'] = $this->utils->roundCurrencyForShow($bigWallet['main']['real']
			 + $bigWallet['main']['real_for_bonus']
			 + $bigWallet['main']['bonus']
			 + $bigWallet['main']['win_real']
			 + $bigWallet['main']['win_bonus']);
		$bigWallet['main']['real_for_bonus'] = 0;
		$bigWallet['main']['bonus'] = 0;
		$bigWallet['main']['win_real'] = 0;
		$bigWallet['main']['win_bonus'] = 0;

		return true;
	}

	public function updateSubWalletsOnBigWallet($playerId, $balancesFromApi) {

		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$changed=false;
		foreach ($balancesFromApi as $apiId => $apiRlt) {
			if ($apiRlt['success'] && isset($apiRlt['balance'])) {
				$balance = $apiRlt['balance'];
				$changedThis=$this->refreshSubWalletByBigWallet($bigWallet, $apiId, $balance);
				if($changedThis){
					$changed=$changedThis;
				}
			}
		}

		if($changed){
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}
		return true;
	}

	public function reorderWallet(&$bigWallet) {
		//never save reorder wallet
		// $changed = false;
		if (empty($bigWallet)) {
		    return;
		}

        // $config_wallet_type = $this->utils->getConfig('wallet_type');
        $this->addOldFormat($bigWallet);

        $game_wallet_settings = $this->operatorglobalsettings->getGameWalletSettings();

        if(empty($bigWallet['sub'])){
            return;
        }

        $subwalets = $bigWallet['sub'];
        $sort = 0;
        foreach($subwalets as $wallet_id => $wallet_data){
            if(!isset($game_wallet_settings[$wallet_id])){
                continue;
            }

            $sort = ($game_wallet_settings[$wallet_id]['sort'] > $sort) ? $game_wallet_settings[$wallet_id]['sort'] : $sort;
            $wallet_data['sort'] = $game_wallet_settings[$wallet_id]['sort'];
            $wallet_data['enabled_on_desktop'] = $game_wallet_settings[$wallet_id]['enabled_on_desktop'];
            $wallet_data['enabled_on_mobile'] = $game_wallet_settings[$wallet_id]['enabled_on_mobile'];

            $subwalets[$wallet_id] = $wallet_data;
        }

        foreach($subwalets as $wallet_id => $wallet_data){
            $sort++;

            $wallet_data['sort'] = (isset($wallet_data['sort'])) ? $wallet_data['sort'] : $sort;
            $wallet_data['enabled_on_desktop'] = (isset($wallet_data['enabled_on_desktop'])) ? $wallet_data['enabled_on_desktop'] : TRUE;
            $wallet_data['enabled_on_mobile'] = (isset($wallet_data['enabled_on_mobile'])) ? $wallet_data['enabled_on_mobile'] : TRUE;

            if($this->utils->getConfig('seamless_main_wallet_reference_enabled')) {
                $gameMap=$this->utils->getNonSeamlessGameSystemMap();
                if(!array_key_exists($wallet_id, $gameMap)) {
                    unset($subwalets[$wallet_id]);
                    continue;
                }
            }

            $subwalets[$wallet_id] = $wallet_data;
        }

        uasort($subwalets, function($entry1, $entry2){
            if ($entry1['sort'] == $entry2['sort']) {
                return 0;
            }
            return ($entry1['sort'] < $entry2['sort']) ? -1 : 1;
        });

        $bigWallet['sub'] = $subwalets;
        // $changed = true;
	}

	public function addOldFormat(&$bigWallet){
		$gameMap=$this->utils->getGameSystemMap();

		foreach ($bigWallet['sub'] as $subwalletId => &$subwallet) {
			$subwallet['game'] = lang($gameMap[$subwalletId]);
			$subwallet['totalBalanceAmount'] = $subwallet['real'];
			$subwallet['typeId'] = $subwalletId;
			$subwallet['subwalletId'] = $subwalletId;
		}
	}

	public function getBigWalletAddOldFormat($playerId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$this->addOldFormat($bigWallet);
		return $bigWallet;
	}

	public function getOrderBigWallet($playerId) {
		$bigWallet = $this->getBigWalletByPlayerId($playerId);
		$this->reorderWallet($bigWallet);
		return $bigWallet;
	}

	public function getTotalBalanceOnBigWallet($bigWalletJsonStr) {
		$total=0;
		if(!empty($bigWalletJsonStr)){
			$bigWallet=$this->utils->decodeJson($bigWalletJsonStr);
			if(!empty($bigWallet)){
				$total=$bigWallet['total'];
			}
		}

		return $total;
	}

	public function roundMainWallet($playerId){
		return $this->roundMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL);
	}

	public function roundMainByBigWallet(&$bigWallet, $type) {
		$bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type]);
		return true;
	}

	public function roundMainOnBigWallet($playerId, $type) {

		//check sub types
		if (empty($playerId) || empty($type) || !in_array($type, self::BIG_WALLET_SUB_TYPE_ALL)) {
			$this->utils->error_log('empty player id or wrong sub type:' . $type);
			return false;
		}

		$rules = $this->getBigWalletRules();

		if ($rules['force_real']) {
			//set real and move to real
			// $this->moveAllToRealByMainWallet($bigWallet);
			$type = 'real';
			//set real
			// return true;
		}

		//update cache first
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$this->roundMainByBigWallet($bigWallet, $type);

		// $this->roundMainOnBigWallet($bigWallet, $type, $incAmount);

		// $bigWallet['main'][$type] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type] + $incAmount);
		// if( ($type=='real' || $type=='bonus') ){
		// 	$this->utils->debug_log('add '.$type.' limit '.$incAmount);
		// $bigWallet['main'][$type.'_limit'] = $this->utils->roundCurrencyForShow($bigWallet['main'][$type.'_limit'] + $incAmount);
		// }
		// $this->totalBigWallet($bigWallet, $playerId);

		//save back
		return $this->updateBigWalletByPlayerId($playerId, $bigWallet);

		// return true;

	}

	public function generateBigWalletBalanceByDate($datetime) {
		$date = new DateTime($datetime);

		$_database = '';
		$_extra_db_name = '';
		$is_balance_history_in_extra_db = $this->utils->_getBalanceHistoryInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_balance_history_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}

		$this->db->delete('player_daily_balance', ['balance_date' => $date->format('Y-m-d')]);
		$queryStr = "
            SELECT
				t1.playerId as player_id,
			    '$datetime' as balance_date,
				COALESCE(t2.total_balance, 0) as total_balance,
			    NOW() as created_at
			FROM player t1 LEFT JOIN
			(
				SELECT player_id, total_balance FROM {$_database}balance_history
				WHERE id IN (
					SELECT MAX(id)
					FROM {$_database}balance_history
					WHERE created_at <= '$datetime'
					GROUP BY player_id
				)
			) t2 ON t1.playerId = t2.player_id
			WHERE t1.createdOn <= '$datetime'
        ";

        $this->utils->debug_log('Generating Big Wallet Balance this will take time. Please wait.......');
		$query = $this->db->query($queryStr);
       	$result =  $query->result_array();

		if (!empty($result)) {
			$rest = $this->db->insert_batch('player_daily_balance', $result);
		}
		$this->utils->debug_log('Done generating Big Wallet Balance.');
		return true;
	}

	public function getTotalBigWalletBalanceByDate($datetime) {
		$date = new DateTime($datetime);
		$sql = "select sum(total_balance) total from player_daily_balance where balance_date = '".$date->format('Y-m-d')."'";
		$result = $this->db->query($sql);
		$total = $this->getOneRowOneField($result, 'total');
		return $total;
	}

	//====mdb====================================================================
	/**
	 * only insert missing wallet in playeraccount
	 * @param  int $playerId
	 * @param  object $bigWallet
	 * @return bool
	 */
	public function patchOldWallet($playerId, $bigWallet, $db=null){
		if(empty($db)){
			$db=$this->db;
		}
		$success=true;
		if(!empty($playerId) && !empty($bigWallet)){

			$currency_code=$this->utils->getCurrentCurrency()['currency_code'];
			//delete in playeraccount
			//then insert all
			$db->where('playerId', $playerId);
			$this->runRealDelete('playeraccount', $db);

			$insertrows[]=[
				'playerId' => $playerId,
				'type'=>self::TYPE_MAINWALLET,
				'typeId'=>'0',
				'currency'=>$currency_code,
				'typeOfPlayer'=>'real',
				'status'=>'0',
				'totalBalanceAmount' => $bigWallet['main']['total_nofrozen'],
			];

			foreach ($bigWallet['sub'] as $subWalletId=>$sub) {
				$insertrows[]=[
					'playerId' => $playerId,
					'type'=>self::TYPE_SUBWALLET,
					'typeId'=>$subWalletId,
					'currency'=>$currency_code,
					'typeOfPlayer'=>'real',
					'status'=>'0',
					'totalBalanceAmount' => $sub['total_nofrozen'],
				];
			}
            $db->insert_batch('playeraccount', $insertrows);

			// $this->db->from('playeraccount')->where('playerId', $playerId);
			// $rows = $this->runMultipleRowArray();
			// $updaterows=[];
			// $insertrows=[];
			// $missingMain=true;
			// $missingSubList=[];
			// foreach ($bigWallet['sub'] as $subWalletId => $val) {
			// 	$missingSubList[$subWalletId]=$val['total_nofrozen'];
			// }
			// if(!empty($rows)){
			// 	foreach ($rows as $row) {
			// 		$subWalletId=$row['typeId'];
			// 		if ($row['type'] == self::TYPE_MAINWALLET) {
			// 			$missingMain=false;
			// 			//found main wallet
			// 			$updaterows[] = [
			// 				'playerAccountId' => $row['playerAccountId'],
			// 				'totalBalanceAmount' => $bigWallet['main']['total_nofrozen'],
			// 			];
			// 		}elseif(isset($bigWallet['sub'][$subWalletId])){
			// 			//remove exist subwallet
			// 			unset($missingSubList[$subWalletId]);
			// 			$updaterows[] = [
			// 				'playerAccountId' => $row['playerAccountId'],
			// 				'totalBalanceAmount' => $bigWallet['sub'][$subWalletId]['total_nofrozen'],
			// 			];
			// 		}
			// 	}
			// }else{
			// 	//miss all
			// }
			// $currency_code=$this->utils->getCurrentCurrency()['currency_code'];
			// if($missingMain){
			// 	$insertrows[]=[
			// 		'playerId' => $playerId,
			// 		'type'=>self::TYPE_MAINWALLET,
			// 		'typeId'=>'0',
			// 		'currency'=>$currency_code,
			// 		'typeOfPlayer'=>'real',
			// 		'status'=>'0',
			// 		'totalBalanceAmount' => $bigWallet['main']['total_nofrozen'],
			// 	];
			// }
			// if(!empty($missingSubList)){
			// 	foreach ($missingSubList as $subWalletId=>$sub) {
			// 		$insertrows[]=[
			// 			'playerId' => $playerId,
			// 			'type'=>self::TYPE_SUBWALLET,
			// 			'typeId'=>$subWalletId,
			// 			'currency'=>$currency_code,
			// 			'typeOfPlayer'=>'real',
			// 			'status'=>'0',
			// 			'totalBalanceAmount' => $sub['total_nofrozen'],
			// 		];
			// 	}
			// }
			// if(!empty($insertrows)){
			// 	$this->utils->debug_log('insert wallet rows to playeraccount ', $insertrows);
			// 	$this->db->insert_batch('playeraccount', $insertrows);
			// }

			// if(!empty($updaterows)){
			// 	$this->db->update_batch('playeraccount', $updaterows, 'playerAccountId');
			// }

		} // EOF if(!empty($playerId) && !empty($bigWallet)){...

		return $success;
	} // EOF patchOldWallet()

	/**
	 * make sure big wallet and playeraccount are right
	 * @param  int $playerId
	 * @return bool
	 */
	public function refreshBigWalletOnDB($playerId, $db, $checkLock=true){
		$bigWallet = $this->runOneRowJsonContentById('big_wallet', $playerId, 'playerId', 'player', $db);
		$games = $this->external_system->getAllActiveSystemApiIDByType(SYSTEM_GAME_API, $db);
		$bigWallet = $this->copyBigWalletToEmpty($bigWallet, $playerId, $games);

		if($this->utils->getConfig('verbose_log')){
			$this->utils->debug_log($playerId . ' update big wallet', json_encode($bigWallet));
		}else{
			$this->utils->debug_log($playerId . ' update big wallet');
		}
		//save to DB
		$success = $this->writeBigWalletToDB($playerId, $bigWallet, $db, $checkLock);
		//patch to playeraccount
		$this->patchOldWallet($playerId, $bigWallet, $db);

		return $success;
	}

	public function updateSubWalletByPlayerId($playerId, $subWalletId, $balance) {
		//locked by outside
		$bigWallet = $this->getBigWalletByPlayerId($playerId);

		$changed=$this->refreshSubWalletByBigWallet($bigWallet, $subWalletId, $balance);
		if($changed){
			return $this->updateBigWalletByPlayerId($playerId, $bigWallet);
		}
		return true;
	}

	/**
	 * no need lock on this func
	 *
	 * @param $playerId
	 */
	public function readonlyBigWalletFromDB($playerId){
		$bigWallet = $this->runOneRowJsonContentById('big_wallet', $playerId, 'playerId', 'player');
		return $bigWallet;
	}

	/**
	 * only get nofrozen main wallet
	 * no need lock on this func
	 *
	 * @param $playerId
	 */
	public function readonlyMainWalletFromDB($playerId){
		$bigWallet=$this->readonlyBigWalletFromDB($playerId);
		if(array_key_exists('main', $bigWallet)){
			if(array_key_exists('total_nofrozen', $bigWallet['main'])){
				return doubleval($bigWallet['main']['total_nofrozen']);
			}
		}else{
			return 0;
		}
	}

	/**
	 * only get nofrozen sub wallet
	 * no need lock on this func
	 *
	 * @param $playerId
	 */
	public function readonlySubWalletFromDB($playerId, $game_id){
		$bigWallet=$this->readonlyBigWalletFromDB($playerId);
		if(array_key_exists('sub', $bigWallet) &&
		array_key_exists($game_id, $bigWallet['sub']) &&
		array_key_exists('total_nofrozen', $bigWallet['sub'][$game_id])){
			return doubleval($bigWallet['sub'][$game_id]['total_nofrozen']);
		}
		return 0;
	}


}