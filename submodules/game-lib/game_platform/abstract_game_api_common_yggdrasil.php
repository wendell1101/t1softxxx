<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: Oriental Game
	* API docs: http://mucho.oriental-game.com:8059/
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @bermar.php.ph
**/

abstract class Abstract_game_api_common_yggdrasil extends Abstract_game_api {

    const API_queryDemoGame = 'queryDemoGame';

    const URI_MAP = array(
        self::API_createPlayer => '/att/getBalance',
        self::API_queryForwardGame => '/att/loginGame',
        self::API_queryPlayerBalance => '/att/getBalance',
        self::API_isPlayerExist => '/att/getBalance',
        self::API_depositToGame => '/att/credit',
        self::API_withdrawFromGame => '/att/withdraw',
        self::API_syncGameRecords => '/att/getUsersBetDataV2',
        self::API_queryTransaction => '/att/checkTransferStatus',
        self::API_queryDemoGame => '/att/tryGame',
    );

	const SUCCESS_CODE = ['0'];

	const RESPONSE_CODE_LIST = [
        '0' => 'success',
        '1' => 'system error',
        '2' => 'account frozen',
        '3' => 'balance insufficient',
        '4' => 'sign error',
        '5' => 'amount can\'t less than 0',
        '6' => 'merchant not exist',
        '7' => 'request param error',
        '8' => 'billno already exist',
        '9' => 'channel param error',
        '10' => 'currency not support',
        '11' => 'countryCode error',
        '12' => 'player not exist',
        '13' => 'player no wager data',
        '14' => 'startTime and endTime are required when billno is blank'
    ];

	const CURRENCY_CODE_LIST = [
        'CNY' => 'Chinese Yuan',
        'KRW' => 'South Korean Won',
        'USD' => 'United States Dollar',
        'HKD' => 'Hong Kong Dollar',
        'EUR' => 'Euro',
        'GBP' => 'Great Britain Pound',
        'JPY' => 'Japanese Yen',
        'TWD' => 'New Taiwan dollar',
        'THB' => 'Thai Baht',
        'INR' => 'Indian Rupee',
        'MYR' => 'Malaysian Ringgit',
        'SGD' => 'Singapore Dollar',
        'IDR' => 'Indonesian Rupiah',
        'MKK' => 'Burmese Kyat',
        'AMD' => 'Armenian Dram',
        'ARS' => 'Argentine Peso',
        'AUD' => 'Australian Dollar',
        'BGN' => 'Bulgarian Lev',
        'BRL' => 'Brazilian Real',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'CLP' => 'Chilean Peso',
        'CZK' => 'Czech Republic Koruna',
        'DKK' => 'Danish Krone',
        'HKR' => 'Croatian Kuna',
    ];

	const COUNTRY_CODE_LIST = [
        'CN' => 'China',
        'HK' => 'Hong Kong',
        'TW' => 'Taiwan',
        'KR' => 'South Korea',
    ];

	const LANGUAGE_CODE_LIST = [
        'zh_hans' => 'Simplified Chinese',
        'zh_hant' => 'Traditional Chinese',
        'ko' => 'Korean',
        'th' => 'Thai',
        'en' => 'English',
        'vi' => 'Vietnamese',
        'id' => 'Indonesian',
        'ja' => 'Japanese',
    ];

	const BET_TYPE_LIST = [
        'wager' => 'bet',
        'endWager' => 'end bet',
        'cancelWager' => 'canceled bet due to internal error (bet amount returned to the player)',
        'appendWagerResult' => 'appeared in jackpot and prize drop rewards',
    ];

    const MD5_FIELDS_FOR_ORIGINAL=[
        //api response
        'loginname',
        'currency',
        'type',
        'amount',
        'beforeAmount',
        'afterAmount',
        'gameName',
        'reference',
        'createTime',

        //additional identification
        'gameId',
        'player_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'beforeAmount',
        'afterAmount'
    ];

