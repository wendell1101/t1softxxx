<?php
/**
 * IDN game integration
 * OGP-26223
 *
 * @author  Kristallynn Tolentino
 *
 */

require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_common_idn extends Abstract_game_api {
    const FUNCTION_REGISTER = 1;
    const FUNCTION_LOGIN = 2;
    const FUNCTION_DEPOSIT = 3;
    const FUNCTION_WITHDRAW = 4;
    const FUNCTION_LOGOUT = 6;
    const FUNCTION_CHECK_TRANSACTION = 7;
    const FUNCTION_DAILY_REPORT = 8;
    const FUNCTION_CURRENT_RATE = 9;
    const FUNCTION_PLAYER_DETAIL = 10;
    const FUNCTION_TRANSACTION_LOG_DETAIL = 12;
    const FUNCTION_GLOBAL_JACKPOT = 13;
    const FUNCTION_JACKPOT_LIST = 14;
    const FUNCTION_TRANSACTION_LIST = 15;
    const FUNCTION_CHANGE_PASSWORD = 16;
    const FUNCTION_CREATE_LOGINID = 17; //for mobile version

    const DEPOSIT = 1;
	const WITHDRAW = 2;

    const STATUS_NOT_INCLUDED_IN_TURNOVER_CALCULATION = array("Buy Jackpot","Win Global Jackpot","Gift","Deposit","Withdraw","Refund","Tournament-register","Bonus");
	const STATUS_LOSS = array("Lose","Draw","Buy Jackpot","Fold","Gift","Tournament-register");

    const METHOD = [
        "POST" => "POST",
        "GET" => "GET",
        "PUT" => "PUT"
    ];
    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const MD5_FIELDS_FOR_ORIGINAL = [
        "player",
        "tableno",
        "table_type",
        "periode",
        "room",
        "card",
        "hand",
        "game_status",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'curr_bet',
        'r_bet',
        'prize',
        'amount',
        'curr_amount',
        'total_coin',
        'agent_comission',
        'agent_bill',
        'external_uniqueid'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_code',
        'player_username',
        'start_at',
        'bet_at',
        'end_at',
        'result_amount',
        'real_betting_result',
        'bet_amount',
        'note',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'real_betting_result',
        'bet_amount'
    ];

    const ORIGINAL_LOGS_TABLE = 'idnpoker_game_logs';

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'https://idn889.com');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency', 'IDR');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->token = $this->getSystemInfo('token', 'f17b56a56111deda05e4fe73e');
        $this->always_use_https_launcher = $this->getSystemInfo('always_use_https_launcher',true);
        $this->original_logs_table = self::ORIGINAL_LOGS_TABLE;

        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 15);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);
    }

    public function isSeamLessGame(){
        return false;
    }

    public function getPlatformCode() {
        return IDNPOKER_API;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function generateUrl($apiName, $params) {
		$url = $this->api_url;
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		$xml_object = new SimpleXMLElement("<request></request>");
		$xmlData = $this->CI->utils->arrayToXml($params, $xml_object);
		$this->CI->utils->debug_log('-----------------------IDN POST XML STRING ----------------------------',$xmlData);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);

	}

    public function createPlayer($playerName, $playerId = null, $password = null, $email = null, $extra = null) {

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
            'password' => $password,
            'email' => $email
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_REGISTER,
			'userid' => $playerName,
			'password' => $password,
			'confirm_password' => $password,
			'username' => $playerName,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $password = $this->getVariableFromContext($params, 'password');
        $email = $this->getVariableFromContext($params, 'email');

		//create Mobile Player
		$this->createMobilePlayer($playerName, $playerId, $password, $email);

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

	public function createMobilePlayer($playerName, $playerId, $password, $email) {

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreateMobilePlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_CREATE_LOGINID,
			'userid' => $playerName,
			'loginid' => $playerName
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreateMobilePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);
	}

    public function createPlayerInDB($playerName, $playerId, $password, $email = null, $extra = null) {

        //write to db
        $this->CI->load->model(array('game_provider_auth', 'player_model', 'agency_model'));

        $row = $this->CI->game_provider_auth->getByPlayerIdGamePlatformId($playerId, $this->getPlatformCode());

        if (empty($row)) {
            //convert username, not right name

            $source = Game_provider_auth::SOURCE_REGISTER;
            if ($extra && array_key_exists('source', $extra) && $extra['source']) {
                $source = $extra['source'];
            }

            $is_demo_flag = false;
            if ($extra && array_key_exists('is_demo_flag', $extra) && $extra['is_demo_flag']) {
                $is_demo_flag = $extra['is_demo_flag'];
            }

            $this->CI->utils->debug_log('TCG', "login name regenerated");

            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $source,
                    "is_demo_flag" => $is_demo_flag,
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                ),
                $this->getPlatformCode(), $extra);
        } else if (!empty($extra['fix_username_limit'])
            && $extra['fix_username_limit']
            && !$this->CI->game_provider_auth->loginNameIsRandomlyGenerated($row, $playerName, $this->getSystemInfo('prefix_for_username'))
            && !$this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode())
            ){

            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $row['source'],
                    "is_demo_flag" => $row['is_demo_flag'],
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                    // "sma_id" => (!isset($player->root_agent_id)) ?: NULL
                ),
                $this->getPlatformCode(), $extra);
        } else if( !empty($extra['fix_username_limit'])
            && $extra['fix_username_limit']
            && !$this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode())
            && !$this->CI->game_provider_auth->loginNameIsCorrectLength($row['login_name'], $extra)
        ){
            $player = (array) $this->CI->player_model->getPlayerById($playerId);

            $result = $this->CI->game_provider_auth->savePasswordForPlayerWithProcessedLoginName(
                array(
                    'username' => $playerName,
                    "id" => $playerId,
                    "password" => $password,
                    "source" => $row['source'],
                    "is_demo_flag" => $row['is_demo_flag'],
                    "agent_id" => @$player['agent_id'],
                    "sma_id" => (array_key_exists("root_agent_id",$player)) ? $player['root_agent_id'] : NULL
                    // "sma_id" => (!isset($player->root_agent_id)) ?: NULL
                ),
                $this->getPlatformCode(), $extra);

        }else {
            $result = true;
        }


        return $result;
    }

    public function queryForwardGame($playerName, $extra=null) {

		$password = $this->getPassword($playerName);
		$password = $password['password'];
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$language = $this->getSystemInfo('language', $extra['language']);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
            'language' => $this->getLauncherLanguage($language),
            'is_https' => ($this->always_use_https_launcher) ? 1 : 0
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_LOGIN,
			'userid' => $playerName,
			'password' => $password,
			'ip' => $this->CI->input->ip_address(),
			'secure' => ($this->always_use_https_launcher) ? 1 : 0,
			'lang' => $this->getLauncherLanguage($language)
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	public function processResultForQueryForwardGame($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
        $language = $this->getVariableFromContext($params, 'language');
        $is_https = $this->getVariableFromContext($params, 'is_https');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result = array('url' => $resultArr['lobby_url'].'&lang='.$language);
		}

		return array($success, $result);
    }

    public function logout($username, $password = null) {

		if($password==null){
			$password = $this->getPassword($username);
		}

		if(isset($password['password'])){
			$password = $password['password'];
		}

		$username = $this->getGameUsernameByPlayerUsername($username);
		$password = $password;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $username
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_LOGOUT,
			'userid' => $username,
			'password' => $password,
			'confirm_password' => $password,
			'username' => $username
		);

		return $this->callApi(self::API_logout, $params, $context);

	}

	public function processResultForLogout($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

    public function changePassword($playerName, $oldPassword = null, $newPassword) {

		if($oldPassword==null){
			$oldPassword = $this->getPassword($playerName);
			if(isset($oldPassword['password'])){
				$oldPassword = $oldPassword['password'];
			}
		}

		$playerId = $this->getPlayerIdInPlayer($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'newPassword' => $newPassword,
			'newSbePassword' => $newPassword
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_CHANGE_PASSWORD,
			'userid' => $playerName,
			'password' => $oldPassword,
			'new_password' => $newPassword,
			'retypenew_password' => $newPassword
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext((array) $params, 'newPassword');
		$newSbePassword = $this->getVariableFromContext((array) $params, 'newSbePassword');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		if ($success) {
			$this->updatePasswordForPlayer($playerId, $newSbePassword);
		}

		return array( $success, $resultArr );
	}

	public function queryPlayerBalance($userName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $userName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_PLAYER_DETAIL,
			'userid' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		$result = array();

		if($success){
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
		}

		return array($success, $result);
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$transactionId = isset($transfer_secure_id)?$transfer_secure_id:($playerId.date("YmdHis").rand());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_DEPOSIT,
			'userid' => $gameUsername,
			'id_transaction' => $transactionId,
			'deposit' => $this->dBtoGameAmount($amount)
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
		} else {
			$error_code = @$resultArr['error'];
			switch($error_code) {
				case '1' :
					$result['reason_id'] = self::REASON_GAME_ACCOUNT_LOCKED;
					break;
				case '2' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
				case '3' :
				case '6' :
					$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
					break;
				case '4' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
					break;
				case '5' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				$success=true;
			}
		}

		return array($success, $result);

	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$transactionId = isset($transfer_secure_id)?$transfer_secure_id:($playerId.date("YmdHis").rand());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_WITHDRAW,
			'userid' => $gameUsername,
			'id_transaction' => $transactionId,
			'withdraw' => $this->dBtoGameAmount($amount)
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
		} else {
			$error_code = @$resultArr['error'];
			switch($error_code) {
				case '1' :
					$result['reason_id'] = self::REASON_GAME_ACCOUNT_LOCKED;
					break;
				case '2' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
				case '3' :
				case '6' :
					$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
					break;
				case '4' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
					break;
				case '5' :
				case '7' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);

	}

	public function queryTransaction($transactionId, $extra) {
		$playerName = $extra['playerName'];
		$playerId=$extra['playerId'];
		$transfer_method = @$extra['transfer_method'] == 'deposit' ? self::DEPOSIT : self::WITHDRAW;

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId' => $playerId,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_CHECK_TRANSACTION,
			'userid' => $gameUsername,
			'action' => $transfer_method,
			'id_transaction' => $transactionId,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'reason_id'=>self::REASON_UNKNOWN
		);

		// api only response if id exist or not
		// no checking if approve, processing or decline
		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			# as per game provider only transaction id exists is success, no fail.
			if($resultArr['message'] == $this->transaction_available_message){
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$success = true;
			}
		}

		return array($success, $result);

	}

    public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

		$success = true;
		if(isset($resultArr['status'])&&$resultArr['status']==0){
			$success = false;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('IDN', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;

	}

    public function uid($transaction_no, $date, $game_type, $username) {
		return $transaction_no.'-'.$date.'-'.$game_type.'-'.$username;
	}

   	/**
	 * API RULES
	 * a. 1 call per minute   e.g response  [message] => API request limit, please wait 1 minute
	 * b. api only keep 15 days record
	 */
	public function syncOriginalGameLogs($token = false) {
        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo));

        $sdate = $dateTimeFrom->format('m/d/Y');

        $start = $dateTimeFrom->format('H:i');
        $end = $dateTimeTo->format('H:i');

		$result = $this->syncIdnGamelogs($sdate,$start,$end);

		return $result;
	}

	public function syncIdnGamelogs($date,$start,$end){

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $date, "startTime: " . $start,  "endTime: " . $end);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'date' => $date,
			'start_time' => $start,
			'end_time' => $end
		);
		$params = array(
			'secret_key' => $this->token,
			'id' => self::FUNCTION_TRANSACTION_LIST,
			'date' => $date,
			'start_time' => $start,
			'end_time' => $end
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	public function isMultidimensional(array $array) {
		return count($array) !== count($array, COUNT_RECURSIVE);
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('player_model','original_game_logs_model','external_system'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = !isset($resultArr['error'])?true:false;

        $this->CI->utils->debug_log("TransactionList6108", $resultArr);

		$date = $this->getVariableFromContext($params, 'date');
		$start_time = $this->getVariableFromContext($params, 'start_time');
		$end_time = $this->getVariableFromContext($params, 'end_time');

		$result = array('data_count'=> 0);
		if ($success) {
			if($resultArr['numrow'] > 0) {
				$gameRecords = $resultArr['row'];
				if(!$this->isMultidimensional($gameRecords)) { // means data only one (single array format
					$gameRecords = array();
					$gameRecords[] = $resultArr['row'];
				}
				$this->preProcessGameRecords($gameRecords,$responseResultId);

				if ($gameRecords) {
					list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
						self::ORIGINAL_LOGS_TABLE,
						$gameRecords,
						'external_uniqueid',
						'external_uniqueid',
						self::MD5_FIELDS_FOR_ORIGINAL,
						'md5_sum',
						'id',
						self::MD5_FLOAT_AMOUNT_FIELDS
					);
					$this->CI->utils->debug_log('IDN poker after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
					$this->CI->utils->debug_log("IDN poker fetch data from [$date] [$start_time] to [$end_time]");
					if (!empty($insertRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
					}
					unset($insertRows);

					if (!empty($updateRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
					}
					unset($updateRows);
				}
			} else {
				$this->CI->utils->debug_log('IDN poker success, but empty rows');
			}
		} else {
			$this->CI->utils->debug_log('IDN poker api response ===========> '.json_encode($resultArr));
		}
		return array($success, $result);
	}

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount = 0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function preProcessGameRecords(&$gameRecords,$responseResultId){
		$preResult = array();
		foreach($gameRecords as $index => $record) {


			if($record['status'] =="Deposit" || $record['status'] =="Withdraw"){
				continue; // skip deposit and withdraw transactions
			}
			$preResult[$index] = array();
			$transaction_no = isset($record['transaction_no']) ? $record['transaction_no'] : NULL;
			$date =  isset($record['date']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime(str_replace("/", "-",$record['date'])))) : NULL;
			$game_type = isset($record['game']) ? $record['game'] : NULL;

			$preResult[$index]['transaction_no'] = $transaction_no;
			$preResult[$index]['bet_date'] = $date;
			$preResult[$index]['tableno'] = isset($record['tableno']) ? $record['tableno'] : NULL;
			$preResult[$index]['player'] = isset($record['userid']) ? $record['userid'] : NULL;
			$preResult[$index]['game_code'] = $game_type; //game code
			$preResult[$index]['table_type'] = isset($record['table']) ? $record['table'] : NULL;
			$preResult[$index]['periode'] = isset($record['periode']) ? $record['periode'] : NULL;
			$preResult[$index]['room'] = isset($record['room']) ? $record['room'] : NULL;
			$preResult[$index]['bet'] = isset($record['bet']) ? $record['bet'] : NULL;
			$preResult[$index]['curr_bet'] = isset($record['curr_bet']) ? $record['curr_bet'] : NULL;
			$preResult[$index]['game_status'] = isset($record['status']) ? $record['status'] : NULL;
			$preResult[$index]['hand'] = isset($record['hand']) ? $record['hand'] : NULL;
			$preResult[$index]['card'] = isset($record['card']) ? $record['card'] : NULL;
			$preResult[$index]['prize'] = isset($record['prize']) ? $record['prize'] : NULL;
			$preResult[$index]['curr'] = isset($record['curr']) ? $record['curr'] : NULL;
			$preResult[$index]['curr_player'] = isset($record['curr_player']) ? $record['curr_player'] : NULL;
			$preResult[$index]['amount'] = isset($record['amount']) ? $record['amount'] : NULL;
			$preResult[$index]['curr_amount'] = isset($record['curr_amount']) ? $record['curr_amount'] : NULL;
			$preResult[$index]['total_coin'] = isset($record['total']) ? $record['total'] : NULL;
			$preResult[$index]['agent_comission'] = isset($record['agent_comission']) ? $record['agent_comission'] : NULL;
			$preResult[$index]['agent_bill'] = isset($record['agent_bill']) ? $record['agent_bill'] : NULL;

			//extra info from SBE
			$preResult[$index]['external_uniqueid'] = $this->uid($transaction_no,$date,$game_type,$preResult[$index]['player']);
			$preResult[$index]['response_result_id'] = $responseResultId;
		}
		$gameRecords = $preResult;
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $game_logs_table = $this->original_logs_table;
        $game_logs_table_as = "idn";

        $sqlTime="{$game_logs_table_as}.created_at >= ? and {$game_logs_table_as}.created_at <= ?";

        if($use_bet_time){
            $sqlTime="{$game_logs_table_as}.bet_date >= ? and {$game_logs_table_as}.bet_date <= ?";
        }

        $sql = <<<EOD
SELECT
{$game_logs_table_as}.id as sync_index,
{$game_logs_table_as}.player,
{$game_logs_table_as}.external_uniqueid,
{$game_logs_table_as}.bet_date AS game_date,
{$game_logs_table_as}.response_result_id,
{$game_logs_table_as}.curr_amount AS result_amount,
{$game_logs_table_as}.periode AS game_round_id,
{$game_logs_table_as}.curr_bet AS bet_amount,
{$game_logs_table_as}.amount AS real_bet,
{$game_logs_table_as}.game_status,
{$game_logs_table_as}.total_coin AS after_balance,
{$game_logs_table_as}.hand AS hand,
{$game_logs_table_as}.prize AS prize,
{$game_logs_table_as}.card AS card,
{$game_logs_table_as}.agent_comission,
{$game_logs_table_as}.agent_bill,
{$game_logs_table_as}.md5_sum,

game_provider_auth.login_name as player_username,
game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id,
gd.game_code

FROM {$game_logs_table} as {$game_logs_table_as}

LEFT JOIN game_description as gd ON idn.game_code = gd.game_code and gd.game_platform_id= ?
JOIN game_provider_auth ON idn.player = game_provider_auth.login_name and game_provider_auth.game_provider_id= ?

WHERE


{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $this->CI->utils->debug_log('-----------------------IDN SQL ----------------------------',$sql);
        $this->CI->utils->debug_log('-----------------------IDN SQL ----------------------------',$params);
        $this->CI->utils->debug_log('-----------------------IDN SQL ----------------------------',$result);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra_info=[
			'table' => $row["game_round_id"],
			'rent' => $row["agent_comission"],
			'note' => $row['game_status'],
			'trans_amount' => $this->gameAmountToDBTruncateNumber($row['bet_amount']),
			'match_details' => $row["prize"],
			'match_type' => json_encode(array("hand" => $row["hand"], "card" => $row["card"])),
			'handicap' => $row["game_round_id"],
			'bet_type'  => ''
		];

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ?  $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ?  $this->gameAmountToDBTruncateNumber($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ?  $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['real_bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['real_bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDBTruncateNumber($row['after_balance']) : 0
            ],
            'date_info'=>[
                'start_at'              => $row['game_date'], 'end_at'=>$row['game_date'], 'bet_at'=>$row['game_date'],
				'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null,
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => $extra_info,
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_description_id = $row['game_description_id'];
		$game_type_id = $row['game_type_id'];
		if (empty($game_description_id)) {
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
		}
		$row['game_description_id']=$game_description_id;
		$row['game_type_id']=$game_type_id;

		$row['status'] = Game_logs::STATUS_SETTLED;

		$bet_amount = $row['bet_amount'];
		$real_bet = $this->gameAmountToDBTruncateNumber($row['bet_amount']);
		$result_amount = $row["result_amount"];

		$game_status = strtolower($row["game_status"]);

		$status_not_include_in_turn_over =  array_map('strtolower', self::STATUS_NOT_INCLUDED_IN_TURNOVER_CALCULATION);
		$status_loss =  array_map('strtolower', self::STATUS_LOSS);

		if (in_array($game_status, $status_not_include_in_turn_over)) {
			$bet_amount = 0;
			$real_bet  = 0;
		}
		if(in_array($game_status, $status_loss)){ //status counted as loss
			$result_amount = -$result_amount;
		}

		$row['bet_amount'] = $bet_amount;
		$row['real_bet_amount'] = $real_bet;
		$row['result_amount'] = $result_amount;
		$row['after_balance'] = $row["after_balance"];
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
            $game_description_id = $gameDescId;
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

	public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
			case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
			case 'en-us':
				$lang = 'en';
				break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
			case 'zh-cn':
                $lang = 'cs';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
			case 'id-id':
                $lang = 'id';
                break;
			case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'th-th':
				$lang = 'th';
				break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
			case 'vi-vn':
                $lang = 'vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
			case 'ko-kr':
                $lang = 'kr';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }
}
