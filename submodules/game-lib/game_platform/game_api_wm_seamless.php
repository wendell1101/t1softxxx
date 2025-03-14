<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_wm.php';

class Game_api_wm_seamless extends Abstract_game_api_common_wm {

    public $request;

    public $original_gamelogs_table;
    public $original_transactions_table;

	public function getPlatformCode(){
		return WM_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();

    	$this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->original_gamelogs_table = $this->getSystemInfo('original_gamelogs_table', 'wm_seamless_game_logs');
        $this->original_transactions_table = $this->getSystemInfo('original_transactions_table', 'wm_casino_transactions');

        $this->uri_map[self::API_queryForwardGame] = 'LoginGame';

		$this->trigger_delay_pointinout2 = $this->getSystemInfo('trigger_error_pointinout2', 0);
		$this->trigger_delay_pointinout1 = $this->getSystemInfo('trigger_error_pointinout', 0);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->enable_mock_failed_transaction = $this->getSystemInfo('enable_mock_failed_transaction', false);
        $this->enable_mock_failed_transaction_player_list = $this->getSystemInfo('enable_mock_failed_transaction_player_list', []);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("WM CASINO SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("WM CASINO SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("WM CASINO SEAMLESS: (queryPlayerBalance)");

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("WM CASINO SEAMLESS: (queryPlayerBalanceByPlayerId)");

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function queryTransaction($transactionId, $extra=null) {
        return $this->returnUnimplemented();
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function isSeamLessGame(){
        return true;
    }

	public function queryForwardGame($playerName, $extra = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);

        $language = $extra['language'];
        if ($this->language !==null) {
            $language =$this->language;
        }
        $language =$this->getLauncherLanguage($language);
        $password = $this->CI->game_provider_auth->getPasswordByLoginName($gameUsername, $this->getPlatformCode());
        $syslang = $this->getLauncherLanguage($this->syslang);
        $isTest = (isset($extra['game_mode']) && $extra['game_mode'] == "trial") ? 1 : "";

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername'    => $gameUsername
		);

		$params = array (
    		"cmd" => $this->uri_map[self::API_queryForwardGame],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"password" => $password,
			"lang" => $language,
			"returnurl" => $this->returnurl,
			"isTest" => $isTest,
			"syslang" => $syslang,
		);

		// addtional params if needed
		// $params['size'] = $this->size;
		// $params['site'] = $this->site;
		
		if ($this->ui && !empty($this->ui)) {
			$params['ui'] = $this->ui;
		}

		if ($extra['game_code'] && $extra['game_code'] != null) {
			$params['mode'] = $this->getGameMode($extra['game_code']);
		}

		$this->CI->utils->debug_log('WM processResultForQueryForwardGame params: ', $params);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}


    ############### SEAMLESS SERVICE API CODES ###################

    public function getTransactionsTable($monthStr = null){

        $origTableName = $this->original_transactions_table;

        if(!$this->use_monthly_transactions_table){
            return $origTableName;
        }

        if(empty($monthStr)){
            $date=new DateTime();
            $monthStr=$date->format('Ym');
        }
        $this->CI->load->model(array('wm_casino_transactions'));
        return $this->CI->wm_casino_transactions->initTransactionsMonthlyTableByDate($monthStr, $origTableName);        
    }

	public function debitCreditAmountToWallet($params, $mode, $request, &$previousBalance, &$afterBalance){        

        if(empty($this->request)){
            $this->request = $request;
        }

		$this->CI->utils->debug_log(__METHOD__, $params, $previousBalance, $afterBalance);
        $this->CI->load->model(array('wm_casino_transactions'));

        $tableName = $this->getTransactionsTable();
        $this->CI->wm_casino_transactions->setTableName($tableName);

        $gameStatus = Game_logs::STATUS_PENDING;

		//initialize params
		$player_id			= $params['player_id'];
        $code               = $params['code'];
		$amount 			= abs($this->gameAmountToDBTruncateNumber($params['money']));
        $params['amount'] = $amount;
        $external_uniqueid  = $params['external_uniqueid'];
        $round_id           = $params['round_id'];
		
		//initialize response
		$success                = false;
		$isValidAmount          = true;		
		$insufficientBalance    = false;	
		$isTransactionAdded     = false;
		$flagrefunded           = false;
		$additionalResponse	    = [];
        $trans_type             = $params['trans_type'];
        $prevTranstable         = $this->CI->wm_casino_transactions->getTransactionsPreviousTable($this->original_transactions_table);

        $totalBetAmount         = 0;
        $totalWinAmount         = 0;
        $totalResultAmount      = 0;

        $get_balance = $this->queryPlayerBalanceByPlayerId($player_id);
        $afterBalance = $previousBalance = isset($get_balance['balance'])?$get_balance['balance']:0;
		$isAlreadyExists = false;	        
		$alreadyCancelled = false;	        
		$alreadySettled = false;

        # check insufficient
        if($mode == 'debit' && $previousBalance<$amount){
            $insufficientBalance = true;
            $this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) ERROR: INSUFFICIENT BALANCE", 
            'previousBalance', $previousBalance,
            'amount', $amount,
            'request', $this->request,
            'params', $params
            );
            return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        # check if need to check prev table
        $checkOtherTable = $this->checkOtherTransactionTable();

        # set unique id
        $uniqueid_of_seamless_service = $this->getPlatformCode().'-'.$params['external_uniqueid'];
        $external_game_id = isset($params['gtype'])?$params['gtype']:null;
        $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id );