    const MD5_FIELDS_FOR_MERGE=['player_id', 'currency','result_amount','bet_amount','status'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['result_amount','bet_amount'];

    private $original_gamelogs_table = 'yggdrasil_game_logs';

    private $trial_mode = ['trial', 'fun', 'demo'];

	private $url;
	private $topOrg;
	private $org;
	private $key;
	private $currency;
	private $language;
	private $method;
    private $countryCode;
    private $sync_time_interval;

    public function __construct() {
        parent::__construct();

		$this->url          = $this->getSystemInfo('url');
		$this->topOrg       = $this->getSystemInfo('topOrg');
		$this->org          = $this->getSystemInfo('org');
		$this->key 		    = $this->getSystemInfo('key');
		$this->currency 	= $this->getSystemInfo('currency');
        $this->language 	= $this->getSystemInfo('language');
        $this->returnUrl    = $this->getSystemInfo('returnUrl'); //to use for mobile
        $this->countryCode    = $this->getSystemInfo('countryCode');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
        $this->gametype = $this->getSystemInfo('gametype', 'yg');

        $this->utils->debug_log('YGGDRASIL (__construct) url: '.$this->url.
        ', topOrg: '.$this->topOrg.
        ', org: '.$this->org.
        ', key: '.$this->key.
        ', currency: '.$this->currency,
        ', language: '.$this->language);


        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
    }

    public function getPlatformCode() {
        return YGGDRASIL_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->getSystemInfo('url').self::URI_MAP[$apiName];
        if($apiName == self::API_syncGameRecords){
            $url = $this->getSystemInfo('ticket_api_url').self::URI_MAP[$apiName];
        }
        $this->utils->debug_log("YGGDRASIL: (generateUrl) url: $url");
        return $url;
    }

    public function generateSign($mode = 'merchant', $gameUsername = '') {
        $sign = md5($this->topOrg . $this->org . $this->key);
        switch ($mode){
            case 'player':
                $sign = md5($gameUsername . $this->key);
				break;
            case 'merchant':
                break;
            default:
                break;
        }
        $this->utils->debug_log("YGGDRASIL: (generateSign) mode: $mode, gameUsername: $gameUsername, sign: $sign");
        return $sign;
    }

    public function convertLocalToUtc($dateTime){
        $myDateTime = new DateTime($dateTime);
        $myDateTime->setTimezone(new DateTimeZone('UTC'));
        return $myDateTime->format('Y-m-d H:i:s');
    }

    public function convertUtcToLocal($dateTime){
        $myDateTime = new DateTime($dateTime, new DateTimeZone('UTC'));
        $myDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        return $myDateTime->format('Y-m-d H:i:s');
    }

    public function getResponseCodeDescription($code) {
        return 'unknown';
    }

	protected function customHttpCall($ch, $params) {
        $this->utils->debug_log("YGGDRASIL: (customHttpCall) method: ", $this->method, 'params:', json_encode($params));
		switch ($this->method){
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				break;
		}
	}

    protected function processResultBoolean($responseResultId, $resultArr, $playerName = null,$is_querytransaction= false) {
        $success = false;
        if(isset($resultArr['code']) && trim($resultArr['code']) == '0'){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('YGGDRASIL (processResultBoolean) got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    public function queryForwardGame($playerName, $extra = array()){
        $this->utils->debug_log("YGGDRASIL: (queryForwardGame) playerName: $playerName");
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForQueryForwardGame',
            'gameUsername'      => $gameUsername,
            'playerName'        => $playerName
        );

        $channel = 'pc';
        if(isset($extra['is_mobile']) && $extra['is_mobile']){
            $channel = 'mobile';
        }

        if (isset($extra['channel']) && !empty($extra['channel'])) {
            $channel = $extra['channel'];
        }

        $this->method = 'POST';

        if (isset($extra['home_link']) && !empty($extra['home_link'])) {
            $this->returnUrl = $extra['home_link'];
        }

        if (array_key_exists("extra", $extra)) {
            if(isset($extra['extra']['t1_lobby_url'])) {
                $this->returnUrl = $extra['extra']['t1_lobby_url'];
            }
		}

        if(isset($extra['game_mode']) && in_array($extra['game_mode'], $this->trial_mode)){
            $params = array(
                "gameId"        => $extra['game_code'],
                "channel"       => $channel,
                "currency"      => $this->currency,
                "language"      => $this->language,
                "org"           => $this->org,
            );
            return $this->callApi(self::API_queryDemoGame, $params, $context);
        }

        $params = array(
            "loginname"     => $gameUsername,
            "topOrg"        => $this->topOrg,
            "org"           => $this->org,
            "gameId"        => $extra['game_code'],
            "currency"      => $this->currency,
            "language"      => $this->language,
            "channel"       => $channel,
            "returnUrl"     => $this->returnUrl,
            "countryCode"   => $this->countryCode,
            "sign"          => $this->generateSign('player', $gameUsername),
        );

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $this->utils->debug_log("YGGDRASIL: (processResultForQueryForwardGame) params: ",json_encode($params));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = array('url'=>'');

		if($success){
            $result['url'] = (array_key_exists('data',$resultArr)?$resultArr['data']:'');
            $this->utils->debug_log("YGGDRASIL: (processResultForQueryForwardGame) url: ",$result['url']);
        }

		return array($success, $result);
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $this->utils->debug_log("YGGDRASIL: (createPlayer) playerName: $playerName");
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->utils->debug_log("YGGDRASIL: (createPlayer) gameUsername: $gameUsername");

        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForCreatePlayer',
            'gameUsername'      => $gameUsername,
            'playerId'          => $playerId,
            'playerName'        => $playerName
        );

        $params = array(
            "loginname" => $gameUsername,
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "currency"  => $this->currency,
            "sign"      => $this->generateSign('player', $gameUsername),
        );

        $this->method = 'POST';

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
        $this->utils->debug_log("YGGDRASIL: (processResultForCreatePlayer) params: ".json_encode($params));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

    public function isPlayerExist($playerName){
        $this->utils->debug_log("YGGDRASIL: (playerName) playerName: $playerName");
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForIsPlayerExist',
            'gameUsername'      => $gameUsername,
            'playerName'      => $playerName
        );

        $params = array(
            "loginname" => $gameUsername,
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "currency"  => $this->currency,
            "sign"      => $this->generateSign('player', $gameUsername),
        );

        $this->method = 'POST';

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if($success && $resultArr['code']) {
            $result['exists'] = true;
        } else {
            if($resultArr['code']==12){
                $success = true;
            }
            $result['exists'] = false;
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForQueryPlayerBalance',
            'gameUsername'      => $gameUsername,
            'playerName'        => $playerName
        );

        $params = array(
            "loginname" => $gameUsername,
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "currency"  => $this->currency,
            "sign"      => $this->generateSign('player', $gameUsername),
        );

        $this->method = 'POST';

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        if($success && array_key_exists('data', $resultArr)) {
            $result = array('balance' => floatval($resultArr['data']));
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if (empty($transfer_secure_id)) {
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        }

        $context = array(
            'callback_obj'              => $this,
            'callback_method'           => 'processResultForDepositToGame',
            'gameUsername'              => $gameUsername,
            'external_transaction_id'   => $transfer_secure_id,
            'amount'                    => $amount
        );

        $params = array(
            "loginname" => $gameUsername,
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "amount"    => $amount,
            "billno"    => $transfer_secure_id,
            "currency"  => $this->currency,
            "sign"      => $this->generateSign('player', $gameUsername),
        );

        $this->method = 'POST';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = @$resultArr['code'];
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if (empty($transfer_secure_id)) {
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        }

        $context = array(
            'callback_obj'              => $this,
            'callback_method'           => 'processResultForDepositToGame',
            'gameUsername'              => $gameUsername,
            'external_transaction_id'   => $transfer_secure_id,
            'amount'                    => $amount
        );

        $params = array(
            "loginname" => $gameUsername,
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "amount"    => $amount,
            "billno"    => $transfer_secure_id,
            "currency"  => $this->currency,
            "sign"      => $this->generateSign('player', $gameUsername),
        );

        $this->method = 'POST';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
        );

        return array($success, $result);
    }

    /**
     *  Allows to confirm if system recorded credit/withdraw request.
     */
    public function queryTransaction($transactionId, $extra) {
        $playerName         = isset($extra['playerName'])?$extra['playerName']:'';
        $gameUsername       = $this->getGameUsernameByPlayerUsername($playerName);
        $transfer_time      = isset($extra['transfer_time'])?$extra['transfer_time']:'';
        $startDateTime      = '';
        $endDateTime        = '';

        if(empty($transfer_secure_id)){
            $transfer_time = date('Y-m-d H:i:s');
        }

        if(!empty($transfer_time)){
            $startDateTime = new DateTime($extra['transfer_time']);
            $startDateTime->modify('-2 minutes');
            $endDateTime = new DateTime($extra['transfer_time']);
            $endDateTime->modify('+2 minutes');
        }

        $context = array(
            'callback_obj'              => $this,
            'callback_method'           => 'processResultForQueryTransaction',
            'gameUsername'              => $gameUsername,
            'external_transaction_id'   => $transactionId,
			'playerName'                => $playerName,
        );

        $params = array(
            "topOrg"    => $this->topOrg,
            "org"       => $this->org,
            "loginname" => $gameUsername,
            "billno"    => $transactionId,
            "currency"  => $this->currency,
            "startTime" => $this->serverTimeToGameTime($startDateTime->format("Y-m-d H:i:s")),
            "endTime"   => $this->serverTimeToGameTime($endDateTime->format("Y-m-d H:i:s")),
            "sign"      => $this->generateSign('merchant'),
        );

        $this->method = 'POST';

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $transId = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $this->CI->utils->debug_log('tianhao query response', $resultJsonArr, 'transaction id', $transId);

        $result = array(
            'response_result_id'      => $responseResultId,
            'external_transaction_id' => $transId,
			'status'                  =>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'               =>self::REASON_UNKNOWN
        );

        if($success && array_key_exists('data', $resultJsonArr)) {
            $validation_data = @$resultJsonArr['data'][0];
            if(isset($validation_data['billno'])
                && isset($validation_data['loginname'])
                && $validation_data['billno']==$transId
                && $validation_data['loginname']==$gameUsername){
                $result['reason_id'] = self::REASON_UNKNOWN;
                $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            }
        }

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        $this->utils->debug_log("YGGDRASIL: (syncOriginalGameLogs)");

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $this->utils->debug_log("YGGDRASIL: (syncOriginalGameLogs) startDate:",$startDate," endData: ", $endDate);

		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:s");


    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
    	}
        $this->utils->debug_log("YGGDRASIL: (syncOriginalGameLogs) queryDateTimeMax:",$queryDateTimeMax," queryDateTimeStart: ", $queryDateTimeStart);
        $result = [];
    	while ($queryDateTimeMax  > $queryDateTimeStart) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
            );

