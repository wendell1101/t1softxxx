<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_loto_seamless extends Abstract_game_api {

    const ORIGINAL_GAMELOGS_TABLE = '';
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    const MODE_FUN = 0;
    const MODE_REAL = 1;

    const REVOKE = "revoke";
    const PLAY = "play";
    const CREDIT = "credit";

    const OPENWIN_NOT_WINNING = "3";
    const OPENWIN_WINNING = "4";

    const SUCCESS_CODE = 200;
    const ERR_OTHER_FAILED = 1000;
    const ERR_PLATFORM_NOT_EXIST = 1001;
    const ERR_USERNAME_ALREADY_EXIST = 1002;
    const ERR_INCORRECT_PASSWORD = 1003;
    const ERR_LOGIN_FAILED = 1004;
    const ERR_CALL_FAILED = 1005;
    const ERR_METHOD_NOT_EXIST = 1006;
    const ERR_NON_WHITELISTED_USERS = 1007;
    const ERR_SIGNATURE_ERROR = 1008;
    const ERR_AMOUNT_SYNCRONIZATION_FAILED = 1009;
    const ERR_TOKEN_ERROR = 1010;
    const ERR_USERNAME_GREATER_THAN_30 = 1011;
    const ERR_USERNAME_LESS_THAN_14 = 1012;
    const ERR_USERNAME_MUST_START_PLATFORM = 1013;

    const URI_MAP = array(
        self::API_createPlayer => '?flag=registerOrLogin',
		self::API_queryForwardGame => '?flag=registerOrLogin',
	);




    public function getPlatformCode(){
        return LOTO_SEAMLESS_API;
    }

    public function isSeamLessGame()
    {
       return true;
    }

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

    public function __construct(){
        parent::__construct();
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->platform_code = $this->getSystemInfo('platform_code');

        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 14);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 30);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 30);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->enable_mm_channel_nofifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);

    }

    public function getApiKey() {

        return $this->api_key;

    }

    public function getLotoPlatformId() {
        return $this->platform_code;
    }


    public function getTransactionsTable(){
        return $this->original_transaction_table;
    }

    protected function customHttpCall($ch, $params) {

		$requestBodyString = json_encode($params);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Accept:application/json"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyString);

    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $platform_code_length = strlen($this->platform_code);
        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit - ($platform_code_length + 1), // plus 1 because of underscore
            'minimum_user_length' => $this->minimum_user_length - ($platform_code_length + 1),
            'maximum_user_length' => $this->maximum_user_length - ($platform_code_length + 1),
            'default_fix_name_length' => $this->default_fix_name_length - ($platform_code_length + 1),
            'check_username_only' => true,
            'strict_username_with_prefix_length' => true,
        ];

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		// MD5( type+username+password+platform+Secret Key

        $sign = md5("registerOrLogin".$this->platform_code.'_'.$gameUsername.$password.$this->platform_code.$this->api_key);
		$params = array(
			'type' => "registerOrLogin",
			'username' => $this->platform_code.'_'. $gameUsername,
			'password' => $password,
            'platform' => $this->platform_code,
            'remark' => $this->platform_code,
            'sign' => $sign
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result=['response_result_id'=>$responseResultId];

		$result['exists']=null;

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists']=true;
		}

		return array($success, $result);

	}

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$password = $this->getPasswordString($playerName);

        $sign = md5("registerOrLogin".$this->platform_code.'_'.$gameUsername.$password.$this->platform_code.$this->api_key);
		$params = array(
			'type' => "registerOrLogin",
			'username' => $this->platform_code.'_'. $gameUsername,
			'password' => $password,
            'platform' => $this->platform_code,
            'remark' => $this->platform_code,
            'sign' => $sign
		);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);


        if(!$success){ #possible error
            $result['message'] = $resultArr['msg'];
        } else {
            $result['url'] = ( $success && isset($resultArr['url']) ) ? $resultArr['url'] : null;
        }

        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName) {

		$success = (!empty($resultJson)) && $resultJson['status'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('Loto Seamless got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

    private function errorReason($code) {

        switch($code) {

            case self::ERR_OTHER_FAILED:
                $errMsg = "Other failed";
                break;
            case self::ERR_PLATFORM_NOT_EXIST:
                $errMsg= "Platform does not exist";
                break;
            case self::ERR_USERNAME_ALREADY_EXIST:
                $errMsg = "Username already exists";
                break;
            case self::ERR_INCORRECT_PASSWORD:
                $errMsg = "Incorrect password";
                break;
            case self::ERR_LOGIN_FAILED:
                $errMsg = "Login failed";
                break;
            case self::ERR_CALL_FAILED:
                $errMsg = "Call failed";
                break;
            case self::ERR_METHOD_NOT_EXIST:
                $errMsg = "Method not exist";
                break;
            case self::ERR_NON_WHITELISTED_USERS:
                $errMsg = "Non-whitelisted users";
                break;
            case self::ERR_SIGNATURE_ERROR:
                $errMsg = "Signature Error";
                break;
            case self::ERR_AMOUNT_SYNCRONIZATION_FAILED:
                $errMsg = "Amount synchronization failed";
                break;
            case self::ERR_TOKEN_ERROR:
                $errMsg = "Token Error";
                break;
            case self::ERR_USERNAME_GREATER_THAN_30:
                break;
            case self::ERR_USERNAME_LESS_THAN_14:
                break;
            case self::ERR_USERNAME_MUST_START_PLATFORM:
                break;

        }

    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'en'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("KA SEAMLESS: (depositToGame)");

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
        $this->utils->debug_log("KA SEAMLESS: (withdrawFromGame)");

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

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token)
    {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword){
        return $this->returnUnimplemented(); // need to add this one because once the player change the password of their player center the password in the game_provider_auth was change.
        // as per game provider there is no endpoint to change the password.
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

        $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);

        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo) {
        #query bet only
        $sqlTime="loto.updated_at >= ? AND loto.updated_at <= ? AND loto.game_platform_id = ? AND (loto.transaction_type = 'bet' AND loto.transaction_type != 'cancelled')";

        $sql = <<<EOD
SELECT
loto.id as sync_index,
loto.response_result_id,
loto.transaction_id as external_uniqueid,
loto.md5_sum,
game_provider_auth.login_name as player_username,
loto.player_id,
loto.game_platform_id,
loto.amount as bet_amount,
loto.amount as real_betting_amount,
loto.game_id as game_code,
loto.transaction_type,
loto.status,
loto.round_id as round_number,
loto.extra_info,
loto.start_at,
loto.start_at as bet_at,
loto.end_at,
loto.before_balance,
loto.after_balance,
loto.transaction_type,
loto.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as loto
LEFT JOIN game_description as gd ON loto.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth
ON
	loto.player_id = game_provider_auth.player_id AND
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

        $transaction_ids = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {


                $transaction_type = $record['transaction_type'];

                $temp_game_records = $record;
                $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;

                $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                $temp_game_records['external_uniqueid'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
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

                $temp_game_records['bet_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;
                $temp_game_records['real_betting_amount'] = $record["bet_amount"] !== null ? $record["bet_amount"] : 0;

                $open_win_result = $this->queryOpenWinAmount($record['round_number'],$record['transaction_id']);

                $win_number = array();

                if($open_win_result) {

                    if($open_win_result['status'] == self::OPENWIN_WINNING) {
                        $temp_game_records['result_amount'] = $open_win_result['result_amount'] - $record["bet_amount"];
                    } else {
                        $temp_game_records['result_amount'] = -$record["bet_amount"];
                    }

                    $win_number = $open_win_result["win_number"];
                } else {
                    $temp_game_records['result_amount'] = -$record["bet_amount"];
                }

                $extra_info = json_decode($record["extra_info"], true);

                $temp_game_records['bet_details'] = $this->processBetDetails($extra_info, $win_number);


                $temp_game_records['status'] = $this->getGameRecordsStatus($record['status']);
                // $gameRecords[$index] = $temp_game_records;
                $rebuildGameRecords[] = $temp_game_records;
                unset($data);

            }

        }

    }

    private function queryOpenWinAmount($round_id, $transaction_id) {

        $sqlTime='loto.round_id = ? and loto.transaction_id = ? and loto.game_platform_id = ? and loto.transaction_type="openWin"';

        $sql = <<<EOD
SELECT
loto.id as sync_index,
loto.amount,
loto.end_at,
loto.after_balance,
loto.transaction_type,
loto.extra_info,
loto.game_platform_id
FROM common_seamless_wallet_transactions as loto
WHERE
{$sqlTime}
EOD;

        $params=[
            $round_id,
            $transaction_id,
            $this->getPlatformCode()
        ];
        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);


        return $this->processOpenWinAmount($result);

    }

    private function processOpenWinAmount($datas) {

        $result = false;

        if(!empty($datas)){


            $total_result_amount = 0;

            foreach($datas as $data) {

                $extra_info_arr = json_decode($data["extra_info"]);

                $win_number = isset($extra_info_arr->WinNumber) ? json_decode($extra_info_arr->WinNumber, true) : false;

                $status = isset($extra_info_arr->Status) ? $extra_info_arr->Status : false;

                $total_result_amount += $data["amount"];

            }

            $result = array(
                                "result_amount" => $total_result_amount,
                                "win_number" => $win_number,
                                "status" => $status
                            );



        }
        return $result;
    }

    public function processBetDetails($bet_details, $win_number) {


        $bet_amount = isset($bet_details["BetAmount"]) ? $bet_details["BetAmount"] : null;
        $bet_type = isset($bet_details["BetType"]) ? $bet_details["BetType"] : null;
        $bet_codes = isset($bet_details["Codes"]) ? $bet_details["Codes"] : null;
        $times = isset($bet_details["Times"]) ? $bet_details["Times"] : null;

        return array(
            "Bet Type" => $bet_type,
            "Bet Amount" => $bet_amount,
            "Codes" => $bet_codes,
            "Times" => $times,
            "Win Number" => $win_number
        );
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
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
                'after_balance' => null,
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
            'bet_details' => $row["bet_details"],
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

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {

        switch (strtolower($status)) {
            case "settled":
                $status = Game_logs::STATUS_SETTLED;
                break;
            case "ok":
                $status = Game_logs::STATUS_PENDING;
                break;
            case "cancelled":
                $status = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }
        return $status;
    }

    public function generateUrl($apiName, $params)
    {
        $apiUri = self::URI_MAP[$apiName];


		$url = $this->api_url . $apiUri;

		return $url;
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

                $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], ['openWin', 'cancel'])){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='`created_at` >= ? AND `created_at` <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $finalResult = [];
        $status = Game_logs::STATUS_PENDING;

        $sql = <<<EOD
select group_concat(`transaction_type`) as concat_action, group_concat(`status`) as concat_status,  round_id, player_id, transaction_id,
SUM(IF(`transaction_type` = 'openWin', amount, 0)) as total_win,
SUM(IF(`transaction_type` = 'bet', amount, 0)) as sum_deduct
from {$this->original_transaction_table}
where {$sqlTime} and `transaction_type` in ('bet','openWin') and game_platform_id = ?
group by round_id, player_id, transaction_id
having concat_status not like '%settled%';
EOD;

        $params=[
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
		];

	    $this->CI->utils->debug_log('LOTO SEAMLESS (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data) {
        $this->CI->utils->debug_log('LOTO SEAMLESS (checkBetStatus)', $data);
        if(!isset($data['transaction_id']) || !isset($data['transaction_id'])){
            return array('success'=>false, 'exists'=>false);
        }

        $gamePlatformId = $this->getPlatformCode();
        $this->CI->load->model(array('original_game_logs_model', 'seamless_missing_payout'));
        //$this->original_transactions_table = $this->getTransactionsTable();

        $ispayoutexist = true;

        //check round if no refund
        $this->CI->db->from($this->original_transaction_table)
            ->where("transaction_id",$data['transaction_id'])
            ->where("round_id",$data['round_id'])
            ->where("transaction_type !=", 'bet')
            ->where("player_id",$data['player_id']);
        $ispayoutexist = $this->CI->original_game_logs_model->runExistsResult();

        if($ispayoutexist){
            return array('success'=>true, 'exists'=>$ispayoutexist);
        }

        $transTable=$this->getTransactionsTable();

        //save record to missing payout report
$sql = <<<EOD
SELECT
t.created_at transaction_date,
t.`transaction_type`,
game_provider_auth.player_id,
t.round_id,
t.transaction_id,
t.amount,
t.amount deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
t.external_unique_id as external_uniqueid
FROM {$transTable} as t
left JOIN game_description as gd ON t.game_id = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON t.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id=?
WHERE
t.transaction_id = ? and t.`transaction_type` = 'bet' and t.player_id=? and t.game_platform_id = ?
EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $data['transaction_id'], $data['player_id'], $this->getPlatformCode()];

        $trans = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }

        foreach($trans as $insertData){
            $insertData['transaction_status'] = Game_logs::STATUS_PENDING;
            $insertData['game_platform_id'] = $this->getPlatformCode();
            $insertData['added_amount'] = 0;
            $insertData['status'] = Seamless_missing_payout::NOT_FIXED;
            $notes = [];
            $insertData['note'] = json_encode($notes);
            $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$insertData);
            if($result===false){
                $this->CI->utils->error_log('LOTO SEAMLESS (checkBetStatus) Error insert missing payout', $insertData);
            }
        }

        if($this->enable_mm_channel_nofifications){

            //save data to seamless_missing_payout

            //check if transaction has no payout
            $adminUrl = $this->CI->utils->getConfig('admin_url');
            $message = "@all LOTO Seamless to check missing Payout"."\n";
            $message = "Client: ".$adminUrl."\n";
            $message .= json_encode($data);

            $this->CI->load->helper('mattermost_notification_helper');

            $notif_message = array(
                array(
                    'text' => $message,
                    'type' => 'warning'
                )
            );
            sendNotificationToMattermost("LOTO SEAMLESS SERVICE ($gamePlatformId)", $this->mm_channel, $notif_message, null);
            $this->CI->utils->debug_log('LOTO SEAMLESS (checkBetStatus) sendNotificationToMattermost', $message);
        }

		return array('success'=>true, 'exists'=>$ispayoutexist);
	}
}
/*end of file*/