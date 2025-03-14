<?php
trait lock_app_module {

	protected function createLockKey($arr) {
		return $this->utils->createLockKey($arr);
	}

	protected function lockActionById($anyId, $action) {
		return $this->utils->lockActionById($anyId, $action);
	}

	protected function releaseActionById($anyId, $action) {
		return $this->utils->releaseActionById($anyId, $action);
	}

	public function startTrans() {
		if (!property_exists($this, 'player_model')) {
			$this->load->model('player_model');
		}

		return $this->player_model->startTrans();
	}

	public function endTransWithSucc() {
		if (!property_exists($this, 'player_model')) {
			$this->load->model('player_model');
		}

		return $this->player_model->endTransWithSucc();
	}

	public function rollbackTrans() {
		if (!property_exists($this, 'player_model')) {
			$this->load->model('player_model');
		}

		return $this->player_model->rollbackTrans();
	}
	public function lockAndTransForRegistration($anyid, $callbakcable, $add_prefix=true, &$isLockFailed=false, $doExceptionPropagation=false) {
		return $this->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_REGISTRATION, $anyid, $callbakcable, $add_prefix, $isLockFailed, $doExceptionPropagation);
	}

	public function lockAndTransForAgencyCredit($agentId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_AGENCY_BALANCE, $agentId, $callbakcable);
	}

	public function lockAndTransForAgencyBalance($agentId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_AGENCY_BALANCE, $agentId, $callbakcable);
	}

	public function lockAndTransForPlayerBalance($playerId, $callbakcable, $add_prefix=true, &$isLockFailed=false, $doExceptionPropagation=false) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_BALANCE, $playerId, $callbakcable, $add_prefix, $isLockFailed, $doExceptionPropagation);
	}

	public function lockAndTransForSeamlessWalletTransactionProcess($playerId, $callbakcable, $add_prefix=true, &$isLockFailed=false) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_SEAMLESS_WALLET_TRANSACTION_PROCESS, $playerId, $callbakcable, $add_prefix, $isLockFailed);
	}

	public function lockAndTransForGameSyncing($uniqueId, $callbakcable, $add_prefix=true, &$isLockFailed=false) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_GAME_LIST_SYNCING, $uniqueId, $callbakcable, $add_prefix, $isLockFailed);
	}

	public function lockAndTransForPlayerPromo($playerPromoId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_PLAYER_PROMO, $playerPromoId, $callbakcable);
	}

	public function lockAndTransForPlayerQuest($playerId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_PLAYER_QUEST, $playerId, $callbakcable);
	}

	public function lockAndTransForAffiliateBalance($affId, $callbakcable, $add_prefix=true, &$isLockFailed=false, $doExceptionPropagation=false) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_AFF_BALANCE, $affId, $callbakcable, $add_prefix, $isLockFailed, $doExceptionPropagation);
	}

	public function lockAndTransForDepositLock($salesOrderId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_DEPOSIT_LOCK, $salesOrderId, $callbakcable);
	}

    public function lockAndTransForWithdrawLock($walletAccountId, $callbakcable) {
        return $this->lockAndTrans(Utils::LOCK_ACTION_WITHDRAW_LOCK, $walletAccountId, $callbakcable);
    }

	public function lockAndTransForRedemptionCode($redemptionCodeCateId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_REDEMPTION_CODE, $redemptionCodeCateId, $callbakcable);
	}

	public function lockAndTransForStaticRedemptionCode($redemptionCodeCateId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_STATIC_REDEMPTION_CODE, $redemptionCodeCateId, $callbakcable);
	}

	public function lockAndTransForUpdateTournamentPlayerScore($event_id, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_UPDATE_TOURNAMENT_PLAYER_SCORE, $event_id, $callbakcable);
	}

	public function lockAndTransForApplyRoulette($playerId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_APPLY_ROULETTE, $playerId, $callbakcable);
	}

	public function lockAndGenerateRoulette($playerId, $callbakcable) {
		return $this->lockAndTrans(Utils::LOCK_ACTION_GENERATE_ROULETTE, $playerId, $callbakcable);
	}

	public function lockResourceBy($anyId, $action, &$lockedKey) {
		return $this->utils->lockResourceBy($anyId, $action, $lockedKey);
	}

	public function releaseResourceBy($anyId, $action, &$lockedKey) {
		return $this->utils->releaseResourceBy($anyId, $action, $lockedKey);
	}

	public function lockPlayerBalanceResource($playerId, &$lockedKey){
		return $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	public function releasePlayerBalanceResource($playerId, &$lockedKey){
		return $this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
	}

	/**
	 * lockAndTrans
	 *
	 * @param string $lock_type
	 * @param string $anyid
	 * @param callable $callbakcable
	 * @param boolean $add_prefix
	 * @param boolean $isLockFailed
	 * @param boolean $doExceptionPropagation
	 * @return mixed
	 */
	public function lockAndTrans($lock_type, $anyid, callable $callbakcable, $add_prefix=true, &$isLockFailed=false, $doExceptionPropagation=false) {
		$success = false;
		$lockedKey = null;
		$lock_it = $this->utils->lockResourceBy($anyid, $lock_type, $lockedKey, $add_prefix);
		try {
			if ($lock_it) {
				$this->db->resetTransStatus();
				$this->startTrans();
				$success=false;
				try{
					$success = $callbakcable();
				}catch(Exception $e){

                    if( $this->isDeadlockException($e) ){
                        $last_query = '';
                        if( !empty($this->db) ){
                            $last_query = $this->db->last_query(); /// get the last_query first
                        }

                        $fullProcesslist = $this->utils->getFullProcesslist();
                        $this->utils->debug_log('callbakcable() Exception, Processlist:', $fullProcesslist);
                    }
					$this->utils->error_log('got exception in lockAndTrans', $e);
					$success=false;
					if($doExceptionPropagation){
						throw $e; // Catch Exception at outter try block, https://stackoverflow.com/a/9041245
					}
				}

				if(!$success){

                    $last_query = '';
                    if( !empty($this->db) ){
                        $last_query = $this->db->last_query(); /// get the last_query first
                    }

					//rollback
					$this->rollbackTrans();
					$this->utils->error_log('rollback trans because failed');

					$fullProcesslist = $this->utils->getFullProcesslist();
					$this->utils->debug_log('trans failed by callbakcable, fullProcesslist:', $fullProcesslist, 'last_query:', $last_query);
				} else {
					$success = $this->endTransWithSucc();
				}

			} else {
				$isLockFailed=true;
				$retryTime=$this->utils->getConfig('lock_retry_delay')*20;
				$timeout = $this->utils->getConfig('app_lock_timeout') * 1000;
				$this->utils->error_log('lock failed by retryTime: '.$retryTime, 'timeout: '.$timeout, $lock_type, $anyid);
			}
		} finally {
			if(!empty($lockedKey)){
				$rlt = $this->utils->releaseResourceBy($anyid, $lock_type, $lockedKey);
				if(!$rlt){
					$this->utils->error_log('release failed', $lock_type, $anyid, $lockedKey);
				}
			} else {
				$this->utils->info_log('cannot release empty key', $lock_type, $anyid, $lockedKey);
			}
		}
		return $success;
	}

	public function dbtransOnly($callbakcable) {
		$success = false;
		$this->db->resetTransStatus();
		$this->startTrans();
		try {

			$success = $callbakcable();

			if(!$success){
				//rollback
				$this->rollbackTrans();
				$this->utils->error_log('rollback trans because failed');
			}else{
				$success = $this->endTransWithSucc();
			}

		}catch(Exception $e){
			$this->utils->error_log('throw exception on trans', $e);
			$success=false;
			//rollback
			$this->rollbackTrans();
		}
		return $success;
	}

	public function lockAndTransForPlayerBalanceAndAgencyCredit($playerId, $agentId, $callbakcable) {

		$lock_array=[
			['lock_type'=>Utils::LOCK_ACTION_BALANCE, 'anyid'=>$playerId],
			['lock_type'=>Utils::LOCK_ACTION_AGENCY_BALANCE, 'anyid'=>$agentId],
		];

		return $this->multipleLockAndTrans($lock_array, $callbakcable);
	}

	public function multipleLockAndTrans($lock_array, $callbakcable) {
		$success = false;
		if(empty($lock_array) || empty($callbakcable)){
			return $success;
		}

		$locked_all=false;
		$lock_it=[];
		foreach ($lock_array as &$lock_info) {
			$key = $this->utils->createLockKey([$lock_info['anyid'], $lock_info['lock_type']]);
			$lock_it[$key] = $this->utils->lockResourceBy($lock_info['anyid'], $lock_info['lock_type'], $lockedKey);

			$lock_info['lockedKey'] = $lockedKey;
			if(!$lock_it[$key]){
				$locked_all = false;
			} else {
				$locked_all = true;
			}
		}

		$this->utils->debug_log('try lock', $lock_array, 'result', $lock_it);

		try {
			if ($locked_all) {
				$success = $callbakcable();
			} else {
				$this->utils->error_log('lock failed', $lock_array);
			}
		} finally {
			for($i=count($lock_array)-1;$i>=0;$i--){
				$lock_info=$lock_array[$i];
				$lockedKey=isset($lock_info['lockedKey']) ? $lock_info['lockedKey'] : null;
				if(!empty($lockedKey)){
					$rlt = $this->utils->releaseResourceBy($lock_info['anyid'], $lock_info['lock_type'], $lockedKey);
					if(!$rlt){
						$this->utils->error_log('release failed', $lock_info);
					}
				}
			}
		}
		return $success;
	}

	public function localLockResourceBy($anyId, $action, &$lockedKey) {
		return $this->utils->localLockResourceBy($anyId, $action, $lockedKey);
	}

	public function localReleaseResourceBy($anyId, $action, &$lockedKey) {
		return $this->utils->localReleaseResourceBy($anyId, $action, $lockedKey);
	}

	public function localLockOnlyWithResult($lock_type, $anyid, callable $callbakcable) {
		$success = false;
		$error=null;
		$lockedKey=null;
		$lock_it = $this->utils->localLockResourceBy($anyid, $lock_type, $lockedKey);
		try {
			if ($lock_it) {
				$success = $callbakcable($error);
			} else {
				$timeout = $this->utils->getConfig('app_lock_timeout') * 1000;
				$this->utils->error_log('lock failed by timeout :'.$timeout, $lock_type, $anyid);
				$error='Lock failed';
			}
		} finally {
			if(!empty($lockedKey)){
				$rlt = $this->utils->localReleaseResourceBy($anyid, $lock_type, $lockedKey);
				if(!$rlt){
					$this->utils->error_log('release failed', $lock_type, $anyid, $lockedKey);
				}
			}else{
				$this->utils->info_log('cannot release empty key', $lock_type, $anyid, $lockedKey);
			}
		}

		$result['success']=$success;
		$result['error']=$error;

		return $result;
	}

	public function isResourceInsideLock($anyId, $action){
		return $this->utils->isResourceInsideLock($anyId, $action);
	}

	/**
	 * dbtransOnlyWithDeadlockRetry
	 * retry when find deadlock exception
	 * @param  callable  $callbakcable
	 * @param  integer $retryTimes
	 * @param  integer $sleepSecond
	 * @return boolean
	 */
	public function dbtransOnlyWithDeadlockRetry($callbakcable, $retryTimes=3, $sleepSecond=2) {
		$success = false;
		$retry=false;
		$retryCount=0;
		do{
			$retry=false;
			$this->db->resetTransStatus();
			//run once
			$this->startTrans();
			$this->utils->debug_log('startTrans', $retry, $retryCount, 'trans_strict', $this->db->trans_strict);
			try {
				$success = $callbakcable();

				if(!$success){
					//rollback
					$this->rollbackTrans();
					$this->utils->error_log('rollback trans because failed');
				}else{
					$success = $this->endTransWithSucc();
					$this->utils->debug_log('try commit', $success);
				}
			}catch(Exception $e){
				$this->utils->error_log('throw exception on trans', $e);
				$success=false;
				//rollback
				$this->rollbackTrans();
				//deadlock and retry
				if($this->isDeadlockException($e) && $retryCount<$retryTimes){
					$this->utils->debug_log('found deadlock, retry, sleep', $sleepSecond);
					$retryCount++;
					$retry=true;
					if($sleepSecond>0){
						sleep($sleepSecond);
					}
					$this->utils->debug_log('close and reconnect db');
					//close db and reconnect
			        if(!$this->db->closeAndConnect()){
			        	//if can't reconnect, stop it
			        	$retry=false;
			        	$success=false;
			        	$this->utils->error_log('cannot retry, because connect db is failed');
			        }
				}else if($retryCount<$retryTimes){
					$this->utils->info_log('reached max retry', $retryCount, $retryTimes);
				}
			}
			//if retry
		}while(!$success && $retry);
		return $success;
	}

	/**
	 * isDeadlockException
	 * check if code is deadlock 1213
	 * @param  Exception  $e
	 * @return boolean
	 */
	public function isDeadlockException($e){
		$result=false;
		if(!empty($e) && $e instanceof Exception){
			//1213 is deadlock code of mysql, 1205: Lock wait timeout exceeded
			$result= $e->getCode()==1213 || $e->getCode()==1205;
			$this->utils->debug_log('it is exception with code', $e->getCode());
		}

		return $result;
	}

}

///END OF FILE/////////