            $this->utils->debug_log("#############YGGDRASIL: (syncOriginalGameLogs) queryDateTimeStart:",$queryDateTimeStart," queryDateTimeEnd: ", $queryDateTimeEnd);

            $params = array(
                "topOrg"        => $this->topOrg,
                "org"           => $this->org,
                "lastId"        => 0,
                //"loginname"     => '',
                "currency"      => $this->currency,
                "startTime"     => $queryDateTimeStart,
                "endTime"       => $queryDateTimeEnd,
                "sign"          => $this->generateSign('merchant'),
                "gametype"      => $this->gametype
            );

            $this->method = 'POST';

			$result[] = $this->callApi(self::API_syncGameRecords, $params, $context);

            $queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
	    	}
		}

		return array("success" => true, "results"=>$result);
	}

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);

        //check if call success
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        if(!$success) {
            return array(false);
        }

        $gameRecords = array();
        if(array_key_exists('data', $resultArr)) {
            $gameRecords = $resultArr['data'];
        }

        $result = ['data_count' => 0];

        if(!empty($gameRecords) && is_array($gameRecords)){
            # add in columns not returned by API, and process username column to remove suffix
            foreach($gameRecords as $index => $record) {
                $gameRecords[$index]['external_uniqueid'] = $gameRecords[$index]['id'];
                $gameRecords[$index]['response_result_id'] = $responseResultId;
                $gameRecords[$index]['gameId'] = $gameRecords[$index]['gameName'];
                $gameRecords[$index]['player_id'] = '';

                //convert game time to server time
                $gameRecords[$index]['createTime'] = $this->gameTimeToServerTime($gameRecords[$index]['createTime']);
            }

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
        }

        return array(true, $result);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {

                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->utils->debug_log('YGGDRASIL (syncMergeToGameLogs)');

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            true);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $this->CI->utils->debug_log('YGGDRASIL (queryOriginalGameLogs)');
        //to watchout for game name changes when sync

        $sql = <<<EOD
