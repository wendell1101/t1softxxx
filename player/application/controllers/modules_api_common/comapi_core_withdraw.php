<?php

/**
 * Api_common core module: withdraw
 * Separated 6/17/2021
 *
 * This trait has following member methods:
 *     public function manualWithdraw()
 *     public function manualWithdrawForm()
 *     public function updatePlayerWithdrawalPassword()
 *
 * @see		api_common.php
 */
trait comapi_core_withdraw {

    /**
     * Manual withdraw pre-phase
     * usage:
     *     1 provide crypto exchange rate if withdrawal account is crypto
     *     2 provide withdraw-related limits (single transaction min/max, etc)
     *
     * @uses    POST:api_key        string  The api_key, as md5 sum. Required.
     * @uses    POST:username       string  Player username.  Required.
     * @uses    POST:token          string  Effective token for player. Required.
     * @uses    POST:amount         decimal Withdraw amount.
     * @uses    POST:bankDetailsId  int     bankDetailsId reported by API method listPlayerWithdrawAccounts.  Specify this item to use player's available account.
     *
     * @return  JSON    General JSON return object
     */
    public function manualWithdrawForm() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            // $this->load->model([ 'playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'player_model' ]);
            $this->load->model([ 'player_model' ]);

            $token              = $this->input->post('token', 1);
            $username           = $this->input->post('username', 1);
            $bankDetailsId      = intval($this->input->post('bankDetailsId', 1));
            $amount             = floatval($this->input->post('amount', 1));
            // $bankTypeId         = $this->input->post('bankTypeId');

