<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Generate Soap Method
 * * Prepares Data below
 * * * Currency for Create Account
 * * * Profile List Id
 * * * Currency for Deposit
 * * * My Balance
 * * Create Player
 * * Login/Logout
 * * Deposit To game
 * * Withdraw from Game
 * * Change Password
 * * Check Player Balance
 * * Check Transaction
 * * Check Game records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Authenticate Soap
 * * Make Soap Options
 * * Check if Player Exist
 * * Check Player Information
 * * Block/Unblock Player
 * * Check Player Daily Balance
 * * Check login Status
 * * Check Total Betting Amount
 *
 *
 * @see Redirect redirect to game page
 * @document name PURSE ADVANCED INTEGRATION
 * @api version 5.9
 * @category Game API
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_isb_seamless extends Abstract_game_api {

	const MD5_FIELDS_FOR_ORIGINAL= [
        'username',
        'transactionid',
        'roundid',
        'amount',
        'result_amount',
        'jpc',
        'froundid',
        'fround_coin_value',
        'fround_lines',
        'fround_line_bet',
        'timestamp',
        'closeround',
        'sessionid',
        'command',
        'skinid',
        'before_balance',
        'after_balance'
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'result_amount',
        'before_balance',
        'after_balance'
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount'
    ];

	public function __construct() {
		parent::__construct();
		$this->CI->load->model(array('original_game_logs_model'));
		$this->currency = $this->getSystemInfo('currency','KRW');
		$this->countryCode = $this->getSystemInfo('countryCode','KR');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->api_url = $this->getSystemInfo('url');
		$this->secret_key = $this->getSystemInfo('secret');
		$this->api_license_id = $this->getSystemInfo('api_license_id');
		$this->operator_name = $this->getSystemInfo('operator_name');
		$this->key = $this->getSystemInfo('key', 'tripleonetech');
        $this->isb_game_lobby_url = $this->getSystemInfo('isb_game_lobby_url');
        $this->table_id = $this->getSystemInfo('table_id');

	}

    public function isSeamLessGame(){
        return true;
    }

	public function getPlatformCode() {
		return ISB_SEAMLESS_API;
	}

	const AUTO_REDIRECT = 0;

	public function getLanguage($currentLang = null) {
		if(!empty($currentLang)){
			switch ($currentLang) {
	            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
	                $language = 'zh';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
	                $language = 'id';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
	                $language = 'vi';
	                break;
	            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
	                $language = 'ko';
	                break;
	            default:
	                $language = 'en';
	                break;
	        }
	        return $language;
		}
        return $this->language;
	}

	public function generateHMAC($params){
		return hash_hmac('SHA256',$params,$this->secret_key);
	}

	public function getCountryCode() {
		return $this->countryCode;
	}

	public function getCurrency() {
		return $this->currency;
	}

	public function generateUrl($apiName, $params) {
		return $this->api_url;
	}

	public function getHttpHeaders($params){
		$headers = array(
			"API" => $params['method'],
			"DataType" => "JSON"
		);

		return $headers;
	}

	protected function customHttpCall($ch, $params) {
		unset($params["method"]); //unset action not need on params
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
  		//curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

	}

	public function createPlayer($userName, $playerId, $password, $email = null, $extra = null) {
		// create player on game provider auth
		$return = parent::createPlayer($userName, $playerId, $password, $email, $extra);
		$success = false;
		$message = "Unable to create Account for ISB SEAMLESS";
		if($return){
			$success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$message = "Successfull create account for ISB SEAMLESS";
		}

		return array("success"=>$success,"message"=>$message);
	}

	// public function changePassword($playerName, $oldPassword = null, $newPassword) {
	// 	return $this->returnUnimplemented();
	// }

	// public function isPlayerExist($userName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($userName);
	// 	$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
 //        $result = true;
 //        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	// 	return array('success'=>true, 'exists'=>$result);
	// }

	// public function queryPlayerBalance($userName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($userName);
 //        $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
	// 	$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

	// 	$result = array(
	// 		'success' => true,
	// 		'balance' => $balance
	// 	);

	// 	return $result;
	// }

	public function depositToGame($userName, $amount, $transfer_secure_id=null){

        // $external_transaction_id = $transfer_secure_id;

		// $player_id = $this->getPlayerIdFromUsername($userName);
		// $playerBalance = $this->queryPlayerBalance($userName);
		// $afterBalance = @$playerBalance['balance'];
		// if(empty($transfer_secure_id)){
		// 	$external_transaction_id = $this->utils->getTimestampNow();
		// }

		// $transaction = $this->insertTransactionToGameLogs($player_id, $userName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());

		return array(
			'success' => true,
			'external_transaction_id' => $transfer_secure_id,
			'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
		);
	}

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {

  //       $external_transaction_id = $transfer_secure_id;

  //   	$player_id = $this->getPlayerIdFromUsername($userName);
		// $playerBalance = $this->queryPlayerBalance($userName);
		// $afterBalance = @$playerBalance['balance'];
		// if(empty($transfer_secure_id)){
		// 	$external_transaction_id = $this->utils->getTimestampNow();
		// }
		// $this->insertTransactionToGameLogs($player_id, $userName, $afterBalance, $amount, NULL,$this->transTypeSubWalletToMainWallet());
		return array(
			'success' => true,
			'external_transaction_id' => $transfer_secure_id,
			'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
		);
    }

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'cn':
            case 'CN':
            case 'zh-cn':
            case "Chinese":
                $lang = 'chs';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id':
            case 'ID':
            case 'id-id':
            case "Indonesian":
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'VI':
            case 'vi-vn':
            case "Vietnamese":
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case 'kr':
            case 'KR':
            case 'ko-kr':
            case "Korean":
                $lang = 'kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th':
            case 'TH':
            case 'th-th':
            case "thai":
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName, $param) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getPlayerTokenByUsername($playerName);
        $url = null;

        if(!$this->utils->getConfig('disabled_multiple_database')){
        	$gameUsername=$playerName;
        	$hashToken=[];
        	$hashToken=array(
        		'currency'=>$this->getCurrency(),
        		'api'=>$this->getPlatformCode(),
        		'token'=>$token
        	);
        	ksort($hashToken);
        	$hashToken['signature']=md5(json_encode($hashToken).$this->key);
        	$token=base64_encode(json_encode($hashToken));
        }

        $language = $param['language'];
		$language = $this->getLauncherLanguage($param['language']);

        if (!empty($token)) {
            $game_mode = $param['game_mode'] == 'real' ? 1 : 0;
            $url = $this->game_url . "/" . $this->api_license_id . "/" . $param['game_code'] . "?lang=" . $language . '&cur=' . $this->currency . '&mode=' . $game_mode . '&user=' . $gameUsername . '&uid=' . $gameUsername . '&token=' . $token . '&operator=' . $this->operator_name;

            if (isset($param['extra']['is_mobile_flag']) && $param['extra']['is_mobile_flag'] || isset($param['home_link']) && !empty($param['home_link'])) {
                $lobbyUrl = isset($this->isb_game_lobby_url) && !empty($this->isb_game_lobby_url) ? $this->getSystemInfo('isb_game_lobby_url') : $param['home_link'];
                $url .= "&lobbyURL=" . $lobbyUrl;
            }

            if(isset($param['game_type']) && $param['game_type'] == 'table_games') {
                if(isset($param['extra']['table_id']) && !empty($param['extra']['table_id'])) {
                    $url .= "&table=" . $param['extra']['table_id'];
                } else {
                    if(isset($this->table_id) && !empty($this->table_id)) {
                        $url .= "&table=" . $this->table_id;
                    }
                }
            }
        }

        return array(
            'success' => true,
            'url' => $url,
            'iframeName' => "ISB API",
        );
    }


	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $gameRecords = $this->queryTransactions($startDate, $endDate);
        if(!empty($gameRecords)){
            $this->processGameRecords($gameRecords);
            $oldCnt=count($gameRecords);
            $this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'roundid', function() {
                return 2;
            });
            $cnt=count($gameRecords);
            $this->CI->utils->debug_log('removeDuplicateUniqueid oldCnt:'.$oldCnt.', cnt:'.$cnt);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'roundid',
                'roundid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }
        return array('success'=>true, 'result'=>$dataResult);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    unset($record['external_uniqueid']);
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function processGameRecords(&$gameRecords) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['transactionid'] = isset($record['transactionid']) ? $record['transactionid'] : null;
                $data['roundid'] = isset($record['roundid']) ? $record['roundid'] : $this->getCurrency();
                $data['amount'] = isset($record['amount']) ? $record['amount'] : 0;
                $data['result_amount'] = -$data['amount'];
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['jpc'] = isset($record['jpc']) ? $record['jpc'] : null;
                $data['froundid'] = isset($record['froundid']) ? $record['froundid'] : null;
                $data['fround_coin_value'] = isset($record['fround_coin_value']) ? $record['fround_coin_value'] : null;
                $data['fround_lines'] = isset($record['fround_lines']) ? $record['fround_lines'] : null;
                $data['fround_line_bet'] = isset($record['fround_line_bet']) ? $record['fround_line_bet'] : null;
                $data['timestamp'] = isset($record['timestamp']) ? $record['timestamp'] : null;
                $data['closeround'] = isset($record['closeround']) ? $record['closeround'] : null;
                $data['jpw'] = isset($record['jpw']) ? $record['jpw'] : null;
                $data['jpw_from_jpc'] = isset($record['jpw_from_jpc']) ? $record['jpw_from_jpc'] : null;
                $data['command'] = isset($record['command']) ? $record['command'] : null;
                $data['skinid'] = isset($record['skinid']) ? $record['skinid'] : null;
                $data['start_at'] = isset($record['created_at']) ? $record['created_at'] : null;
                $data['end_at'] = isset($record['updated_at']) ? $record['updated_at'] : $record['created_at'];
                $data['sessionid'] = isset($record['sessionid']) ? $record['sessionid'] : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                $data['transaction_status'] = isset($record['transaction_status']) ? $record['transaction_status'] : null;

                $betResults = $this->queryResultTransactions($data['roundid']);
                $winResult = $this->queryResultTransactionsWin($data['roundid']);

                if(!empty($winResult)) {
                    if(count($winResult) > 1) {
                        $totalFreeSpin=0;
                        foreach ($winResult as $result) {
                            $totalFreeSpin += $result['amount'];
                        }
                        $data['result_amount'] = $totalFreeSpin - $data['amount'];
                        $after_balance = end($winResult);
                        $data['after_balance'] = $after_balance['after_balance'];
                    } else {
                        $result = $winResult[0];
                        if(count($betResults) > 1) {
                            $totalBets=0;
                            foreach ($betResults as $betResult) {
                                $totalBets += $betResult['amount'];
                            }
                            $data['amount'] = $totalBets;
                        }
                        if($result['command'] == 'cancel' || $result['transaction_status'] == 'cancelled') {
                            $data['result_amount'] = $result['amount'];
                        }
                        if($result['amount'] >= 0) {
                            $data['result_amount'] = isset($result['amount']) ? $result['amount'] - $data['amount'] : 0;
                            $data['after_balance'] = isset($result['after_balance']) ? $result['after_balance'] : null;
                        }
                    }
                } else {
                    $totalBets=0;
                    $totalBetsAmount=0;
                    $resultAmount=0;
                    if(count($betResults) > 1) {
                        foreach ($betResults as $betResult) {
                            $totalBets += $betResult['amount'];
                        }
                        $totalBetsAmount = $totalBets;
                        $resultAmount = $totalBets;
                        $getLastBet = end($betResults);
                        $getFirstBet = reset($betResults);
                        $before_balance = $getFirstBet['before_balance'];
                        $after_balance = $getLastBet['after_balance'];
                    }
                    $data['amount'] = isset($betResults[0]['amount']) && count($betResults) < 2 ? $betResults[0]['amount'] : $totalBetsAmount;
                    $data['result_amount'] = isset($betResults[0]['amount']) && count($betResults) < 2 ? -$betResults[0]['amount'] : -$resultAmount;
                    $data['after_balance'] = isset($betResults[0]['after_balance']) && count($betResults) < 2 ? $betResults[0]['after_balance'] : $after_balance;
                    $data['before_balance'] = isset($betResults[0]['before_balance']) && count($betResults) < 2 ? $betResults[0]['before_balance'] : $before_balance;
                }

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactions($dateFrom, $dateTo){
        $sqlTime='sg.updated_at >= ? and sg.updated_at <= ? and command = "bet"';
        $sql = <<<EOD
SELECT
sg.id as sync_index,
sg.username,
sg.transactionid,
sg.roundid,
sg.amount,
sg.before_balance,
sg.after_balance,
sg.jpc,
sg.froundid,
sg.fround_coin_value,
sg.fround_lines,
sg.fround_line_bet,
sg.timestamp,
sg.closeround,
sg.jpw,
sg.jpw_from_jpc,
sg.command,
sg.sessionid,
sg.skinid,
sg.external_uniqueid,
sg.response_result_id,
sg.created_at,
sg.updated_at,
sg.transaction_status

FROM {$this->transaction_table_name} as sg
WHERE

{$sqlTime}

EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

        /**
     * queryResultTransactions
     * @param  string $roundid
     * @return array
     */
    public function queryResultTransactions($roundId){
        $sqlTime='sg.roundid = ? and command="bet"';
        $sql = <<<EOD
SELECT
sg.id as sync_index,
sg.username,
sg.transactionid,
sg.roundid,
sg.amount,
sg.before_balance,
sg.after_balance,
sg.jpc,
sg.froundid,
sg.fround_coin_value,
sg.fround_lines,
sg.fround_line_bet,
sg.timestamp,
sg.closeround,
sg.jpw,
sg.jpw_from_jpc,
sg.command,
sg.sessionid,
sg.skinid,
sg.external_uniqueid,
sg.response_result_id,
sg.created_at,
sg.updated_at,
sg.transaction_status

FROM {$this->transaction_table_name} as sg
WHERE

{$sqlTime}

EOD;

        $params=[$roundId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

        /**
     * queryResultTransactions
     * @param  string $roundid
     * @return array
     */
    public function queryResultTransactionsWin($roundId){
        $sqlTime='sg.roundid = ? and command in ("win", "cancelled")';
        $sql = <<<EOD
SELECT
sg.id as sync_index,
sg.username,
sg.transactionid,
sg.roundid,
sg.amount,
sg.before_balance,
sg.after_balance,
sg.jpc,
sg.froundid,
sg.fround_coin_value,
sg.fround_lines,
sg.fround_line_bet,
sg.timestamp,
sg.closeround,
sg.jpw,
sg.jpw_from_jpc,
sg.command,
sg.sessionid,
sg.skinid,
sg.transaction_status,
sg.external_uniqueid,
sg.response_result_id,
sg.created_at,
sg.updated_at,
sg.transaction_status

FROM {$this->transaction_table_name} as sg
WHERE

{$sqlTime}

EOD;

        $params=[$roundId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }


	public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /** queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='sg.end_at >= ? AND sg.end_at <= ?';

        $sql = <<<EOD
SELECT
sg.id as sync_index,
sg.response_result_id,
sg.external_uniqueid,
sg.md5_sum,

sg.username as player_username,
sg.transactionid,
sg.roundid as round_number,
sg.amount as bet_amount,
sg.amount as real_betting_amount,
sg.result_amount,
sg.before_balance,
sg.after_balance,
sg.jpc,
sg.froundid,
sg.fround_coin_value,
sg.fround_lines,
sg.fround_line_bet,
sg.timestamp,
sg.closeround,
sg.jpw,
sg.jpw_from_jpc,
sg.command,
sg.sessionid,
sg.skinid as game_code,
sg.skinid as game_name,
sg.start_at,
sg.start_at as bet_at,
sg.end_at,
sg.created_at,
sg.updated_at,
sg.transaction_status,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id,
game_provider_auth.login_name as player_username

FROM $this->original_gamelogs_table as sg
LEFT JOIN game_description as gd ON sg.skinid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sg.username = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $status = $this->checkBetStatus($row['transaction_status']);
        $row['status'] = $status;
    }

    private function checkBetStatus($status) {
        switch ($status) {
            case 'cancelled':
                $trans_status = Game_logs::STATUS_CANCELLED;
                break;
            
            default:
                $trans_status = Game_logs::STATUS_SETTLED;
                break;
        }
        return $trans_status;
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

	// public function blockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->blockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }

	// public function unblockPlayer($playerName) {
	// 	$playerName = $this->getGameUsernameByPlayerUsername($playerName);
	// 	$success = $this->unblockUsernameInDB($playerName);
	// 	return array("success" => true);
	// }

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/