SELECT
    gd.game_type_id,
    gd.id as game_description_id,
    gd.game_code,
    original.gameName as game_type,
    original.gameName as game_name,

    game_provider_auth.player_id,
    original.loginname as player_username,

    original.type as bet_type,
    original.amount as bet_amount,
    original.amount as real_bet,
    original.gameId,
    original.afterAmount as after_balance,
    original.beforeAmount as before_balance,

    COALESCE(original_endwager.afterAmount, 0) as endwager_after_balance,
    COALESCE(original_endwager.beforeAmount, 0)  as endwager_before_balance,
    COALESCE(original_endwager.amount, 0)  as endwager_amount,

    COALESCE(original_cancelwager.afterAmount, 0) as cancelwager_after_balance,
    COALESCE(original_cancelwager.beforeAmount, 0) as cancelwager_before_balance,
    COALESCE(original_cancelwager.amount, 0) as cancelwager_amount,

    COALESCE(original_appendwager.afterAmount, 0) as appendwager_after_balance,
    COALESCE(original_appendwager.beforeAmount, 0) as appendwager_before_balance,
    COALESCE(original_appendwager.amount, 0) as appendwager_amount,

    original.createTime as bet_at,

    original.external_uniqueid,
    original.md5_sum,
    original.response_result_id,
    original.id as sync_index,
    gd.game_name as game_description_name
