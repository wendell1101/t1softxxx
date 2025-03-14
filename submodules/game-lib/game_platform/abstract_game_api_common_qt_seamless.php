<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: QTech (AGGREGATOR)
* Wallet Type: Seamless
*
/**
* API NAME: Qtech
* @Controller: qt_seamless_service_api.php
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @renz.php.ph
**/

abstract class Abstract_game_api_common_qt_seamless extends Abstract_game_api {

    const ORIGINAL_GAMELOGS_TABLE = '';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

	private $api_url;
	private $api_key;
	private $api_secret;
	private $access_token;
	private $transferId;
	private $isMethod;
	private $gameCodeForGameLaunch;
	private $currency;
    private $enable_merging_rows;

	const POST_METHOD = 1;
	const PUT_METHOD = 2;
	const GET_METHOD = 3;
	const DELETE_METHOD = 4;
	const FLAG_TRUE = 1;
	const FLAG_FALSE = 0;
	const API_syncNextGameRecords = "next";

    const DEBIT = "debit";
    const CREDIT = "credit";
    const ROLLBACK = "rollback";
    const BONUSREWARDS = "bonus-rewards";



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
        'result_amount',
        'after_balance',
        'before_balance'
    ];

	const URI_MAP = array(
		self::API_login => '/v1/auth/token',
		self::API_queryForwardGame => '/v1/games',
		self::API_checkLoginToken => '/v1/auth/token',
		self::API_getGameProviderGamelist => '/v2/games',
	);

    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    public function getCurrency() {
        return $this->currency;
    }
	## --------------------------------------------------------------------------------

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
		$this->qt_username = $this->getSystemInfo('qt_username');
		$this->qt_password = $this->getSystemInfo('qt_password');
		$this->qt_return_url = $this->getSystemInfo('qt_return_url');
		// $this->currency = $this->getSystemInfo('currency', 'CNY');
        $this->currency = $this->getSystemInfo('currency', 'THB');
        $this->country = $this->getSystemInfo('country','TH');
        $this->go_back_www = $this->getSystemInfo('go_back_www', true);
        $this->gamelist_include_fields = $this->getSystemInfo('gamelist_include_fields', "id,name,category,supportedDevices");
        $this->enabled_ip_checking = $this->getSystemInfo('enabled_ip_checking', false);
        $this->lang = $this->getSystemInfo('lang','en_US');
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    public function isSeamLessGame()
    {
       return true;
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for HACKSAW QT";
        if($return){
            $success = true;
            // $this->setGameAccountRegistered($playerId);
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfully created account for HACKSAW QT";
        }

        return array("success" => $success, "message" => $message);
    }

    public function getAccessToken() {
		$this->isMethod = self::POST_METHOD;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultGetAccessToken',
		);
		$params = array(
			"grant_type" => "password",
			"response_type" => "token",
			"username" => $this->qt_username,
			"password" => $this->qt_password,
		);


		return $this->callApi(self::API_login, http_build_query($params), $context);
	}

	public function processResultGetAccessToken($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$success = false;
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$result = array();
		if ($success) {
			$this->access_token = $resultJson['access_token'];
		}
		return array($success, $resultJson);
	}

    public function queryForwardGame($playerName, $extra) {
    	if($this->enabled_ip_checking){
    		$ip = $this->CI->utils->getIP();
	    	$isoCode = $this->CI->utils->getIpIsoCode($ip);
	    	if(!empty($isoCode)){
	    		$this->country = $isoCode;
	    	}
    	}
		$this->isMethod = self::POST_METHOD;
		$result = $this->getAccessToken();
        $this->CI->utils->debug_log('gameLauncher token: ', $result);
		$this->gameCodeForGameLaunch = $extra['game_code'];
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultQueryForwardGame',
			'playerName' => $playerName,
		);

        $go_back_url=$this->qt_return_url;
        if($this->go_back_www){
        	if(isset($extra['is_mobile']) && $extra['is_mobile']){
        		$go_back_url=$this->CI->utils->getSystemUrl('m');
        	}else{
        		$go_back_url=$this->CI->utils->getSystemUrl('www');
        	}
        }

        $language = $this->force_lang ? $this->lang : $this->getLauncherLanguage($this->getSystemInfo('language', $extra['language']));

        if (isset($extra['home_link'])) {
            $go_back_url = $extra['home_link'];
        }

        if (isset($extra['extra']['home_link'])) {
            $go_back_url = $extra['extra']['home_link'];
        }

        if($extra['game_mode'] != 'real'){
            $params = array(
                "currency" => $this->currency,
                "lang" => $language,
                "mode" => 'demo',
                "device" => $extra['is_mobile'] ? 'mobile' : 'desktop', //Note: This is deprecated already
                "returnUrl" => $go_back_url,
            );
            $this->CI->utils->debug_log('QT gameLauncher params >----------------------------------------------------> ', $params);
            return $this->callApi(self::API_queryForwardGame, json_encode($params), $context);

        } else {
			if (isset($result['access_token'])) {
				$playerName = $this->getGameUsernameByPlayerUsername($playerName);
				$this->CI->utils->debug_log('gameLauncher playerName: ', $playerName);

                $token = $this->getPlayerTokenByGameUsername($playerName);

	            $params = array(
	                "playerId" => $playerName,
	                "currency" => $this->currency,
	                "country" => $this->country,
	                "lang" => $this->getLauncherLanguage($extra['language']),
	                "mode" => $extra['game_mode'],
	                "device" => (isset($extra['is_mobile']) && $extra['is_mobile']) ? 'mobile' : 'desktop', //Note: This is deprecated already
	                "returnUrl" => $go_back_url,
                    "walletSessionId" => $token
	            );
				$this->CI->utils->debug_log('gameLauncher params: ', $params);
				return $this->callApi(self::API_queryForwardGame, json_encode($params), $context);
			}else{
				return array("success" => false, []);
			}
		}
	}

	public function processResultQueryForwardGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson);
		$this->CI->utils->debug_log('processResultLaunchGame resultJson: ', $resultJson);
		$result = array();
		if ($success && isset($resultJson['url'])) {
			$this->CI->utils->debug_log('Launch Game: ', 'playerName', $playerName, 'balance', @$resultJson['url']);
			$result['url'] = $resultJson['url'];
			$result['iframeName'] = 'QT_API';
		} else {
			$success = false;
		}

		return array($success, $result);
	}

    protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($this->isMethod == self::POST_METHOD) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		} elseif ($this->isMethod == self::PUT_METHOD) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		} elseif ($this->isMethod == self::DELETE_METHOD) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
	}

    function forwardCallback($url, $params) {
		$this->CI->utils->debug_log('forwardCallback =============  ', $url, $params);
		$data = json_encode($params);
		$ch = curl_init( $url );
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$response = curl_exec( $ch );
	 	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 	$errCode = curl_errno($ch);
        $error = curl_error($ch);
        $this->CI->utils->debug_log('forwardCallback result=============  ', $response , $statusCode, $errCode , $error);
		return array('header' => json_decode($response, true),'status' => $statusCode);
	}

    public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
		$success = !empty($resultJson);
		if ($this->CI->utils->notEmptyInArray('errorcode', $resultJson) || $this->CI->utils->notEmptyInArray('error', $resultJson)) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('QT got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
			$success = false;
		}

		return $success;
	}

    public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];

		if ($apiName == self::API_login || $apiName == self::API_syncGameRecords) {
			$url = $this->api_url . $apiUri . '?' . $params;
		} elseif ($apiName == self::API_queryTransaction) {
			$url = $this->api_url . $apiUri . '/' . $this->transferId . '/status';
		} elseif ($apiName == self::API_queryPlayerBalance) {
			$url = $this->api_url . $apiUri . '/' . $params['playerId'];
		} elseif ($apiName == self::API_queryForwardGame) {
			$url = $this->api_url . $apiUri . '/' . $this->gameCodeForGameLaunch . '/launch-url';
		} elseif ($apiName == self::API_syncNextGameRecords) {
			$url = $this->api_url . $params;
		} elseif ($apiName == self::API_queryBetDetailLink) {
			$url = $this->api_url . $apiUri . '/' . $this->playerName . '/service-url';
		} elseif ($apiName == self::API_getGameProviderGamelist) {
			$url = $this->api_url . $apiUri . '?' . http_build_query($params);
		} else {
			$url = $this->api_url . $apiUri;
		}

		return $url;
	}

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;

        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle);
    }

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){

        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);

        $rebuildGameRecords = array();

        if ($this->enable_merging_rows) {
            $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);
        } else {
            $rebuildGameRecords = $gameRecords;
        }
        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo, $use_bet_time) {
        if ($this->enable_merging_rows) {
            $transaction_type_in = "qt.transaction_type IN ('debit', 'bonus-rewards')";
        } else {
            $transaction_type_in = "qt.transaction_type IN ('debit', 'credit', 'rollback', 'bonus-rewards')";
        }

        #query bet only
        $sqlTime="qt.updated_at >= ? AND qt.updated_at <= ? AND qt.game_platform_id = ? AND {$transaction_type_in}";

        if($use_bet_time) {
            $sqlTime="qt.start_at >= ? AND qt.start_at <= ? AND qt.game_platform_id = ? AND {$transaction_type_in}";
        }

        $sql = <<<EOD
SELECT
qt.id as sync_index,
qt.response_result_id,
qt.transaction_id,
qt.external_unique_id as external_uniqueid,
qt.md5_sum,
game_provider_auth.login_name as player_username,
qt.player_id,
qt.game_platform_id,
IF(qt.transaction_type="debit" OR qt.transaction_type="bonus-rewards", qt.amount, 0) as bet_amount,
IF(qt.transaction_type="debit" OR qt.transaction_type="bonus-rewards", qt.amount, 0) as real_betting_amount,
qt.amount as result_amount,
qt.amount,
qt.game_id as game_code,
qt.game_id as game_name,
qt.transaction_type,
qt.status,
qt.round_id as round_number,
qt.extra_info,
qt.start_at,
qt.start_at as bet_at,
qt.end_at,
qt.before_balance,
qt.after_balance,
qt.transaction_type,
qt.transaction_id,
gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as qt
LEFT JOIN game_description as gd ON qt.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth
ON
	qt.player_id = game_provider_auth.player_id AND
	game_provider_auth.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;

    }

    public function processGameRecordsFromTrans(&$gameRecords, &$rebuildGameRecords) {

        $roundIds = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {


                $transaction_type = $record['transaction_type'];


                if($transaction_type == SELF::BONUSREWARDS) {
                    $round_username = $record["transaction_id"];
                } else {


                    $round_username = $record["round_number"] . "-" . $record['player_username'];

                }

                if(!in_array($round_username, $roundIds)) {

                    $temp_game_records = $record;
                    $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                    $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                    $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;

                    $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                    $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : $round_username;
                    $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                    $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                    $temp_game_records['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                    $temp_game_records['bet_at'] = isset($record['bet_at']) ? $record['bet_at'] : null;
                    $temp_game_records['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                    // $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null; //
                    // $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;

                    // before_balance and after_balance because bet and openWin (result) is in separate transaction. the result is depend on the draw time.
                    // once we get the transaction, we combine the bet and openWin upon merged.

                    //$temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;

                    if($transaction_type == SELF::BONUSREWARDS) {

                        $temp_game_records['bet_amount']            = 0;
                        $temp_game_records['real_betting_amount']   = 0;
                        $temp_game_records['result_amount']         = $record["amount"];
                        $temp_game_records['status']                = Game_logs::STATUS_SETTLED;

                        $ex_info_arr = json_decode($record["extra_info"], true);

                        $temp_game_records['bet_details']           = array("Reward Type" => $ex_info_arr["RewardType"],
                                                                            "Reward Title" => $ex_info_arr["RewardTitle"],
                                                                            "Transaction Type" => $transaction_type
                                                                            );

                    } else {

                        $temp_game_records['bet_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;
                        $temp_game_records['real_betting_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;

                        $game_result = $this->queryBetResult($record['round_number'],$record['player_id']);

                        if($game_result) {

                            $temp_game_records['bet_amount']            = $game_result["bet_amount"];
                            $temp_game_records['real_betting_amount']   = $game_result["bet_amount"];
                            $temp_game_records['result_amount']         = $game_result['result_amount'] - $game_result["bet_amount"];
                            $temp_game_records['after_balance']         = $game_result["after_balance"];

                            if($game_result["settled"]){

                                $temp_game_records['status'] = Game_logs::STATUS_SETTLED;

                            } else {

                                $temp_game_records['status'] = Game_logs::STATUS_PENDING;

                            }
                        }

                        $temp_game_records['bet_details'] = $game_result["bet_details"];

                        if(!empty($game_result["end_at"])) {
                            $temp_game_records['end_at'] =  $game_result["end_at"];
                        }

                    }




                    $roundIds[] = $round_username;
                    // $gameRecords[$index] = $temp_game_records;
                    $rebuildGameRecords[] = $temp_game_records;
                    unset($data);

                }

            }

        }

    }

    private function queryBetResult($round_id, $playerId) {

        $sqlTime='qt.round_id = ? and qt.game_platform_id = ? and qt.player_id = ? and qt.status != "cancelled" and qt.transaction_type != "rollback" and qt.transaction_type != "bonus-rewards"';

        $sql = <<<EOD
SELECT
qt.id as sync_index,
qt.amount,
qt.end_at,
qt.after_balance,
qt.transaction_type,
qt.extra_info,
qt.game_platform_id,
qt.transaction_id
FROM common_seamless_wallet_transactions as qt
WHERE
{$sqlTime}
EOD;

        $params=[
            $round_id,
            $this->getPlatformCode(),
            $playerId
        ];
        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);


        return $this->processBetResult($result);

    }

    private function processBetResult($datas) {

        $result = false;

        if(!empty($datas)){


            $total_bet_amount = 0;
            $total_result_amount = 0;
            $end_at = "";
            $settled = false;

            $bet_details = array();

            $bet_details_debit = array();

            $bet_details_credit = array();
            $after_balance = null;

            foreach($datas as $data) {


                $ex_info_arr = json_decode($data["extra_info"], true);

                if($data['transaction_type'] == SELF::CREDIT) {
                    $total_result_amount += $data["amount"];
                    $settled = true;

                    $details = array(
                        "Bet ID" => $ex_info_arr["BetId"],
                        "Device" => $ex_info_arr["Device"],
                        "Amount" => $data["amount"],
                        "Category" => $ex_info_arr["Category"],
                        "Client Type" => $ex_info_arr["ClientType"],
                        "Client Round Id" => $ex_info_arr["ClientRoundId"],
                        "Transaction ID" => $data["transaction_id"]
                    );

                    $bet_details_credit[] = $details;

                } else {
                    $total_bet_amount += $data["amount"];

                    $details = array(
                        "Bet ID" => $data["transaction_id"],
                        "Device" => $ex_info_arr["Device"],
                        "Amount" => $data["amount"],
                        "Category" => $ex_info_arr["Category"],
                        "Client Type" => $ex_info_arr["ClientType"],
                        "Client Round Id" => $ex_info_arr["ClientRoundId"],
                    );

                    if($ex_info_arr["BonusType"] == "FREE_ROUND") {
                        $details["Bonus Type"] = $ex_info_arr["BonusType"];
                        if(!empty($ex_info_arr["BonusPromoCode"])) {
                            $details["Bonus Promo Code"] = $ex_info_arr["BonusPromoCode"];
                        }

                        if(!empty($ex_info_arr["BonusBetAmount"])) {
                            $details["Bonus Bet Amount"] = $ex_info_arr["BonusBetAmount"];
                        }
                    }

                    $bet_details_debit[] = $details;

                                            /**
                                             *{"Amount": 0.1, "Device": "MOBILE", "GameId": "HAK-cubes", "Created": "2022-03-23T12:38:42+08:00[Asia/Shanghai]", "RoundId": "KPGTGYIVYXHK", "TableId": null, "Category": "CASINO/SLOT/5REEL", "Currency": "THB", "UserName": "devtestt1dev", "playerId": "2", "BonusType": null, "Completed": "true", "ClientType": "FLASH", "GameUserName": "devtestt1dev", "ClientRoundId": "SPPLAXTFJKIN", "TransactionId": "ZIKITKGXYIJL", "BonusBetAmount": null, "BonusPromoCode": null, "JPContribution": null, "TransactionType": "debit"}
                                             */
                }

                $end_at = $data["end_at"];
                $after_balance = $data["after_balance"];

            }

            if(!empty($bet_details_debit)) {

                $bet_details["BET"] = $bet_details_debit;
            }

            if(!empty($bet_details_credit)) {
                $bet_details["PAYOUT"] = $bet_details_credit;
            }

            $result = array(
                                "bet_amount" => $total_bet_amount,
                                "result_amount" => $total_result_amount,
                                "end_at"        => $end_at,
                                "settled"       => $settled,
                                "bet_details"    => $bet_details,
                                "after_balance" => $after_balance
                            );



        }
        return $result;
    }


    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->debug_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
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
            'bet_details' => isset($row['bet_details']) ? $row['bet_details'] : [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if (!$this->enable_merging_rows) {
            if ($row['transaction_type'] == 'credit' || $row['transaction_type'] == 'rollback') {
                $row['bet_amount'] = $row['real_betting_amount'] = 0;
                $row['result_amount'] = $row['amount'];
            } else {
                $row['result_amount'] = -$row['amount'];
            }
        }

        $row['bet_details'] = $this->preprocessBetDetails($row);

        if ($row['status'] == 'cancelled' || $row['transaction_type'] == 'rollback') {
            $row['status'] = Game_logs::STATUS_REFUND;
            
            if ($this->enable_merging_rows) {
                $row['bet_amount'] = $row['real_betting_amount'] = 0;
            }
        } else {
            $row['status'] = Game_logs::STATUS_SETTLED;
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }


    protected function getHttpHeaders($params) {
		return array("Accept" => "application/json",
			"Content-Type" => "application/json",
			"Authorization" => "Bearer " . $this->access_token,
			"timezone" => "UTC/GMT",
		);
	}

    /* public function getLauncherLanguage($lang){
        if($this->force_lang){
        	return $this->lang;
        }
        $this->CI->load->library("language_function");
        // echo $lang;
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            //     $lang = 'id_ID';
            //     break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi_VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko_KR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $lang = 'th_TH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case "pt":
            case "pt-br":
                $lang = 'pt_BR';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_JAPANESE:
            case "ja":
            case "ja_JP":
                $lang = 'ja_JP';
                break;
        
            default:
                $lang = 'en_US';
                break;
        }
        return $lang;
    } */

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en_US',
            'zh_cn' => 'zh_CN',
            'id_id' => 'id_ID',
            'vi_vn' => 'vi_VN',
            'ko_kr' => 'ko_KR',
            'th_th' => 'th_TH',
            'hi_in' => 'hi_IN',
            'pt_pt' => 'pt_PT',
            'es_es' => 'es_ES',
            'kk_kz' => 'kk_KZ',
            'pt_br' => 'pt_BR',
            'ja_jp' => 'ja_JP',
        ]);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("HACKSAW QT SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );

    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("HACKSAW QT SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_APPROVED,
            'reason_id'=>self::REASON_UNKNOWN,
        );
    }

    public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transaction_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
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

                    $temp_game_record = [];
                    $temp_game_record['player_id'] = $transaction['player_id'];
                    $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                    $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                    $temp_game_record['amount'] = abs($transaction['amount']);
                    $temp_game_record['before_balance'] = $transaction['before_balance'];
                    $temp_game_record['after_balance'] = $transaction['after_balance'];
                    $temp_game_record['round_no'] = $transaction['round_no'];
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['trans_type'] == SELF::CREDIT || $transaction['trans_type'] == SELF::ROLLBACK || $transaction['trans_type'] == SELF::BONUSREWARDS) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    } else {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;
        }

     public function queryGameList(){
     	$result = $this->getAccessToken();
     	$this->isMethod = self::GET_METHOD;
     	if (isset($result['access_token'])) {
			$params = array(
	            "size" => 500,
	            "providers" => $this->getPlatformPrefix(),
	            "currencies" => $this->getCurrency(),
	            "includeFields" => $this->gamelist_include_fields
	        );

	        $context = [
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForQueryGameList',
	        ];

	        return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
		}else{
			return array("success" => false, []);
		}
    }

    public function processResultForQueryGameList($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = true;
        return array($success, $resultArr);
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }

        if ($this->enable_merging_rows) {
            if (isset($row['bet_amount'])) {
                $bet_details['bet_amount'] = $row['bet_amount'];
            }
    
            if (isset($row['bet_details']['PAYOUT'][0]['Amount'])) {
                $bet_details['win_amount'] = $row['bet_details']['PAYOUT'][0]['Amount'];
            }

            if (isset($row['transaction_id'])) {
                $bet_details['bet_id'] = $row['transaction_id'];
            }

            if ($row['status'] == 'cancelled') {
                if (isset($row['amount'])) {
                    $bet_details['refund_amount'] = $row['amount'];
                }
            }
        } else {
            if ($row['transaction_type'] == 'debit') {
                $bet_details['bet_amount'] = $row['amount'];

                if (isset($row['transaction_id'])) {
                    $bet_details['bet_id'] = $row['transaction_id'];
                }

                unset($bet_details['win_amount'], $bet_details['settlement_datetime']);
            } elseif ($row['transaction_type'] == 'rollback') {
                if (isset($row['amount'])) {
                    $bet_details['refund_amount'] = $row['amount'];
                }
    
                unset($bet_details['bet_amount'], $bet_details['win_amount']);
            } else {
                $bet_details['win_amount'] = $row['amount'];
                unset($bet_details['bet_amount'], $bet_details['betting_datetime']);
            }
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        #instead query unsettled,query settled to check if have settlement
        $sqlTime='qt.created_at >= ? AND qt.created_at <= ? AND qt.transaction_type != ?';
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
qt.round_id as round_id, 
qt.transaction_id as transaction_id, 
qt.created_at as transaction_date,
CONCAT(qt.round_id, "_", qt.player_id)  as external_uniqueid,
qt.player_id,
qt.transaction_type as transaction_type,
if(qt.transaction_type = 'debit', qt.amount, 0) as deducted_amount,
if(qt.transaction_type != 'debit', qt.amount, 0) as added_amount,
gd.id as game_description_id,
gd.game_type_id,
{$this->getPlatformCode()} as game_platform_id

from {$original_transactions_table} as qt
LEFT JOIN game_description as gd ON qt.game_id = gd.external_game_id and gd.game_platform_id=?
where
{$sqlTime}
GROUP BY qt.round_id, qt.player_id
EOD;


        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            "bonus-rewards"
        ];
        $this->CI->utils->debug_log('==> qt getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $original_transactions_table = $this->getSeamlessTransactionTable();
            $roundId = $row['round_id'];
            $playerId = $row['player_id'];
            $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'credit' , 'player_id' => $playerId]);
            $cancelExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'rollback', 'player_id' => $playerId]);
            if(!$payoutExist && !$cancelExist){
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('qt SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        $arrayData = explode("_", $external_uniqueid);
        $roundId = isset($arrayData[0]) ?$arrayData[0] : null;
        $playerId = isset($arrayData[1]) ?$arrayData[1] : null;
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        if(!$playerId || !$roundId){
        	return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
        }
        $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'credit', 'player_id' => $playerId]);
        $cancelExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($original_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'rollback', 'player_id' => $playerId]);
        if($payoutExist || $cancelExist){
            return array('success'=>true, 'status'=> Game_logs::STATUS_SETTLED);
        }
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}
/*end of file*/