        $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        if($params['trans_type']=='PointInoutIncrease' || $params['trans_type']=='PointInoutIncreaseLoseToWin' || $params['trans_type']=='PointInoutRePayout'){
            $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        }else if($params['trans_type'] == 'TimeoutBetReturnIncrease' || $params['trans_type'] == 'TimeoutBetReturnDecrease'){
            $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        }
        $this->CI->wallet_model->setGameProviderActionType($remoteActionType);

        # check if already exist
        $isAlreadyExists = false;
        $roundData = $this->CI->wm_casino_transactions->getRoundData($this->getTransactionsTable(),$round_id);

        if(empty($isAlreadyExists)&&$checkOtherTable){                         
            $prevRoundData = $this->CI->wm_casino_transactions->getRoundData($prevTranstable, $round_id);
            $roundData = array_merge($roundData, $prevRoundData);
        }

        # get total amounts to use for game logs
        list($totalBetAmount, $totalWinAmount, $totalResultAmount) = $this->getTotalBetAndResultAmount($amount, $params, $roundData);

        $pointInoutDecreastToCancel = null;
        $pointInoutIncreaseToCancel = null;
        $pointInoutDecrease = [];

        # general checking if duplicate request
        foreach($roundData as $roundRow){
            # check if already exist by unique id
            if($roundRow['external_uniqueid']==$params['external_uniqueid']){
                $this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) DUPLICATE REQUEST", $this->request,
                'roundData', $roundData);
                $isAlreadyExists = true;	
                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);    
            }

            # get pointdecrease to cancel, increase balance
            if($trans_type=='TimeoutBetReturnIncrease'){
                if($roundRow['trans_type']=='PointInoutIncrease'){
                    $pointInoutIncreaseToCancel = $roundRow;
                }
            }

            # collect all decrease code=2
            if($roundRow['trans_type']=='PointInoutDecrease'){
                $pointInoutDecrease[] = $roundRow;
            }

            # get pointdecrease to cancel, increase balance
            if($trans_type=='TimeoutBetReturnDecrease'){
                if($roundRow['trans_type']=='PointInoutDecrease' && $roundRow['dealid']==$params['dealid']){
                    $pointInoutDecreastToCancel = $roundRow;
                }
            }
        }//end for each round

        if($trans_type=='TimeoutBetReturnDecrease'){
            /**
             * 2. When PointInOut by bet is failed(code=2) ---> If you receive rollback , refuse to roll back.
             */
            $additionalResponse['TimeoutBetReturnDecreaseMissing'] = false;
            if(empty($pointInoutDecreastToCancel)){
                $this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) #2", $this->request, 
                'pointInoutDecreastToCancel', $pointInoutDecreastToCancel, 
                'roundData', $roundData);
                $additionalResponse['TimeoutBetReturnDecreaseMissing'] = true;
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }else{
                /**
                 * 1. When PointInOut by bet is successful(code=2) ---> If you receive rollback , agree to roll back (cancel the order , increase your customer balance).
                 */
            }
        }

        # check if trying to rollback/credit bet code=2, cannot payout or retry increase if no valid debit/code2
        # checking is increase balance from payout is valid, criteria: if bet/deeduct/code2 is present
        if($trans_type=='TimeoutBetReturnIncrease' || $trans_type=='PointInoutIncrease'){
            $additionalResponse['MissingPointInoutDecrease'] = false;
            if(empty($pointInoutDecrease)){
                $this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) check TimeoutBetReturnIncrease has debit code 2", $this->request, 
                'pointInoutDecrease', $pointInoutDecrease, 
                'roundData', $roundData);
                $additionalResponse['MissingPointInoutDecrease'] = true;
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }
        }

        if($trans_type=='TimeoutBetReturnIncrease'){

            /**
             * 3. When PointInOut by payout is successful(code=1) ---> If you receive rollback , 
             * refuse to roll back.
             */
            $additionalResponse['TimeoutBetReturnIncreaseAlreadyProcessed'] = false;
            if(!empty($pointInoutIncreaseToCancel)){
                $this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) #3", $this->request, 
                'pointInoutIncreaseToCancel', $pointInoutIncreaseToCancel, 
                'roundData', $roundData);
                $additionalResponse['TimeoutBetReturnIncreaseAlreadyProcessed'] = true;
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }else{
                /**
                 * 4. When PointInOut by payout is failed(code=1) ---> If you receive rollback , agree to rollback (retry the order , increase your customer balance).
                 */
            }

        }
            
        $params['bet_amount'] = $totalBetAmount;
        $params['result_amount'] = $totalResultAmount;


		if($amount<>0){

			//insert transaction
            if($mode=='debit'){
                $afterBalance = $previousBalance-$amount;
            }else{
                $afterBalance = $previousBalance+$amount;
            }
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) isAdded already", $isAdded);
				$isAlreadyExists = true;					
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}	

            if($mode=='debit'){
                $success = $this->CI->wallet_model->decSubWallet($player_id, $this->getPlatformCode(), $amount, $afterBalance);	
            }elseif($mode=='credit'){
                $success = $this->CI->wallet_model->incSubWallet($player_id, $this->getPlatformCode(), $amount, $afterBalance);
            }        

            # treat success if remote wallet return double uniqueid
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                $remoteErrorCode = $this->CI->wallet_model->getRemoteWalletErrorCode();
                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
                    $success = true;
                }    
            }

			if(!$success){
                $success = false;
				$this->utils->error_log("WM CASINO SEAMLESS: (debitCreditAmountToWallet) ERROR: debit/credit adjust balance", $this->request, 'success', $success);

                return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }
            
            $success = true;
		}else{
			//$get_balance = $this->queryPlayerBalanceByPlayerId($player_id);
            //$afterBalance = $previousBalance = $get_balance['balance'];
            $success = true;
            //insert transaction
            $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
		}	


        # set bet to cancel if TimeoutBetReturn and code 2 by dealid
        if($trans_type=='TimeoutBetReturnDecrease'){
            $updateData = ['status'=>Game_logs::STATUS_CANCELLED];
            $successUpdate = $this->CI->wm_casino_transactions->updateTransactionByKeyValue('dealid', $params['dealid'], $updateData, $tableName);
            if($checkOtherTable){
                $successUpdate = $this->CI->wm_casino_transactions->updateTransactionByKeyValue('dealid', $params['dealid'], $updateData, $prevTranstable);
            }
        }        
        
        # set bet to settle if TimeoutBetReturn and code 1 by dealid
        if($trans_type=='TimeoutBetReturnIncrease'){
            $updateDataArr = ['bet_amount'=>$totalBetAmount, 'result_amount'=>$totalResultAmount, 'status'=>Game_logs::STATUS_SETTLED];
            $updateWhereArr = ['round_id' => $params['round_id'], 'status' => Game_logs::STATUS_PENDING];
            $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($updateWhereArr, $updateDataArr, $tableName);
            if($checkOtherTable){
                $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($updateWhereArr, $updateDataArr, $prevTranstable);
            }

        }

        # update bet transactions for payout
        if($trans_type=='PointInoutIncrease'){
            $updateDataArr = ['bet_amount'=>$totalBetAmount, 'result_amount'=>$totalResultAmount, 'status'=>Game_logs::STATUS_SETTLED];
            $whereArr = ['round_id' => $params['round_id'], 'status' => Game_logs::STATUS_PENDING];
            $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($whereArr, $updateDataArr, $tableName);
            if($checkOtherTable){
                $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($whereArr, $updateDataArr, $prevTranstable);
            }

        }

        # update bet transactions for if bet and payout reset, update only settled
        if($trans_type=='PointInoutIncreaseLoseToWin'||$trans_type=='PointInoutDecreaseWinToLose'){
            $updateDataArr = ['bet_amount'=>$totalBetAmount, 'result_amount'=>$totalResultAmount];
            $whereArr = ['round_id' => $params['round_id'], 'status' => Game_logs::STATUS_SETTLED];
            $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($whereArr, $updateDataArr, $tableName);
            if($checkOtherTable){
                $successUpdate = $this->CI->wm_casino_transactions->updateTransactionArr($whereArr, $updateDataArr, $prevTranstable);
            }
        }

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						 
						$additionalResponse,
						$isTransactionAdded);
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$trans_record = $this->makeTransactionRecord($data);		
        $tableName = $this->getTransactionsTable();
        $this->CI->wm_casino_transactions->setTableName($tableName);        
		return $this->CI->wm_casino_transactions->insertIgnoreRow($trans_record);        		
	}

	public function makeTransactionRecord($raw_data){
		$data = [];		
        $data['user'] 			= isset($raw_data['user'])?$raw_data['user']:null;//string
		$data['cmd'] 			= isset($raw_data['cmd'])?$raw_data['cmd']:null;//string
		$data['money'] 			= isset($raw_data['money'])?$raw_data['money']:null;//string
		$data['amount'] 		= isset($raw_data['money'])?abs($raw_data['money']):null;//string
        $data['amount']         = isset($raw_data['amount'])?abs($raw_data['amount']):null;//string

		$data['request_date'] 	= isset($raw_data['request_date'])?$raw_data['request_date']:null;//datetime
		$data['dealid'] 	    = isset($raw_data['dealid'])?$raw_data['dealid']:null;//datetime
		$data['gtype'] 	        = isset($raw_data['gtype'])?$raw_data['gtype']:null;//datetime
		$data['type'] 	        = isset($raw_data['type'])?$raw_data['type']:null;//datetime
		$data['betdetail'] 	    = isset($raw_data['betdetail'])?$raw_data['betdetail']:null;//datetime
		$data['gameno'] 	    = isset($raw_data['gameno'])?$raw_data['gameno']:null;//datetime
		$data['code'] 	        = isset($raw_data['code'])?$raw_data['code']:null;//datetime
		$data['category'] 	    = isset($raw_data['category'])?$raw_data['category']:null;//datetime
		$data['game_platform_id'] 	= $this->getPlatformCode();		
		$data['bet_id'] 			= isset($raw_data['bet_id'])?$raw_data['bet_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string		
		$data['payout'] 			= isset($raw_data['payout'])?$raw_data['payout']:null;//string
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//string
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;//string
		$data['wallet_adjustment_mode'] = isset($raw_data['wallet_adjustment_mode'])?$raw_data['wallet_adjustment_mode']:null;//string
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;	
		//$data['status'] 			= $this->getTransactionStatus($data['trans_type'], $raw_data['wallet_adjustment_mode']);		
		$data['status'] 			= isset($raw_data['status'])?floatVal($raw_data['status']):0;		
		$data['payout'] 			= isset($raw_data['payout'])?$raw_data['payout']:null;//string
		$data['bet_amount'] 		= isset($raw_data['bet_amount'])?$raw_data['bet_amount']:0;//string
		$data['result_amount'] 		= isset($raw_data['result_amount'])?$raw_data['result_amount']:0;//string
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;	
		$data['external_uniqueid'] 	= $raw_data['external_uniqueid'];
        $data['raw_data'] 			= @json_encode($this->request);//text		   
        $data['bet_result'] 		= isset($raw_data['bet_result'])?$raw_data['bet_result']:null;//string		         

        $data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);
		return $data;
	}

    protected function getTotalBetAndResultAmount($amount, $params, $roundData){
        $totalBetAmount = 0;
        $totalWinAmount = 0;
        $totalResultAmount = 0;

        if($params['trans_type']=='PointInoutIncrease'||
        $params['trans_type']=='TimeoutBetReturnIncrease'||
        $params['trans_type']=='PointInoutIncreaseLoseToWin'){
            $totalWinAmount += abs($amount);
        }

        if($params['trans_type']=='PointInoutDecreaseWinToLose'){
            $totalWinAmount -= abs($amount);
        }

        if($params['trans_type']=='PointInoutDecrease'){
            $totalBetAmount += abs($amount);
        }

        foreach($roundData as $roundRow){
            # compute total amount
            if($roundRow['status']<>Game_logs::STATUS_CANCELLED){
                if(
                    $roundRow['trans_type']=='PointInoutIncrease'||
                    $params['trans_type']=='TimeoutBetReturnIncrease'
                ){
                    $totalWinAmount += abs($roundRow['amount']);
                }
                
                if($roundRow['trans_type']=='PointInoutDecrease'){
                    $totalBetAmount += abs($roundRow['amount']);
                }

            }
        }//end for each round

        $totalResultAmount = $totalWinAmount-$totalBetAmount;

        $this->utils->error_log("WM CASINO SEAMLESS:", 
            'totalResultAmount', $totalResultAmount,
            'totalWinAmount', $totalWinAmount,
            'totalBetAmount', $totalBetAmount,
            'params', $params,
            'roundData', $roundData
            );
        
        return [$totalBetAmount, $totalWinAmount, $totalResultAmount];
    }

	private function getTransactionStatus($transType, $mode){
		
		if($transType=='PointInoutIncrease'){
			return Game_logs::STATUS_SETTLED;
        }elseif($transType=='PointInoutDecrease'){
			return Game_logs::STATUS_PENDING;
		}elseif($transType=='TimeoutBetReturnDecrease'){
			return Game_logs::STATUS_REFUND;
		}elseif($transType=='TimeoutBetReturnIncrease'){
			return Game_logs::STATUS_SETTLED;
		}else{
			return Game_logs::STATUS_SETTLED;
		}
	}


    ############### SYNC GAME LOGS ###################

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;

        if ($this->enable_merging_rows) {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsFromTransMerge'],
                $enabled_game_logs_unsettle
            );
        } else {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                $enabled_game_logs_unsettle
            );
        }
    }

    /**
     * queryOriginalGameLogsFromTransMerge
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time) {
        $original_transactions_table = $this->getTransactionsTable();

        $currentTableData = $this->queryOriginalGameLogsWithTable($original_transactions_table, $dateFrom, $dateTo, $use_bet_time);        

        $this->CI->utils->debug_log("WM CASINO SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', $original_transactions_table);
        $prevTableData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();

        if($checkOtherTable||$this->force_check_other_transaction_table){            
            $prevTable = $this->CI->wm_casino_transactions->getTransactionsPreviousTable($this->original_transactions_table);             
            $this->CI->utils->debug_log("WM CASINO SEAMLESS: (queryOriginalGameLogs) tables used", 'original_transactions_table', 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        
        //$this->processGameRecordsFromTrans($gameRecords);
        return $gameRecords;
    }

    /*public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime=' AND `original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = ' AND `original`.`request_date` >= ? AND `original`.`request_date` <= ?';
        }
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.result_amount', 'original.status', 'original.updated_at', 'gd.game_name'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.updated_at,
    original.request_date start_at,
    original.player_id,
    original.round_id,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.bet_amount,
    original.result_amount,
    original.user as player_name,
    original.status,
    original.wallet_adjustment_mode,
    original.gtype as game,
    original.bet_result,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.gtype = gd.external_game_id AND gd.game_platform_id = ?
WHERE (original.trans_type='PointInoutDecrease')
{$sqlTime};
EOD;

#WHERE (original.trans_type='PointInoutIncrease' OR original.trans_type='TimeoutBetReturnDecrease')

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('WM CASINO SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }*/

    public function queryOriginalGameLogsWithTable($table, $dateFrom, $dateTo, $use_bet_time){
        $sqlTime=' AND `original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = ' AND `original`.`request_date` >= ? AND `original`.`request_date` <= ?';
        }
        $this->CI->utils->debug_log('PGSOFT SEAMLESS GAME sqlTime', $sqlTime);
        $md5Fields = implode(", ", array('original.bet_amount', 'original.result_amount', 'original.status', 'original.updated_at', 'gd.game_name'));
        //result amount = win - bet
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.updated_at,
    original.request_date start_at,
    game_provider_auth.player_id,
    original.round_id,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.bet_amount,
    original.result_amount,
    original.user as player_name,
    original.status,
    original.wallet_adjustment_mode,
    original.gtype as game,
    original.bet_result,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$table} as original