FROM
    {$this->original_gamelogs_table} as original
    LEFT JOIN game_description AS gd ON gd.external_game_id = original.gameId AND gd.game_platform_id = ?
    JOIN game_provider_auth ON original.loginname = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    LEFT JOIN {$this->original_gamelogs_table} as original_endwager ON original.reference = original_endwager.reference AND original_endwager.type='endWager'
    LEFT JOIN {$this->original_gamelogs_table} as original_cancelwager ON original.reference = original_cancelwager.reference AND original_cancelwager.type='cancelWager'
    LEFT JOIN {$this->original_gamelogs_table} as original_appendwager ON original.reference = original_appendwager.reference AND original_appendwager.type='appendWagerResult'
    WHERE
    original.createTime BETWEEN ? AND ? AND original.type = 'wager'
GROUP BY original.reference;
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $this->CI->utils->debug_log('YGGDRASIL (makeParamsForInsertOrUpdateGameLogsRow)');
        if(empty($row['md5_sum'])){
            $this->CI->utils->debug_log('YGGDRASIL (makeParamsForInsertOrUpdateGameLogsRow=>generateMD5SumOneRow)');
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if($row['bet_type']<>'wager'){
            //return [];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => ($row['endwager_amount'] + $row['cancelwager_amount'] + $row['appendwager_amount']) - $row['bet_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => max($row['endwager_after_balance'], $row['cancelwager_after_balance'], $row['appendwager_after_balance'])
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['bet_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => '',
            'extra' => [],
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
        if($row['cancelwager_amount'] > 0){
            $row['status'] = Game_logs::STATUS_CANCELLED;
        }
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['gameId'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['gameId']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    function login($userName, $password = null) {
        return $this->returnUnimplemented();
    }

    function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return $this->returnUnimplemented();
    }

}