            $std_creds = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $wd_creds = [ 'bankDetailsId' => $bankDetailsId, 'amount' => $amount ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds, $wd_creds);
            $err_res = null;
            // Check player username
            $player_id = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
            }

            // check player verified phone
            if ($this->utils->getConfig('enable_sms_verified_phone_in_withdrawal')) {
                $player_verified_phone = $this->player_model->isVerifiedPhone($player_id);
                if (!$player_verified_phone) {
                    throw new Exception(lang('withdrawal.msg8'), self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED);
                }
            }

            $mwf_res = $this->comapi_lib->manualWithdrawForm($player_id, $bankDetailsId, $amount);

            if ($mwf_res['code'] != 0) {
                $err_res = $mwf_res['result'];
                throw new Exception($mwf_res['mesg'], $mwf_res['code']);
            }

           // Successful return
            $ret = [
                'success'   => true ,
                'code'      => 0 ,
                'mesg'      => 'manualWithdrawForm execution successful' ,
                'result'    => $mwf_res['result']
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $err_res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    }
    /**
     * Manual withdraw endpoint
     * Demanded by Lanhai, OGP-6509, OGP-7109
     *
     * @uses    POST:api_key        string  The api_key, as md5 sum. Required.
     * @uses    POST:username       string  Player username.  Required.
     * @uses    POST:token          string  Effective token for player. Required.
     * @uses    POST:amount         decimal Withdraw amount.
     * @uses    POST:withdrawal_password    string  Player's withdrawal password.
     * @uses    POST:bankDetailsId  int     bankDetailsId reported by API method listPlayerWithdrawAccounts.  Specify this item to use player's available account.
     * @uses    POST:bankTypeId     int     bankTypeId reported by API method queryDepositWithdrawalAvailableBank.  Specify this item to add and use a new account.
     * @uses    POST:bankAccName    string  New bank account holder name
     * @uses    POST:bankAccNum     string  New bank account number
     * @uses    POST:branch         string  New bank account branch
     * @uses    POST:city           string  New bank account bank city
     * @uses    POST:province       string  New bank account bank province
     * @uses    POST:phone          string  New bank account bank phone
     * @uses    POST:bankAddress    string  New bank account bank address
     *
     * @return  JSON    General JSON return object
     */
    public function manualWithdraw() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'playerbankdetails','risk_score_model', 'banktype', 'player_promo', 'transactions', 'users', 'player_model' ]);

            $token              = $this->input->post('token');
            $username           = $this->input->post('username');
            $bankDetailsId      = $this->input->post('bankDetailsId');
            $withdrawal_password = $this->input->post('withdrawal_password');
            $amount             = $this->input->post('amount');
            $bankTypeId         = $this->input->post('bankTypeId');
            $bankAccountNumber  = $this->input->post('bankAccountNumber');
            $bankAccountFullName= $this->input->post('bankAccountFullName');

            $std_creds = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $wd_creds = [ 'bankDetailsId' => $bankDetailsId, 'bankTypeId' => $bankTypeId, 'bankAccountNumber' => $bankAccountNumber, 'bankAccountFullName' => $bankAccountFullName, 'withdrawal_password' => $withdrawal_password, 'amount' => $amount ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds, $wd_creds);

            $playerId = $this->player_model->getPlayerIdByUsername($username);
            if (empty($playerId)) {
                throw new Exception('Player username invalid', self::CODE_MW_PLAYER_USERNAME_INVALID);
            }

            // Check player token
            if (!$this->__isLoggedIn($playerId, $token)) {
                // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
                // throw new Exception('Token invalid or player not logged in', self::CODE_MW_PLAYER_TOKEN_INVALID);
            }

            $player = $this->player_model->getPlayerArrayById($playerId);

            $class = $this->CI->router->class;
            $method = $this->CI->router->method;
            if ( $this->__isCoolDownIn($player['username'], $class, $method) > 0 ) {
                throw new Exception('The called API is in Cool Down Time', self::CODE_MW_IN_COOLDOWN);
            }

            $this->utils->debug_log('playerId', $playerId, 'player', $player, 'input post', $this->input->post());

            if ($this->utils->isEnabledFeature("show_allowed_withdrawal_status") && $this->utils->isEnabledFeature("show_risk_score") && $this->utils->isEnabledFeature("show_kyc_status")) {
                if(!$this->risk_score_model->generate_allowed_withdrawal_status($playerId)){
                    throw new Exception('Player withdraw is prohibited by KYC checking', self::CODE_MW_ILLEGAL_KYC_STATUS);
                }
            }

            // if(!$this->verifyAndResetDoubleSubmit($playerId)){
            //  throw new Exception('Double submit detected', self::CODE_MW_DOUBLE_SUBMIT_DETECTED);
            // }

            if ($player['enabled_withdrawal'] == self::WITHDRAWAL_DISABLED) {
                throw new Exception('Player withdrawal is disabled.', self::CODE_MW_PLAYER_WITHDRAWAL_DISABLED);
            }

            // SMS and Password verification is not yet present in new player center. Verify withdraw password only
            // $withdrawVerificationMethod = $this->utils->getConfig('withdraw_verification');
            // if($withdrawVerificationMethod == 'withdrawal_password'){ }

            // OGP-9659: Use sys feature enabled_withdraw_password to control withdrawal password verification

            $enabled_change_withdrawal_password = $this->operatorglobalsettings->getSettingJson('enabled_change_withdrawal_password');
            $enabled_change_withdrawal_password = (empty($enabled_change_withdrawal_password)) ? ['disable'] : $enabled_change_withdrawal_password;

            if (in_array('enable', $enabled_change_withdrawal_password) &&
                $this->utils->getConfig('withdraw_verification') == 'withdrawal_password') {
                $checkWithdrawPassword=$this->player_model->validateWithdrawalPassword($playerId, $withdrawal_password);
                if(!$checkWithdrawPassword){
                    throw new Exception('Withdrawal password does not match', self::CODE_MW_WRONG_WITHDRAWAL_PASSWORD);
                }
            }

            // OGP-15776: checks for bankDetailsId
            if (!empty($bankDetailsId)) {
                $this->utils->debug_log(__METHOD__, 'player acc check', [ 'playerId' => $playerId ]);
                if (!$this->comapi_lib->is_valid_withdraw_account_for_player($playerId, $bankDetailsId)) {
                    throw new Exception('bankDetailsId invalid', self::CODE_MW_BANKDETAILSID_INVALID);
                }
            }

            // Validate withdraw limits
            // $this->utils->debug_log(__METHOD__, "Withdrawal submitted by:", [ 'player' => $playerId, 'amount' => $amount, 'bankDetailsId' => $bankDetailsId ]);

            // Check whether withdrawal satisfies withdrawal rule for this player
            $playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
            list($withdrawalCount, $accumulativeAmount) = $this->transactions->count_today_withdraw($playerId);
            if (!$playerWithdrawalRule || is_null($playerWithdrawalRule['max_withdraw_per_transaction'])) {
                throw new Exception("Undefined withdraw rule", self::CODE_MW_UNDEFINED_WITHDRAW_RULE);
            }
            if ($amount > $playerWithdrawalRule['max_withdraw_per_transaction']) {
                throw new Exception("Amount of a single withdrawal cannot exceed {$playerWithdrawalRule['max_withdraw_per_transaction']}", self::CODE_MW_SINGLE_WITHDRAWAL_AMOUNT_MAX_HIT);
            }
            if ($amount + $accumulativeAmount > $playerWithdrawalRule['daily_max_withdraw_amount']) {
                throw new Exception("Amount of withdrawals of a single day cannot exceed {$playerWithdrawalRule['daily_max_withdraw_amount']}", self::CODE_MW_DAILY_WITHDRAWAL_LIMIT_HIT);
            }
            if ($withdrawalCount >= $playerWithdrawalRule['withdraw_times_limit']) {
                throw new Exception("Count of withdrawals of a single day cannot exceed {$playerWithdrawalRule['withdraw_times_limit']}", self::CODE_MW_DAILY_WITHDRAWAL_COUNT_LIMIT_HIT);
            }

            if ($amount < $playerWithdrawalRule['min_withdraw_per_transaction']) {
                throw new Exception("Amount of a single withdrawal must be at least {$playerWithdrawalRule['min_withdraw_per_transaction']}", self::CODE_MW_SINGLE_WITHDRAWAL_AMOUNT_MIN_HIT);
            }

            //check  withdrawal conditions
            if ($this->utils->isEnabledFeature('check_withdrawal_conditions')) {
                $un_finished = $this->withdraw_condition->getPlayerUnfinishedWithdrawCondition($playerId);
                $this->utils->debug_log(__FUNCTION__, 'check_withdrawal_conditions',  [ 'playerId' => $playerId, 'un_finished' => $un_finished ]);
                if(FALSE !== $un_finished){

                    throw new Exception(lang('notify.withdrawal.condition'), self::CODE_MW_BET_AMOUNT_NOT_SATISFIED);
                }

                // $withdraw_data = $this->withdraw_condition->getPlayerWithdrawalCondition($playerId);
                // $totalRequiredBet =$this->comapi_lib->multi_array_sum($withdraw_data,'conditionAmount');
                // $totalPlayerBet =$this->comapi_lib->multi_array_sum($withdraw_data,'currentBet');
                // $un_finished = $totalRequiredBet - $totalPlayerBet ;
                // $this->utils->debug_log(__FUNCTION__, 'check_withdrawal_conditions',  [ 'playerId' => $playerId, 'totalRequiredBet' => $totalRequiredBet, 'totalPlayerBet' => $totalPlayerBet, 'un_finished' => $un_finished ]);
                // //un_finished
                // if ($un_finished > 0){
                //     throw new Exception("Bet amount not met, required: {$totalRequiredBet} to go: {$un_finished}", self::CODE_MW_BET_AMOUNT_NOT_SATISFIED);
                // }
            }

            //check  withdrawal conditions -- for each
            if ($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach')) {
                $withdraw_data = $this->withdraw_condition->getPlayerWithdrawalCondition($playerId);
                if(!empty($withdraw_data)) {
                    foreach($withdraw_data as $v){
                         $this->utils->debug_log("check_withdrawal_conditions_foreach===>", "player", [$playerId], "conditionAmount", $v['conditionAmount'], "currentBet", $v['currentBet'], "un_finished", $v['unfinished']);
                        //un_finished
                        if ($v['unfinished'] > 0){
                            throw new Exception("Withdrawal condition not met, required: {$v['conditionAmount']} to go: {$v['unfinished']}", self::CODE_MW_CONDITION_AMOUNT_NOT_SATISFIED);
                        }
                    }
                }
            }

            ##check deposit conditions in withdrawal conditions -- for each
            if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
                if(FALSE !== $un_finished_deposit = $this->withdraw_condition->getPlayerUnfinishedDepositConditionForeach($playerId)){
                    //un_finished_deposit
                    throw new Exception("Deposit condition not met", self::CODE_MW_CONDITION_AMOUNT_NOT_SATISFIED);
                }
            }

            // Check whether multiple pending withdraws are allowed
            $this->load->model('group_level');
            if ($this->group_level->isOneWithdrawOnly($playerId)) {
                $this->load->model('player_model');
                if ($this->player_model->countWithdrawByStatusList($playerId) >= 1) {
                    throw new Exception("Group rules prohibit multiple withdrawals at a time", self::CODE_MW_GROUP_LIMITS_ONLY_ONE_WITHDRAWAL);
                }
            }

            // All checks done, we can now proceed to submit withdrawal request
            // Save bank info if it's new (no bankDetailsId given)
            if(!$bankDetailsId) {
                $data = array(
                    'playerId' => $playerId,
                    'bankTypeId' => $this->input->post('bankTypeId'),
                    'bankAccountNumber' => $this->input->post('bankAccNum'),
                    'bankAccountFullName' => $this->input->post('bankAccName'),
                    'bankAddress' => $this->input->post('bankAddress'),
                    'province' => $this->input->post('province'),
                    'city' => $this->input->post('city'),
                    'branch' => $this->input->post('branch'),
                    'isRemember' => Playerbankdetails::REMEMBERED,
                    'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
                    'status' => Playerbankdetails::STATUS_ACTIVE,
                    'phone' => $this->input->post('phone'),
                );

                if(empty($data['bankTypeId'])) {
                    throw new Exception('bankTypeId missing', self::CODE_MW_MISSING_BANKTYPEID);
                }
                $this->load->library('player_functions');
                $existsBankDetails = $this->player_functions->getPlayerExistsBankDetails($playerId, $data['bankAccountNumber'], $data['bankTypeId'], Playerbankdetails::WITHDRAWAL_BANK);
                if(!empty($existsBankDetails)) {
                    $bankDetailsId = $existsBankDetails['playerBankDetailsId'];
                } else {
                    $bankDetailsId = $this->player_functions->addBankDetailsByWithdrawal($data);
                }
            }

            $this->load->model('wallet_model');
            $playerAccount = $this->player_model->getPlayerAccountByPlayerIdOnly($playerId);
            // $beforeBalance = $this->wallet_model->getMainWalletBalance($playerId);
            $dwIp = $this->utils->getIP();
            $geolocation = $this->utils->getGeoplugin($dwIp);

            // Get bank details
            $playerBankDetails = $this->playerbankdetails->getBankList(array('playerBankDetailsId' => $bankDetailsId))[0];
            // $bankName = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId'])->bankName;
            $banktype = $this->banktype->getBankTypeById($playerBankDetails['bankTypeId']);
            $bankName = $banktype->bankName;

            $this->load->library('payment_library');
            $withdrawFeeAmount = 0;
            $calculationFormula = '';
            #if enable config get withdrawFee from player
            if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
                list($withdrawFeeAmount,$calculationFormula) = $this->payment_library->chargeFeeWhenWithdrawalAmountOverMonthlyAmount($playerId, $player['levelId'], $amount);
            }

            //wallet account details
            $walletAccountData = array(
                'playerAccountId' => $playerAccount['playerAccountId'],
                'walletType' => 'Main',
                // 'amount' => $amount,
                'dwMethod' => 1,
                'dwStatus' => 'request',
                'dwDateTime' => $this->utils->getNowForMysql(),
                'transactionType' => 'withdrawal',
                'dwIp' => $dwIp,
                'dwLocation' => $geolocation['geoplugin_city'] . ',' . $geolocation['geoplugin_countryName'],
                'transactionCode' => $this->wallet_model->getRandomTransactionCode(),
                'status' => '0',
                //after transfer back balance
                // 'before_balance' => $beforeBalance,
                // 'after_balance' => $beforeBalance - $amount,
                'playerId' => $playerId,
                'player_bank_details_id'=>$bankDetailsId,
                'bankAccountFullName' => $playerBankDetails['bankAccountFullName'],
                'bankAccountNumber' => $playerBankDetails['bankAccountNumber'],
                'bankName' => $bankName,
                'bankAddress' => $playerBankDetails['bankAddress'],
                'bankCity' => $playerBankDetails['city'],
                'bankProvince' => $playerBankDetails['province'],
                'bankBranch' => $playerBankDetails['branch'],
                'withdrawal_fee_amount'  => $withdrawFeeAmount,
            );

            // OGP-3531
            if($this->utils->isEnabledFeature('enable_withdrawal_pending_review')){
                if($this->checkPlayerIfTagIsUnderPendingWithdrawTag($playerId)){
                    $walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
                }

                // OGP-3996 // add withdrawal_pending_review to risk score chart and set 1 = true unset if false in System settings->Risk score setting
                if($this->utils->isEnabledFeature('enable_withdrawal_pending_review_in_risk_score') && $this->utils->isEnabledFeature('show_risk_score')) {
                    $risk_score_levels = $this->risk_score_model->getRiskScoreInfo(risk_score_model::RC);
                    $this->utils->debug_log("risk_score_levels enable_withdrawal_pending_review_in_risk_score", "enabled");
                    if(!empty($risk_score_levels)){
                        $this->utils->debug_log("risk_score_levels not empty", true);
                        if(isset($risk_score_levels['rules'])){
                            $this->utils->debug_log("risk_score_levels rules set", true);
                            $rules = json_decode($risk_score_levels['rules'],true);
                            if(!empty($rules)) {
                                $this->utils->debug_log("risk_score_levels rules not empty", true);
                                foreach ($rules as $key => $value) {
                                    if(isset($value['withdrawal_pending_review'])){
                                        $this->utils->debug_log("risk_score_levels withdrawal_pending_review set", true);
                                        if($value['withdrawal_pending_review']){
                                            $this->utils->debug_log("risk_score_levels withdrawal_pending_review enabled", true);
                                            $player_risk_score_level = $this->risk_score_model->getPlayerCurrentRiskLevel($playerId);
                                            $this->utils->debug_log("risk_score_levels player_risk_score_level value", $player_risk_score_level);
                                            $this->utils->debug_log("risk_score_levels risk_score value", $value['risk_score']);
                                            if($player_risk_score_level == $value['risk_score']){
                                                $this->utils->debug_log("risk_score_levels withdrawal_pending_review set", true);
                                                $walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_STATUS;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->utils->debug_log("risk_score_levels walletAccountData", $walletAccountData['dwStatus']);

            //transfer back balance by feature
            $rlt= $this->_transferBackToMainWallet($playerId, $player['username']);
            $this->utils->debug_log('after _transferBackToMainWallet, result', $rlt);
            if(!$rlt){
                throw new Exception('Cannot transfer back from sub-wallets', self::CODE_MW_FAILED_XFER_FROM_SUBWALLETS);
            }

            /*  OGP-13007 Deprecate Promo Rule 7. Withdraw Requirement
            //check promotion to get max limit of withdrawal
            $maxLimit=$this->player_promo->getMaxLimitWithdrawalFromAppliedPromoOnWithdrawal($playerId);
            $changedAmount=false;
            if($maxLimit!=null && $maxLimit>0){
                $adminUserId=Users::SUPER_ADMIN_ID;
                $success = $this->lockAndTransForPlayerBalance($playerId, function ()
                    use ($playerId, $maxLimit, $adminUserId, &$changedAmount) {
                    $success=true;
                    //check balance
                    $targetBalance = $this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
                    if($this->utils->compareResultFloat($targetBalance, '>', $maxLimit)){
                        //try to minus balance
                        $minus_balance= $this->utils->roundCurrencyForShow($targetBalance-$maxLimit);
                        $note='minus balance to '.$maxLimit.' for promotion';
                        $tran=$this->transactions->createDecTransaction($playerId, $minus_balance, $adminUserId, $note);
                        $success=!empty($tran);
                        if($success){
                            $changedAmount=true;
                        }
                    }

                    return $success;
                });

                $this->utils->debug_log('check withdrawal max limit', $success);

                if(!$success){
                    throw new Exception('Failed to withdraw limit rule from promotions', self::CODE_MW_FAILED_PROMO_MAX_LIMIT_WD_RULE);
                }
            }
            if($changedAmount){
                //reset back
                $amount = $this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
            }
            */

            if($this->utils->getConfig('enable_pending_review_custom')){
                $getWithdrawalCustomSetting = json_decode($this->operatorglobalsettings->getSetting('custom_withdrawal_processing_stages')->template,true);

                if(!empty($getWithdrawalCustomSetting['pendingCustom']) && $getWithdrawalCustomSetting['pendingCustom']['enabled']){
                    if($this->checkPlayerIfTagIsUnderPendingCustomWithdrawTag($playerId)){
                        $walletAccountData['dwStatus'] = Wallet_model::PENDING_REVIEW_CUSTOM_STATUS;
                        $this->utils->debug_log("check player tag under pending custom walletAccountData", $walletAccountData['dwStatus']);
                    }
                }
            }

            $walletAccountData['amount']=$amount;
            $beforeBalance = $this->wallet_model->getMainWalletBalance($playerId); //$this->wallet_model->getTargetTransferBalanceOnMainWallet($playerId, Wallet_model::MAIN_WALLET_ID);
            $walletAccountData['before_balance']=$beforeBalance;
            $walletAccountData['after_balance']=$beforeBalance - $amount;

            #cryptocurrency withdrawal
            if($this->utils->isCryptoCurrency($banktype)){
                $cryptocurrency  = $this->utils->getCryptoCurrency($banktype);
                $defaultCurrency = $this->utils->getCurrentCurrency()['currency_code'];

                $custom_withdrawal_fee = $this->config->item('custom_withdrawal_fee') ? $this->config->item('custom_withdrawal_fee') : 0;
                list($crypto, $rate) = $this->utils->convertCryptoCurrency($amount, $defaultCurrency, $cryptocurrency, 'withdrawal');
                $crypto_to_currecny_exchange_rate = $this->utils->getCryptoToCurrecnyExchangeRate($defaultCurrency);
                $cust_crypto_allow_compare_digital = $this->utils->getCustCryptoAllowCompareDigital($cryptocurrency);
                $exchange_rate_token = $this->input->post('exchange_rate_token');
                $request_cryptocurrency_rate = null;
                if(!empty($exchange_rate_token)){
                    $captcha_cache_token = sprintf('%s-%s', 'withdrawCryptoRate', $exchange_rate_token);
                    $request_cryptocurrency_rate = $this->utils->getJsonFromCache($captcha_cache_token);
                    $this->utils->debug_log('withdrawCryptoRate', $request_cryptocurrency_rate);
                    // delete cache, only use once
                    $this->utils->deleteCache($captcha_cache_token);
                }

                $reciprocalDecimalPlaceSetting = $this->utils->getCustCryptoInputDecimalPlaceSetting($cryptocurrency,false);
                $cache_rate = '';

                if(is_array($request_cryptocurrency_rate) && !empty($request_cryptocurrency_rate)){
                    foreach ($request_cryptocurrency_rate as $key => $value) {
                        if($key == $cryptocurrency){
                            $cache_rate = $value;
                        }
                    }
                }
                $this->utils->debug_log('---Cache crypto rate and current crypto rate--- ', $cache_rate, $rate);
                if(!empty($rate) && !empty($cache_rate)){
                    if(abs($rate - $cache_rate) > $cust_crypto_allow_compare_digital){
                        throw new Exception('compare crypto rate is out range');
                    }
                    $rate = $cache_rate;
                    $crypto = number_format($amount/ $rate, $reciprocalDecimalPlaceSetting,'.','');
                }
                $crypto = $crypto * $crypto_to_currecny_exchange_rate;
                // OGP-23116: See Abstract_cryptorate_api
                $this->utils->debug_log(__METHOD__, 'crypto after conversion', $crypto);

                $custom_withdrawal_rate = $this->config->item('custom_withdrawal_rate') ? $this->config->item('custom_withdrawal_rate') : 1;
                // $crypto      = number_format($crypto * $custom_withdrawal_rate, 4, '.', '');
                $player_rate = number_format($rate   * $custom_withdrawal_rate, 4, '.', '');
                $withdrawal_notes = 'Wallet Address: '.$walletAccountData['bankAccountNumber'].' | '.$cryptocurrency.': '.$crypto.' | Crypto Real Rate: '.$rate;

                $walletAccountData['notes'] = $withdrawal_notes;
                $walletAccountData['extra_info'] = $crypto;
                $this->utils->debug_log('=======================cryptocurrency withdrawal_notes', $withdrawal_notes);
            }

            $this->load->model(array('wallet_model','walletaccount_timelog'));
            $walletModel = $this->wallet_model;
            $hasSufficientBalance = false;
            $errorMsg = '';
            $cryptoSet = isset($crypto) ? $crypto : false;
            $rate = isset($rate) ? $rate : '1';
            $cryptocurrency = isset($cryptocurrency) ? $cryptocurrency : false;
            $playerRateSet = isset($player_rate) ? $player_rate : false;
            $fee = isset($fee) ? $fee : false;
            $withdrawal_notes = isset($withdrawal_notes) ? $withdrawal_notes : false;
            if(($cryptoSet != false) && ($playerRateSet != false)) {
                $walletAccountData['showNotesFlag'] = '1';
            }


            // $success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount) {
            $success = $this->lockAndTransForPlayerBalance($playerId, function () use ($walletModel, $playerId, $walletAccountData, $fee, $cryptoSet, $cryptocurrency, $rate, $playerRateSet, &$walletAccountId, &$hasSufficientBalance, &$errorMsg, $calculationFormula, $withdrawFeeAmount,$withdrawal_notes) {
                // Check main wallet (id = 0) balance
                $hasSufficientBalance = $this->utils->checkTargetBalance($playerId, 0, $walletAccountData['amount'], $errorMsg);
                if ($hasSufficientBalance) {
                    $localBankWithdrawalDetails = array(
                        'withdrawalAmount' => $walletAccountData['amount'],
                        'playerBankDetailsId' => $walletAccountData['player_bank_details_id'],
                        'depositDateTime' => $walletAccountData['dwDateTime'],
                        'status' => 'active',
                    );
                    $walletAccountId = $walletModel->newWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId);
                    $withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center api, status => request");
                    if($this->utils->getConfig('enable_withdrawl_fee_from_player') && $this->group_level->isOneWithdrawOnly($playerId)){
                        $withdrawalActionLogByPlayer = sprintf("Withdrawal is success processing from player center api, status => request ; %s ; Withdrawal Fee Amount is %s", $calculationFormula, $withdrawFeeAmount);
                    }
                    $walletModel->addWalletaccountNotes($walletAccountId, $playerId, $withdrawalActionLogByPlayer, 'request', null, Walletaccount_timelog::PLAYER_USER);

                    if(!empty($walletAccountId) && ($cryptoSet != false) && ($playerRateSet != false)) {
                        $adminUserId = $this->users->getSuperAdminId();
                        $lastTransactionNotesId = $this->walletaccount_notes->add($withdrawal_notes, $adminUserId, Walletaccount_notes::ACTION_LOG, $walletAccountId);
                            $crypto_withdrawal_order_id = $walletModel->createCryptoWithdrawalOrder($walletAccountId, $cryptoSet, $rate, $this->utils->getNowForMysql(), $this->utils->getNowForMysql(),$cryptocurrency);
                    }

                } // End if ($hasSufficientBalance)

                return !empty($walletAccountId);
            }); // End closure lockAndTransForPlayerBalance()

            $this->utils->debug_log(__FUNCTION__, "Withdrawal evaluated",  [ 'playerId' => $playerId , 'amount' => $amount , 'hasSufficientBalance' => $hasSufficientBalance, 'walletAccountId' => $walletAccountId, 'errorMsg' => $errorMsg ], $std_creds);

            if(!$hasSufficientBalance) {
                throw new Exception('Insufficient balance', self::CODE_MW_INSUFFICIENT_BALANCE);
            }

            $this->saveHttpRequest($playerId, Http_request::TYPE_WITHDRAWAL);
            if(!$success) {
                throw new Exception('Withdrawal failed', self::CODE_MW_WITHDRAWAL_FAILED);
            }

            $withdrawSuccessMsg = lang($this->config->item('playercenter.withdrawMsg.success') ?: 'notify.58');
            // $this->utils->debug_log('manualWithdraw', 'withdrawSuccessMsg', $withdrawSuccessMsg);
//          if($changedAmount){
//              $withdrawSuccessMsg.=$withdrawSuccessMsg.' '.lang('Changed Automatically Amount because Promotion Limit');
//          }

            // send prompt message when withdrawal request is successful
            if ($this->utils->isEnabledFeature('enable_sms_withdrawal_prompt_action_request')) {

                $this->load->model(['cms_model', 'queue_result']);
                $this->load->library(["lib_queue", "sms/sms_sender"]);

                $dialingCode = $player['dialing_code'];
                $mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];
                $smsContent = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_WITHDRAWAL_REQUEST);
                $mobileNumIsVeridied = $player['verified_phone'];
                $isUseQueueToSend    = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');
                $callerType = Queue_result::CALLER_TYPE_ADMIN;
                $caller = $playerId;
                $state  = null;
                $use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
                $useSmsApi = null;
                $sms_setting_msg = '';
                if ($use_new_sms_api_setting) {
                #restrictArea = action type
                    $sessionId = $this->session->userdata('session_id');
                    $restrictArea = 'sms_api_manager_setting';
                    list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId);
                }

                $this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg);

                if ($mobileNumIsVeridied && $isUseQueueToSend) {
                    $this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
                } else if ($mobileNumIsVeridied) {
                    $this->sms_sender->send($mobileNum, $smsContent, $useSmsApi);
                }
            }

            if ( ! empty( $this->utils->getConfig('enable_withdrawalARP') ) ){
                if( ! empty($walletAccountId) ){
                    $this->load->library(["lib_queue"]);
                    // withdrawal Auto Rick Process
                    $callerType = Queue_result::CALLER_TYPE_ADMIN;
                    $caller = $playerId;
                    $state  = null;
                    $lang=null;
                    $this->lib_queue->addRemoteProcessPreCheckerJob($walletAccountId, $callerType, $caller, $state, $lang);
                }
            } // EOF if ( ! empty( $this->utils->getConfig('enable_withdrawalARP') ) ){...

            $walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);
            // $this->utils->debug_log(__FUNCTION__, [ 'walletAccountId' => $walletAccountId , 'walletAccount' => $walletAccount ]);
            $res = [ 'withdraw_code' => $walletAccount->transactionCode ];

            $this->utils->debug_log(__FUNCTION__, "Withdrawal successful",  [ 'mesg' => $withdrawSuccessMsg ], $std_creds, $wd_creds, $res);

            // Successful return
            $ret = [
                'success'   => true ,
                'code'      => 0 ,
                'mesg'      => $withdrawSuccessMsg ,
                'result'    => $res
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], ['username' => $username, 'token' => $token]);
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
    } // End function manualWithdraw()

    /**
     * Update player's withdrawal password
     *
     * @uses    POST:api_key        string  The api_key, as md5 sum. Required.
     * @uses    POST:username       string  Player username.  Required.
     * @uses    POST:token          string  Effective token for player.
     * @uses    POST:old_password   string  The current withdrawal password
     * @uses    POST:new_password   string  The new withdrawal password
     * @uses    POST:force_reset    int     1 to forcibly reset (requires no old password); leave empty or skip for the general way (only update if the old password matches)
     *
     * @return  JSON    General JSON return object
     */
    public function updatePlayerWithdrawalPassword() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'player_model' ]);

            $token          = $this->input->post('token');
            $username       = $this->input->post('username');
            $old_password   = $this->input->post('old_password');
            $new_password   = $this->input->post('new_password');
            $force_reset    = $this->input->post('force_reset');
            $std_creds      = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];

            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds, [ 'old_password' => $old_password, 'new_password' => $new_password, 'force_reset'=> $force_reset ]);

            $player_id = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_LG_PLAYER_USERNAME_INVALID);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
                throw new Exception('Token invalid or player not logged in', self::CODE_COMMON_INVALID_TOKEN);
                // throw new Exception('Token invalid or player not logged in', self::CODE_LG_PLAYER_TOKEN_INVALID);
            }

            $current_password_not_empty = !$this->player_model->isPlayerWithdrawalPasswordEmpty($player_id);

            $not_force_reset = empty($force_reset);

            // $this->utils->debug_log(__FUNCTION__, 'current_password_not_empty', $current_password_not_empty, 'not_force_reset', $not_force_reset , $std_creds);

            // Not force reset nor old password empty => check old
            if ($not_force_reset && $current_password_not_empty) {
                // Old password not empty
                if (empty($old_password)) {
                    throw new Exception('Old withdrawal password required', self::CODE_UPWP_OLD_PASSWORD_MISSING);
                }

                // Old password must match
                if (!$this->player_model->validateWithdrawalPassword($player_id, $old_password)) {
                    throw new Exception('Old withdrawal password does not match', self::CODE_UPWP_OLD_PASSWORD_NOT_MATCH);
                }

                // New password cannot repeat the old
                if ($new_password == $old_password) {
                    throw new Exception('Do not use exactly the old password again', self::CODE_UPWP_NEW_PASSWORD_REPEATS_OLD);
                }
            }

            // New password must not be empty
            if (empty($new_password)) {
                throw new Exception('New withdrawal password required', self::CODE_UPWP_NEW_PASSWORD_MISSING);
            }

            // All checks passed
            $update_res = $this->player_model->updateWithdrawalPassword($player_id, $new_password);

            $this->utils->debug_log(__FUNCTION__, 'updating wd password', [ 'username' => $username, 'force_reset' => !empty($force_reset), 'old_passwd'=> $old_password, 'new_passwd' => $new_password, 'result'=> $update_res ]);

            $ret = [
                'success'   => true ,
                'code'      => 0 ,
                'mesg'      => 'Player withdrawal password updated successfully' ,
                'result'    => null
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], ['username' => $username, 'token' => $token]);
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
    } // End function updatePlayerWithdrawalPassword()

} // End trait comapi_core_withdraw