LEFT JOIN game_description as gd ON original.gtype = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth ON original.user = game_provider_auth.login_name AND game_provider_auth.game_provider_id = ?
WHERE (original.trans_type='SendMemberReport')
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('WM CASINO SEAMLESS GAME (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row) {
        $this->CI->utils->debug_log('WM CASINO SEAMLESS GAME (makeParamsForInsertOrUpdateGameLogsRow)', 'row', $row);
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $betresult = json_decode( $row['bet_result'], true);
        $betAmount = $row['bet_amount'];
        if(isset($betresult['validbet'])&&$betresult['validbet']>0){
            $betAmount = $betresult['validbet'];
            $table = $this->getTransactionsTable();

            $betresult['reserve'] = $this->queryReserveAmount($table, 'PointInoutDecrease', $row['round_id']);
            $checkOtherTable = $this->checkOtherTransactionTable();

            if ($checkOtherTable || $this->force_check_other_transaction_table) {
                if (empty($betresult['reserve'])) {
                    $prevTable = $this->CI->wm_casino_transactions->getTransactionsPreviousTable($this->original_transactions_table);
                    $betresult['reserve'] = $this->queryReserveAmount($prevTable, 'PointInoutDecrease', $row['round_id']);
                }
            }
        }

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_name']
            ],
            'amount_info' => [
                'bet_amount'            => $betAmount,
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $betAmount,
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => $this->gameTimeToServerTime($row['start_at']),
                'end_at'                => $row['updated_at'],
                'bet_at'                => $this->gameTimeToServerTime($row['start_at']),
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null,
            ],
            'bet_details' => $betresult,
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('PGSOFT ', $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogsFromTransMerge(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if(isset($row['bet_type'])){
            $row['bet_type'] = $row['bet_type'] == self::BET_TYPE_REAL_CODE ? self::BET_TYPE_REAL : '';
        }
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }


    public function queryTransactionByDateTime($startDate, $endDate){
        $date = new DateTime($startDate);
        $monthStr = $date->format('Ym');
        $transactionTable = $this->getTransactionsTable();
        $currentTableData = $this->queryTransactionByDateTimeGetData($transactionTable, $startDate, $endDate);

        $prevTableData = $finalData = [];

        $checkOtherTable = $this->checkOtherTransactionTable();
        if(($this->force_check_other_transaction_table&&$this->use_monthly_transactions_table) || $checkOtherTable){
            $prevTable = $this->CI->wm_casino_transactions->getTransactionsPreviousTable($this->original_transactions_table); 
            $prevTableData = $this->queryTransactionByDateTimeGetData($prevTable, $startDate, $endDate);                   
        }
        $finalData = array_merge($currentTableData, $prevTableData);        
        
        return $finalData;
    }



    public function queryTransactionByDateTimeGetData($table, $startDate, $endDate){
        
$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type,
t.wallet_adjustment_mode wallet_adjustment_mode,
t.raw_data extra_info,
t.gtype game_code
FROM {$table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
and t.trans_type<>'SendMemberReport'
ORDER BY t.updated_at asc;

EOD;
        
        $params=[$this->getPlatformCode(),$startDate, $endDate];
        
                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
        
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = array();
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [
                    'wager_id'=>$transaction['round_id'], 
                    'game_code'=>$transaction['game_code']
                ];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['wallet_adjustment_mode'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function queryReserveAmount($table, $transaction_type, $round_id) {
        return $this->CI->original_game_logs_model->getSpecificColumn($table, 'amount', ['trans_type' => $transaction_type, 'round_id'=> $round_id]);
    }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time)
    {
        $original_transactions_table = $this->getTransactionsTable();

        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.request_date BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
game_description.game_type_id,
game_description.id AS game_description_id,
transaction.gtype,
game_description.english_name AS game,

transaction.player_id,
transaction.user AS player_username,

transaction.amount as bet_amount,
transaction.amount as result_amount,
transaction.after_balance,

transaction.request_date as start_at,
transaction.updated_at,

transaction.status,
transaction.external_uniqueid,
transaction.round_id,
MD5(CONCAT({$md5Fields})) AS md5_sum,
transaction.response_result_id,
transaction.id as sync_index,

transaction.code

FROM
    {$original_transactions_table} as transaction
    LEFT JOIN game_description ON transaction.gtype = game_description.external_game_id AND game_description.game_platform_id = ?

WHERE
    transaction.game_platform_id = ? 
    AND {$sqlTime}
    AND transaction.code IS NOT NULL 
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['gtype'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $this->gameTimeToServerTime($row['start_at']),
                'end_at'                => $row['updated_at'],
                'bet_at'                => $this->gameTimeToServerTime($row['start_at']),
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set bet and result amount
        if ($row['code'] == 2) { #bet
            $row['bet_amount'] = abs($row['bet_amount']);
            $row['result_amount'] = -$row['result_amount'];
        }elseif ($row['code'] == 1) { #settle
            $row['bet_amount'] = 0;
            $row['result_amount'] = $row['result_amount'];
        }
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model', 'wm_casino_transactions'));
        $transactionsTable = $this->getTransactionsTable();
        $bet_code = Wm_casino_transactions::CODE_POINT_DECREASE;

        $sql = <<<EOD
SELECT 
original.round_id, original.external_uniqueid, game_platform_id
from {$transactionsTable} as original
where
original.status=?
and original.code=?
and {$sqlTime}
EOD;


        $params=[
            Game_logs::STATUS_PENDING,
            Wm_casino_transactions::CODE_POINT_DECREASE,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('WM_SEAMLESS_GAME_API-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $transactionsTable = $this->getTransactionsTable();
        $roundId = $data['round_id'];
        $external_uniqueid = $data['external_uniqueid'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.trans_type as transaction_type,
original.status,
original.game_platform_id,
original.player_id,
original.round_id,
original.dealid as transaction_id,
ABS(SUM(original.amount)) as amount,
ABS(SUM(original.amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_uniqueid
from {$transactionsTable} as original
left JOIN game_description as gd ON original.gtype = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and external_uniqueid=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $external_uniqueid, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('WM_SEAMLESS_GAME_API-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $transactionsTable = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
status
FROM {$transactionsTable}
WHERE
game_platform_id=? AND external_uniqueid=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }

}//end of class