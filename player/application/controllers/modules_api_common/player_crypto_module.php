<?php

/**
 * uri: /crypto
 *
 * @property int $player_id
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property Crypto_currency_lib $crypto_currency_lib
 */
trait player_crypto_module{

	public function crypto($action, $additional=null)
	{
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib']);
		$this->load->library(['crypto_currency_lib']);
		$this->load->model(['playerapi_model', 'player_model', 'player_promo', 'roulette_api_record', 'player_crypto_wallet_info']);

        if(FALSE === $this->crypto_currency_lib->init()){
            throw new \APIException('', Playerapi::CODE_CRYPTO_CURRENCY_NOT_ENABLED);
        }

		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'getAllAddress':
				if($request_method == 'GET') {
					return $this->getAllAddress($this->player_id);
				}
				break;
			case 'getCryptoSetting':
				if($request_method == 'GET') {
					return $this->getCryptoSetting($this->player_id);
				}
				break;
			case 'postCryptoWithdrawal':
				if($request_method == 'POST') {
					return $this->postCryptoWithdrawal();
				}
				break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

    public function getAllAddress($player_id)
    {
        try {
            $cryptoSetting = $this->crypto_currency_lib->getEnabledCoinsAndChains();
            $allAddress = [];
            // first. get address from db
            if(is_array($cryptoSetting['coins']) && !empty($cryptoSetting['coins'])){
                foreach ($cryptoSetting['coins'] as $coin) {
                    $targetDBKey = $this->crypto_currency_lib->getTargetDataBaseByCoin($coin);
                    if(!empty($targetDBKey)){                
                        $data = $this->playerapi_lib->switchCurrencyForAction($targetDBKey, function() use ($coin){
                            return $this->player_crypto_wallet_info->getPlayerCryptoAddressForAPI($this->player_id, $coin);
                        });
                        if(!empty($data)){
                            array_push($allAddress, $data);
                        }
                    }
                }
            }

            // second. get address from api
            if(empty($allAddress)){
                $allAddress = $this->crypto_currency_lib->getAllAddress($this->player_id);
                if(!empty($allAddress)){
                    // insert crypto info to target db
                    $this->crypto_currency_lib->generatePlayerCryptoWalletWithAPI($this->player_id, $allAddress);
                }
            }
            $result['code'] = Playerapi::CODE_OK;
            $result['data'] = $allAddress;
            return $this->returnSuccessWithResult($result);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function getCryptoSetting($player_id)
    {
        try {
        	$validateFields = [
				['name' => 'coinId', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'chainName', 'type' => 'string', 'required' => true, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			$this->comapi_log(__METHOD__, 'getCryptoSetting check validation state', ['requestBody' => $requestBody, 'isValidateBasicPassed' => $isValidateBasicPassed]);

			if(!$isValidateBasicPassed['validate_flag']){
				throw new APIException($isValidateBasicPassed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

            $validateCoinAndChain = $this->validateCoinAndChain($requestBody['coinId'], $requestBody['chainName']);
            if(!$validateCoinAndChain['valid']){
                throw new APIException($validateCoinAndChain['validate_msg'], self::CODE_CRYPTO_CURRENCY_NOT_ENABLED);
            }

            $targetDBKey = $this->crypto_currency_lib->getTargetDataBaseByCoin($requestBody['coinId']);
            if(empty($targetDBKey)){
                throw new APIException(lang('coinId does not set target DB'), self::CODE_CRYPTO_CURRENCY_NOT_ENABLED);
            }

            $result = $this->playerapi_lib->switchCurrencyForAction($targetDBKey, function() use ($player_id){
                return $this->crypto_currency_lib->getCryptoSetting($player_id);
            });

			if(!empty($result)){
				$output['code'] = self::CODE_OK;
				$output['data'] = $result;
				return $this->returnSuccessWithResult($output);
			}
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function postCryptoWithdrawal()
    {
    	try {
    		$validateFields = [
				['name' => 'coinId', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'chainName', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'address', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'amount', 'type' => 'positive_double', 'required' => true, 'length' => 0],
				['name' => 'withdrawPassword', 'type' => 'string', 'required' => false, 'length' => 0],
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			$this->comapi_log(__METHOD__, 'postCryptoWithdrawal check validation state', ['requestBody' => $requestBody, 'isValidateBasicPassed' => $isValidateBasicPassed]);

			if($isValidateBasicPassed['validate_flag']){
				$validateCoinAndChain = $this->validateCoinAndChain($requestBody['coinId'], $requestBody['chainName']);
				$targetDBKey = $this->crypto_currency_lib->getTargetDataBaseByCoin($requestBody['coinId']);
				if($validateCoinAndChain['valid']){
					if(!empty($targetDBKey)){
						$result = $this->playerapi_lib->switchCurrencyForAction($targetDBKey, function() use ($requestBody)
						{
							$withdrawPassword = !empty($requestBody['withdrawPassword']) ? $requestBody['withdrawPassword'] : null;
							return $this->handleCryptoWithdrawalrequest($requestBody['coinId'], $requestBody['chainName'], $requestBody['address'], $requestBody['amount'], $withdrawPassword);
						});
					}else{
						throw new APIException(lang('coinId does not set target DB'), self::CODE_CRYPTO_CURRENCY_NOT_ENABLED);
					}
				}else{
					throw new APIException($validateCoinAndChain['validate_msg'], self::CODE_CRYPTO_CURRENCY_NOT_ENABLED);
				}
			}else{
				throw new APIException($isValidateBasicPassed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			if($result['success']){
				$output['code'] = self::CODE_OK;
				$output['data']['id'] = $result['walletAccountId'];
				return $this->returnSuccessWithResult($output);
			}else{
				throw new APIException($result['message'], $result['code']);
			}
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    private function handleCryptoWithdrawalrequest($cryptocurrency, $chainName, $address, $amount, $withdrawPassword)
    {
    	$this->utils->debug_log('============handleCryptoWithdrawalrequest start');
    	$result = [
			'code' => self::CODE_WITHDRAW_REQUEST_OPERATION_FAILED,
			'errorCode' => '',
			'message' => '',
			'success' => false
		];
    	$this->load->model(['playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'wallet_model', 'walletaccount_timelog', 'walletaccount_notes']);
		$playerId 	   = $this->player_id;
		$player   	   = $this->player_model->getPlayerArrayById($playerId);
		$cryptoAccount = $this->playerbankdetails->getCryptoAccountByAddressAndChainName($playerId, 'withdrawal', $cryptocurrency, $address, $chainName);
		if(empty($cryptoAccount)){
			$createResult = $this->createPlayerCryptoAccount($cryptocurrency, $chainName, $address);
			if($createResult['success'] == false) {
				$result['code'] = $createResult['errorCode'];
				$result['message'] = $createResult['message'];
				$result['errorMessage'] = $createResult['message'];
				return $result;
			}
            $bankDetailsId = $createResult['playerBankDetailsId'];
		}else{
			$bankDetailsId = $cryptoAccount['playerBankDetailsId'];
		}
		$withdrawFeeAmount = 0;
		$calculationFormula = '';
		if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
			$this->load->library('payment_library');
			list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player['levelId'], $amount);
			$verify_function_list[] = [ 'name' => 'verifyWithdrawalFeeAmountOverMainAmount', 'params' => [$playerId, $amount, $withdrawFeeAmount] ];
		}

		$withdrawBankFeeAmount = 0;
		$calculationFormulaBank = '';
		if($this->utils->getConfig('enable_withdrawl_bank_fee') && $this->group_level->isOneWithdrawOnly($playerId)){
			$this->load->library('payment_library');
			list($withdrawBankFeeAmount,$calculationFormulaBank) = $this->payment_library->calculationWithdrawalBankFee($playerId, $cryptoAccount['bank_code'], $amount);
			$verify_function_list[] = [ 'name' => 'verifyWithdrawalBankFeeAmountOverMainAmount', 'params' => [$playerId, $amount, $withdrawBankFeeAmount, $withdrawFeeAmount] ];
		}

		$verify_function_list = [
			[ 'name' => 'verifyAgencyCreditMode', 'params' => [$player] ],
			[ 'name' => 'verifyEnablePlayerWithdrawal', 'params' => [$player] ],
            [ 'name' => 'verifyPlayerCompleteUserinfo', 'params' => [$playerId] ],
			[ 'name' => 'verifyKycRiskWithdrawalStatus', 'params' => [$playerId] ],
			[ 'name' => 'verifySubwalletNegativeBalance', 'params' => [$playerId] ],
			[ 'name' => 'verifyExistBankDetailsId', 'params' => [$bankDetailsId] ],
			[ 'name' => 'verifyWithdrawalPassword', 'params' => [$playerId, $withdrawPassword] ],
			[ 'name' => 'verifyAmountDecimal', 'params' => [$amount] ],
			[ 'name' => 'verifyBankDetailsBelongToPlayer', 'params' => [$playerId, $bankDetailsId] ],
			[ 'name' => 'verifyPlayerWithdrawalRule', 'params' => [$playerId, $amount] ],
			[ 'name' => 'verifyUnfinishedWithdrawCondition', 'params' => [$playerId] ],
			[ 'name' => 'verifyLastWithdrawalRequestDone', 'params' => [$playerId] ],
		];
		foreach ($verify_function_list as $method) {
			$this->utils->debug_log('============handleCryptoWithdrawalrequest verify_function', $method);
			$verify_result = call_user_func_array([$this, $method['name']], $method['params']);
			$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
			if(!$exec_continue) {
				$result['message'] = $verify_result['error_message'];
				$result['errorMessage'] = $verify_result['error_message'];
				return $result;
			}
		}
		$walletAccountData = [];
		$walletAccountData = $this->buildInitwalletAccountDataArray($walletAccountData, $playerId, $bankDetailsId, $player, $amount, $withdrawFeeAmount, $withdrawBankFeeAmount);
		$walletAccountData = $this->resetDwStatusPendingReivew($walletAccountData, $playerId);
		$walletAccountData = $this->resetDwStatusPendingReivewRiskScore($walletAccountData, $playerId);
		$rlt= $this->_transferBackToMainWallet($playerId, $player['username']);
		$this->utils->debug_log('after _transferBackToMainWallet, result', $rlt);
		if(!$rlt){
			$result['message'] = lang('Transfer Failed From Sub-wallet');
			$result['errorMessage'] = lang('Transfer Failed From Sub-wallet');
			return $result;
		}
		$walletAccountData['showNotesFlag'] = '1';
		$walletAccountData['amount'] = $amount;
		$beforeBalance = $this->wallet_model->getMainWalletBalance($playerId);
		$walletAccountData['before_balance'] = $beforeBalance;
		$walletAccountData['after_balance'] = $beforeBalance - $amount;

		$walletModel = $this->wallet_model;
		$hasSufficientBalance = false;
		$errorMsg = '';
		$fee = isset($fee) ? $fee : false;
		$withdrawal_notes = isset($withdrawal_notes) ? $withdrawal_notes : false;
		$success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, $fee, $cryptocurrency, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount,$withdrawal_notes) {

			## Check main wallet (id = 0) balance
			$hasSufficientBalance = $this->utils->checkTargetBalance($playerId, 0, $walletAccountData['amount'], $errorMsg);
			if($hasSufficientBalance) {
				$localBankWithdrawalDetails = array(
					'withdrawalAmount' => $walletAccountData['amount'],
					'playerBankDetailsId' => $walletAccountData['player_bank_details_id'],
					'depositDateTime' => $walletAccountData['dwDateTime'],
					'status' => 'active',
				);
				$walletAccountId = $walletModel->newWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId);
				$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request");
				if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
					$withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center, status => request ; %s ; Withdrawal Fee Amount is %s", $calculationFormula, $withdrawFeeAmount);
				}
				$walletModel->addWalletaccountNotes($walletAccountId, $playerId, $withdrawalActionLogByPlayer, $walletAccountData['dwStatus'], null, Walletaccount_timelog::PLAYER_USER);

				if(!empty($walletAccountId)) {
					$adminUserId = $this->users->getSuperAdminId();
					$withdrawal_notes = 'Wallet Address: '.$walletAccountData['bankAccountNumber'].' | Crypto: '.$cryptocurrency;
					$lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
					$crypto_withdrawal_order_id = $walletModel->createCryptoWithdrawalOrder($walletAccountId, $walletAccountData['amount'], 1, $this->utils->getNowForMysql(), $this->utils->getNowForMysql(), $cryptocurrency);
				}
			}
			return !empty($walletAccountId);
		});
		$this->processAutomationRiskPreChecker($walletAccountId, $playerId);
		$this->utils->debug_log("Withdrawal submitted by player [$playerId] evaluated, amount [$amount], hasSufficientBalance [$hasSufficientBalance], walletAccountId [$walletAccountId], errorMsg [$errorMsg]");
		$this->utils->debug_log('============handleCryptoWithdrawalrequest verify_function', 'verifyHasSufficientBalance');
		$verify_result = $this->verifyHasSufficientBalance($hasSufficientBalance, $errorMsg);
		$exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
		if(!$exec_continue) {
			$result['message'] = $verify_result['error_message'];
			$result['errorMessage'] = $verify_result['error_message'];
			return $result;
		}
		$this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);
		if(!$success) {
			$result['message'] = lang('error.withdrawal_failed');
			$result['errorMessage'] = lang('error.withdrawal_failed');
			return $result;
		}
		$walletAccountData['walletAccountId'] = $walletAccountId;
		if($this->utils->getConfig('enable_fast_track_integration')) {
			$this->load->library('fast_track');
			$this->fast_track->requestWithdraw($walletAccountData);
		}
		#OGP-22453,22538
		if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
			if($success){
				$userId = $this->users->getSuperAdminId();
				$this->triggerWithdrawalEvent($playerId, $walletAccountId, null, null, $userId);
			}
		}

		$result['success'] = $success;
		$result['walletAccountId'] = $walletAccountId;
		return $result;
    }

    private function validateCoinAndChain($coin_id, $chain_name)
    {
		$result['valid'] = true;
		$result['validate_msg'] = lang('coinId setting is empty');
		$cryptoSetting = $this->crypto_currency_lib->getEnabledCoinsAndChains();
		if(!empty($cryptoSetting)) {

            if(!in_array($coin_id, $cryptoSetting['coins'])) {
                $result['valid'] = false;
				$result['validate_msg'] = 'coinId value should be '. implode(" or ", $cryptoSetting['coins']).'. ';
				return $result;
            }

			if(!in_array($chain_name, $cryptoSetting['chains'])) {
                $result['valid'] = false;
				$result['validate_msg'] = 'chainName value should be '. implode(" or ", $cryptoSetting['chains']).'. ';
				return $result;
			}

		}
		return $result;
    }

    protected function createPlayerCryptoAccount($cryptoCurrency, $chainName, $address){
		$this->load->model(['banktype','player_model']);
    	$bankList = $this->banktype->getCryptoBank();
    	$bankTypeId = null;
    	foreach ($bankList as $bank_details) {
            if($this->banktype->isBankCodeMatchCoinIdAndChainName($cryptoCurrency, $chainName, $bank_details->bank_code)){
                $bankTypeId = $bank_details->bankTypeId;
    			break;
            }
		}
		if(!empty($bankTypeId)){
			$player = $this->player_model->getPlayerInfoDetailById($this->player_id);
			$bankAccountFullName = Player::getPlayerFullName($player['firstName'], $player['lastName'], $player['language']);
            $result = $this->postAddWithdrawal($this->player_id, $bankTypeId, $address, $bankAccountFullName, $chainName, false, 2, '', '', '', null, null);
		}else{
			$result['success'] = false;
			$result['errorCode'] = Playerapi::CODE_PLAYER_BANK_ACCOUNT_NOT_FOUND;
			$result['message'] = lang('not support this coinId');
			$result['errorMessage'] = lang('not support this coinId');
		}
    	return $result;
    }
}
