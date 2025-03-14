<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Payment library
 *
 * Payment library library
 *
 * @package		Payment library
 * @author		ASRII
 * @version		1.0.0
 */

class Payment_library {

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library(array('permissions', 'authentication'));
	}

    /**
     * get withdrawal status all permissions
     *
     * @return status lang = [0], view permissions = [1] and pass permissions = [2]
     */
    public function getWithdrawalAllStatusPermission($setting = NULL ,$customStageCount = 0){
		$statusPermission = [];
		#array(status name, view stage permission, view detail button permission)
		$statusPermission['request']  = [
			lang('st.pending'),
			$this->ci->permissions->checkPermissions('view_pending_stage'),
			$this->ci->permissions->checkPermissions('pass_decline_pending_stage')
		];

		if($this->ci->utils->isEnabledFeature("enable_withdrawal_pending_review") && $this->ci->permissions->checkPermissions('view_pending_review_stage')){
			$statusPermission['pending_review'] = [
				lang('st.pendingreview'),
				$this->ci->permissions->checkPermissions('view_pending_review_stage'),
				$this->ci->permissions->checkPermissions('pass_decline_pending_review_stage')
			];
		}

		if($this->ci->utils->getConfig('enable_pending_review_custom') && $setting['pendingCustom']['enabled']){
			$statusPermission['pending_review_custom']  = [
				lang('st.pendingreviewcustom'),
				$this->ci->permissions->checkPermissions('view_pending_custom_stage'),
				$this->ci->permissions->checkPermissions('execute_pass_decline_in_pending_custom_stage')
			];
		}

		if($customStageCount>0){
			for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++) {
				if($setting[$i]['enabled']) {
					$statusPermission['CS'.$i] = [lang($setting[$i]['name']),
					$this->ci->permissions->checkPermissions('view_withdraw_custom_stage_CS'.$i),
					$this->ci->permissions->checkPermissions('pass_decline_withdraw_custom_stage_CS'.$i)
					];
				}
			}
		}
		$statusPermission['payProc']  = [
			lang('st.processing'),
			$this->ci->permissions->checkPermissions('view_payment_processing_stage'),
			$this->ci->permissions->checkPermissions('pass_decline_payment_processing_stage')
		];
		$statusPermission['paid']     = [lang('st.paid'), true, ''];
		$statusPermission['declined'] = [lang('st.declined'), true, ''];
		$statusPermission['lock_api_unknown'] = [
			lang('st.lockedapirequest'),
			$this->ci->permissions->checkPermissions('view_locked_3rd_party_request'),
			$this->ci->permissions->checkPermissions('return_to_pending_locked_3rd_party_request')
		];

		return $statusPermission;
	}

	/**
	 * getVipGroupLevelDetails sumWithdrawAmount
	 *
	 * @return withdrawal fee amount
	 */
	public function chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $levelId, $amount){
		$this->ci->load->model(array('group_level','wallet_model'));

		$chargeFee = 0;
		$amount = !empty($amount) ? $amount : 0;
		$calculationFormula = '';
		$first_day_of_this_month = date('Y-m-d H:i:s', strtotime('midnight first day of this month'));
		$today_date = $this->ci->utils->getNowForMysql();
		$playerVipDetails = $this->ci->group_level->getVipGroupLevelDetails($levelId);
		$max_monthly_withdrawal = $playerVipDetails['max_monthly_withdrawal'];
		$accumulatedMonthlyWithdrawalAmount = $this->ci->wallet_model->sumWithdrawAmount($playerId, $first_day_of_this_month, $today_date, 0);
		$accumulatedAmount = $accumulatedMonthlyWithdrawalAmount + $amount;

		if (!empty($this->ci->utils->getConfig('calculate_withdrawal_fee_based_on_vip_level'))) {

			$upg_res = $this->ci->group_level->queryLastGradeRecordRowBy($playerId, $first_day_of_this_month, $today_date, null, 'request_time');
			$this->ci->utils->debug_log(__METHOD__,"upg_res",$upg_res);

			if (!empty($upg_res)) {
				$accumulatedMonthlyWithdrawalAmount = $this->ci->wallet_model->sumWithdrawAmount($playerId, $upg_res['request_time'], $today_date, 0);
				$accumulatedAmount = $accumulatedMonthlyWithdrawalAmount + $amount;
			}

			list($chargeFee,$calculationFormula) = $this->calculateWithdrawFeeAmountBasedonViplevel($amount,$accumulatedAmount,$max_monthly_withdrawal,$accumulatedMonthlyWithdrawalAmount, $levelId);

			$logs_data = [
				'playerVipDetails' => $playerVipDetails,
				'first_day_of_this_month' => $first_day_of_this_month,
				'today_date' => $today_date,
				'max_monthly_withdrawal' => $max_monthly_withdrawal,
				'accumulatedMonthlyWithdrawalAmount' => $accumulatedMonthlyWithdrawalAmount,
				'amount' => $amount,
				'chargeFee' => $chargeFee,
				'calculationFormula' => $calculationFormula
			];

			$this->ci->utils->debug_log(__METHOD__,'logs_data based on vip', $logs_data);
			return array($chargeFee ? $chargeFee : 0,$calculationFormula);
		}elseif(!empty($this->ci->utils->getConfig('withdrawal_fee_levels')) && !empty($this->ci->utils->getConfig('withdrawal_fee_rates'))){
			list($chargeFee,$calculationFormula) = $this->calculateWithdrawFeeAmount($amount,$accumulatedAmount,$max_monthly_withdrawal,$accumulatedMonthlyWithdrawalAmount);

			$logs_data = [
				'playerVipDetails' => $playerVipDetails,
				'first_day_of_this_month' => $first_day_of_this_month,
				'today_date' => $today_date,
				'max_monthly_withdrawal' => $max_monthly_withdrawal,
				'accumulatedMonthlyWithdrawalAmount' => $accumulatedMonthlyWithdrawalAmount,
				'amount' => $amount,
				'chargeFee' => $chargeFee,
				'calculationFormula' => $calculationFormula
			];

			$this->ci->utils->debug_log(__METHOD__,'logs_data', $logs_data);

			return array($chargeFee ? $chargeFee : 0,$calculationFormula);
		}

		return array($chargeFee, $calculationFormula);
	}

	public function calculateWithdrawFeeAmount($amount, $accumulatedAmount = 0, $maxMonthlyWithdrawal = 0, $accumulatedMonthlyWithdrawalAmount){
		if(!is_numeric($amount) || !is_numeric($accumulatedAmount) || !is_numeric($maxMonthlyWithdrawal)){
			return false;
		}

		$chargeFee = 0;
		$countChargeFee = 0;
		$calculationFormula = 'Cumulative withdrawals in the current month : '. $accumulatedAmount .' ; chargeFee = ';
		$currentLev = 0;
		$firstFeeRates = 0;

		if($accumulatedAmount <= $maxMonthlyWithdrawal){
			$this->ci->utils->debug_log(__METHOD__,"accumulatedAmount <=",$accumulatedAmount ," monthly amount",$maxMonthlyWithdrawal);
			return array($countChargeFee,$calculationFormula);
		}

		$withdrawalFeeLevels = $this->ci->utils->getConfig('withdrawal_fee_levels');#$config['withdrawal_fee_levels'] =array(1000, 2000, 3000, PHP_INT_MAX);
		$withdrawalFeeRates = $this->ci->utils->getConfig('withdrawal_fee_rates');#$config['withdrawal_fee_rates'] = array(0.02, 0.03, 0.04, 0.05);

		if($maxMonthlyWithdrawal > $withdrawalFeeLevels[0]){
			return array($countChargeFee,$calculationFormula);
		}

		array_unshift($withdrawalFeeLevels, $maxMonthlyWithdrawal);
		array_unshift($withdrawalFeeRates, $firstFeeRates);

		foreach($withdrawalFeeLevels as $k => $levelFeeAmount){
			if($accumulatedAmount > $levelFeeAmount  && $accumulatedAmount <= $withdrawalFeeLevels[$k+1]){
				$currentLev = $k;
				break;
			}
		}

		$totalAmt = $accumulatedAmount;
		$amt = $amount;
		$this->ci->utils->debug_log(__METHOD__,"withdrawalFeeLevels,withdrawalFeeRates,currentLev",$withdrawalFeeLevels,$withdrawalFeeRates,$currentLev);

		for ($i=$currentLev; $i >= 0 ; $i--) {

			$chargeAmt= $totalAmt - $withdrawalFeeLevels[$i];

			if($amt <= $chargeAmt){
				$chargeAmt = $amt;
			}

			$chargeFee = floatval($chargeAmt * $withdrawalFeeRates[$i+1]);
			$totalAmt = $totalAmt - $chargeAmt;
			$amt = $amt - $chargeAmt;

			$countChargeFee += $chargeFee;
			$calculationFormula .= '('. $chargeAmt . ' * ' . $withdrawalFeeRates[$i+1] .') ='. $chargeFee .'+';

			if($amt == 0){
				break;
			}
		}

		$calculationFormula = rtrim($calculationFormula,'+');
		$countChargeFee = round($countChargeFee, 2);
		$this->ci->utils->debug_log(__METHOD__,"calculateWithdrawFeeAmount",$countChargeFee,$calculationFormula);

		return array($countChargeFee,$calculationFormula);
	}

	public function calculateWithdrawFeeAmountBasedonViplevel($amount, $accumulatedAmount = 0, $maxMonthlyWithdrawal = 0, $accumulatedMonthlyWithdrawalAmount, $levelId){

		$chargeFee = 0;
		$countChargeFee = 0;
		$calculationFormula = 'Cumulative withdrawals in the current month : '. $accumulatedAmount .' ; chargeFee : ';

		if($accumulatedAmount <= $maxMonthlyWithdrawal){
			$this->ci->utils->debug_log(__METHOD__,"accumulatedAmount <=",$accumulatedAmount ," monthly amount",$maxMonthlyWithdrawal);
			return array($countChargeFee,$calculationFormula);
		}

		$condition = $this->ci->utils->getConfig('calculate_withdrawal_fee_based_on_vip_level');

		$logs_data = [
			'condition' => $condition,
			'nowinputamount' => $amount,
			'allamount' => $accumulatedAmount,
			'beforeamount' => $accumulatedMonthlyWithdrawalAmount,
			'maxMonthlyWithdrawal' => $maxMonthlyWithdrawal,
			'levelId' => $levelId
		];

		$this->ci->utils->debug_log(__METHOD__,"logs_data",$logs_data);

		if (!empty($condition)) {
			foreach ($condition as $vip_level_id => $rates) {
				if ($levelId == $vip_level_id) {
					if ($accumulatedAmount > $maxMonthlyWithdrawal) {

						if ($accumulatedMonthlyWithdrawalAmount > $maxMonthlyWithdrawal) {
							$chargeAmt = $amount;
						}else{
							$chargeAmt = $accumulatedAmount - $maxMonthlyWithdrawal;
						}

						$rates = $rates/100;
						$chargeFee = floatval($chargeAmt * $rates);
						$note = '('. $chargeAmt . ' * ' . $rates .') ='. $chargeFee;
						$calculationFormula .= $note;
						$countChargeFee = round($chargeFee, 2);
					}else{
						$calculationFormula .= '('.$chargeFee.')';
					}
				}
			}
		}
		$this->ci->utils->debug_log(__METHOD__,"result",$countChargeFee,$calculationFormula);

		return array($countChargeFee,$calculationFormula);
	}

	public function calculationWithdrawalBankFee($playerId, $bankCode, $amount){
		$chargeFee = 0;
		$countChargeFee = 0;
		$calculationFormula = 'Withdrawal bank('. $bankCode .') chargeFee : ';
		$condition = $this->ci->utils->getConfig('enable_withdrawl_bank_fee');

		$logs_data = [
			'playerId' => $playerId,
			'bankCode' => $bankCode,
			'amount' => $amount,
			'condition' => $condition
		];

		$this->ci->utils->debug_log(__METHOD__,"logs_data",$logs_data);

		foreach ($condition as $bank => $fee) {
			if (strtoupper($bank) == strtoupper($bankCode)) {
				switch ($bank) {
					case 'USDT-ERC':
					//fixed
						$chargeFee = floatval($fee);
						$note = '( fixed amount '. $chargeFee .' )';
						break;
					default:
					//percentage
						$rates = $fee/100;
						$chargeFee = floatval($amount * $rates);
						$note = '('. $amount . ' * ' . $rates .') ='. $chargeFee;
						break;
				}
				$calculationFormula .= $note;
				$countChargeFee = round($chargeFee, 2);
				continue;
			}
		}

		$this->ci->utils->debug_log(__METHOD__, "result", $countChargeFee, $calculationFormula);

		return array($countChargeFee, $calculationFormula);
	}

	/**
	 * verify_dw_achieve_threshold_amount by playerid and type
	 * @param int $playerId
	 * @param int $transactions_type
	 */
	public function verify_dw_achieve_threshold_amount($playerId, $transactions_type){
		$this->ci->load->model(['player_model', 'player_dw_achieve_threshold', 'transactions']);

		if (!empty($playerId)) {
			$suc = false;
			$is_updated = false;
			$playerAchieveThresholdAmount = 0;
			$sumWDAmountFromCreatedOn = 0;
			$getPlayerUsername = $this->ci->player_model->getPlayerUsername($playerId);
			$getPlayerRegisterDate = $this->ci->player_model->getPlayerRegisterDate($playerId);
			$getPlayerAchieveThresholdDetails = $this->ci->player_dw_achieve_threshold->getPlayerAchieveThresholdDetails($playerId);

			if (!empty($getPlayerAchieveThresholdDetails)) {
				$before_deposit_amount = $getPlayerAchieveThresholdDetails[0]->before_deposit_achieve_threshold;
				$before_withdrawal_amount = $getPlayerAchieveThresholdDetails[0]->before_withdrawal_achieve_threshold;
				$after_deposit_amount = $getPlayerAchieveThresholdDetails[0]->after_deposit_achieve_threshold;
				$after_withdrawal_amount = $getPlayerAchieveThresholdDetails[0]->after_withdrawal_achieve_threshold;

				$this->ci->utils->debug_log('---------verify_dw_achieve_threshold_amount amount detail', $before_deposit_amount, $before_withdrawal_amount, $after_deposit_amount, $after_withdrawal_amount);

				if ($transactions_type == Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT) {

					if ($before_deposit_amount !== $after_deposit_amount) {
						$is_updated = true;
					}

					$playerAchieveThresholdAmount = $after_deposit_amount;
					$sumWDAmountFromCreatedOn = $this->ci->transactions->sumDepositAmount($playerId, $getPlayerRegisterDate, $this->ci->utils->getNowForMysql(), 0);
				}elseif ($transactions_type == Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_WITHDRAWAL) {

					if ($before_withdrawal_amount !== $after_withdrawal_amount) {
						$is_updated = true;
					}

					$playerAchieveThresholdAmount = $after_withdrawal_amount;
					$sumWDAmountFromCreatedOn = $this->ci->transactions->sumWithdrawAmount($playerId, $getPlayerRegisterDate, $this->ci->utils->getNowForMysql(), 0);
				}
			}

			if (!empty($playerAchieveThresholdAmount)) {
				if ($sumWDAmountFromCreatedOn >= $playerAchieveThresholdAmount) {
					$data = array(
						'player_id'            	 => $playerId,
						'username'				 => $getPlayerUsername['username'],
						'create_at'              => date('Y-m-d H:i:s'),
						'threshold_amount'		 => $playerAchieveThresholdAmount,
						'achieve_amount'		 => $sumWDAmountFromCreatedOn,
						'achieve_threshold_type' => $transactions_type,
		            );
					$suc = $this->ci->player_dw_achieve_threshold->setAchieveThresholdHistory($data, $transactions_type, $is_updated);
				}
			}
			$this->ci->utils->debug_log('---------insert player_dw_achieve_threshold_history', $suc, $playerAchieveThresholdAmount, $getPlayerUsername, $getPlayerRegisterDate, $sumWDAmountFromCreatedOn, $getPlayerAchieveThresholdDetails, $is_updated);
		}
	}

	public function paymentHttpCall($url, $params, $post = true, $postJson = true, $headers = null, $config = null){

		$this->ci->utils->debug_log('------------paymentHttpCall http call params', $url, $params, $post, $postJson, $headers, $config);

		$query_string = '';
		foreach ($params as $key => $value) {
			$query_string .= $key . '=' . $value . '&';
		}
		$query_string = rtrim($query_string,'&');

		if(strpos($url, '?')!==FALSE){
			//found ?
			$url = empty($query_string) ? rtrim($url,'&').'&'.http_build_query($params) : rtrim($url,'&').'&'.$query_string;
			//$url=rtrim($url,'&').'&'.http_build_query($params);
		}else{
			//no ?
			$url = empty($query_string) ? $url.'?'.http_build_query($params) : $url.'?'.$query_string;
			// $url=$url.'?'.http_build_query($params);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, $post);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (isset($config['call_socks5_proxy']) && !empty($config['call_socks5_proxy'])) {
			$this->ci->utils->debug_log('------------paymentHttpCall http call with proxy', $config['call_socks5_proxy']);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
			curl_setopt($ch, CURLOPT_PROXY, $config['call_socks5_proxy']);
			if (!empty($config['call_socks5_proxy_login']) && !empty($config['call_socks5_proxy_password'])) {
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $config['call_socks5_proxy_login'] . ':' . $options['call_socks5_proxy_password']);
			}
		}

		if(!empty($headers)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

		if ($post) {
			if($postJson){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->ci->utils->encodeJson($params) );
			}else{
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );
			}
		}

		curl_setopt($ch, CURLOPT_TIMEOUT, $this->ci->utils->getConfig('default_http_timeout'));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->ci->utils->getConfig('default_connect_timeout'));

		$response    = curl_exec($ch);
		$errCode     = curl_errno($ch);
		$error       = curl_error($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($response, 0, $header_size);
		$content     = substr($response, $header_size);
		$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$last_url    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$statusText  = $errCode . ':' . $error;
		curl_close($ch);

		$this->ci->utils->debug_log('paymentHttpCall', 'url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode, 'content', $content, 'last_url', $last_url, 'headers', $headers, 'config', $config);

		return $content;
	}

    public function getCachekeyWithStatusOfSaleOrder($status){
        $this->ci->load->model(['sale_order']);
        $cachekey = '';
        switch($status){
            case Sale_order::STATUS_SETTLED:
                // UPADC = update_players_approved_deposit_count
                $cachekey = 'playerIdListInCronOfUPADC';
            break;
            case Sale_order::STATUS_DECLINED:
                // UPDDC = update_players_declined_deposit_count
                $cachekey = 'playerIdListInCronOfUPDDC';
            break;
        }
        return $cachekey;
    }
    public function removePlayerId2refreshPlayersApprovedDepositCount($playerId, $status = null){
        $this->ci->load->model(['sale_order']);
        if(is_null($status)){
            $status = Sale_order::STATUS_SETTLED;
        }
        return $this->removePlayerId2refreshPlayersDepositCountWithStatus($playerId, $status);
    }
    public function removePlayerId2refreshPlayersDeclinedDepositCount($playerId, $status = null){
        $this->ci->load->model(['sale_order']);
        if(is_null($status)){
            $status = Sale_order::STATUS_DECLINED;
        }
        return $this->removePlayerId2refreshPlayersDepositCountWithStatus($playerId, $status);
    }
    public function removePlayerId2refreshPlayersDepositCountWithStatus($playerId, $status = null){
        $playerIdList = [];
        $cachekey = $this->getCachekeyWithStatusOfSaleOrder($status);
        $playerIdList = $this->cronGetData2OperatorSettings($cachekey);
        if(empty($playerIdList)){
            $playerIdList = [];
        }else{
            if (($key = array_search($playerId, $playerIdList)) !== false) {
                unset($playerIdList[$key]);
                $playerIdList = array_values($playerIdList);
            }
        }
        $this->cronSetData2OperatorSettings([$cachekey => $playerIdList]);
        return $playerIdList;
    }
    public function addPlayerId2refreshPlayersApprovedDepositCount($playerId, $status = null){
        $this->ci->load->model(['sale_order']);
        if(is_null($status)){
            $status = Sale_order::STATUS_SETTLED;
        }
        return $this->addPlayerId2refreshPlayersDepositCountWithStatus($playerId, $status);
    }
    public function addPlayerId2refreshPlayersDeclinedDepositCount($playerId, $status = null){
        $this->ci->load->model(['sale_order']);
        if(is_null($status)){
            $status = Sale_order::STATUS_DECLINED;
        }
        return $this->addPlayerId2refreshPlayersDepositCountWithStatus($playerId, $status);
    }
    public function addPlayerId2refreshPlayersDepositCountWithStatus($playerId, $status = ''){
        $playerIdList = [];
        $cachekey = $this->getCachekeyWithStatusOfSaleOrder($status);
        $playerIdList = $this->cronGetData2OperatorSettings($cachekey);
        if(empty($playerIdList)){
            $playerIdList = [];
        }
        if(!in_array($playerId, $playerIdList)){
            array_push($playerIdList, $playerId);
			$playerIdList = array_unique($playerIdList);
            $this->cronSetData2OperatorSettings([$cachekey => $playerIdList]);
        }else{
            // ignore add in the case, playerId has already exists in $playerIdList.
        }

        return $playerIdList;
    }

    public function validateOrderFormat($orderId){
        if(empty($orderId)){
            return false;
        }
        if (!preg_match('/^[DW]\d+$/', $orderId)) {
            return false;
        }
        return true;
    }

    /**
	 * Set data into operator_settings for the refresh counts of deposit in player
	 *
	 * @param array $data the key-value for operator_settings
	 * @return void
	 */
	public function cronSetData2OperatorSettings($data, $note = 'Cronjob for refresh counts of deposit in player'){
        $this->ci->load->model(['operatorglobalsettings']);
		foreach ($data as $key => $value) {
			if($this->ci->operatorglobalsettings->existsSetting($key)){
				$this->ci->operatorglobalsettings->putSettingJson($key, $value, 'value');
				// $this->ci->operatorglobalsettings->putSetting($key, $note, 'note');
			}else{
				$this->ci->operatorglobalsettings->insertSettingJson($key, $value);
				$this->ci->operatorglobalsettings->putSetting($key, $note, 'note');
			}
		}
	}// EOF cronSetData2OperatorSettings
    public function cronGetData2OperatorSettings($key){
        $this->ci->load->model(['operatorglobalsettings']);
        return $this->ci->operatorglobalsettings->getSettingJson($key);
        // return $this->ci->operatorglobalsettings->getOperatorGlobalSetting($key);
    } // EOF cronGetData2OperatorSettings

    /**
     * Wrapp main script with lockResourceBy()/releaseResourceBy() and startTrans()/endTransWithSucc()/rollbackTrans() methods.
     *
     * @param integer $player_id
     * @param callable $callback The main script. (bool)$callback( &$returnOfMain )
     * @param boolean $doDefinitelyLock
     * @return boolean The return of $callback();
     */
    public function _lockAndTransForPlayerBalance($player_id = null, callable $callback, $doDefinitelyLock = true){
        $this->ci->load->model(['transactions']);
        $return = null;
        $lockedKey = NULL; // just collect the lockedKey string
        $lock_it = false;
        if(!empty($player_id)){
            $lock_it = $this->ci->utils->lockResourceBy($player_id, Utils::LOCK_ACTION_BALANCE, $lockedKey);
        }else{
            if(!$doDefinitelyLock){
                $lock_it = true; // ignore lock resource
            }
        }
        if ($lock_it) {
            try {
                $this->ci->transactions->startTrans();
                // main script
                // $result = $this->updateSaleOrderResult($id, $reason, $show_reason_to_player, self::STATUS_BROWSER_CALLBACK);
                $return = $callback();
                if($return){
                    $this->ci->transactions->endTransWithSucc();
                }else{
                    $this->ci->transactions->rollbackTrans();
                }
            } finally {
                if(!empty($lockedKey)){
                    $this->ci->utils->releaseResourceBy($player_id, Utils::LOCK_ACTION_BALANCE, $lockedKey);
                    $this->ci->utils->debug_log('release browserCallbackSaleOrder lock', 'player_id', $player_id);
                }
            }
        }else{
            $this->ci->utils->error_log('lock player balance failed', $player_id, 'saleOrderId:', $id);
        }
        return $return;

        //_dbtransOnlyWithDeadlockRetry
        //_isDeadlockException
    } // EOF _lockAndTransForPlayerBalance

}
