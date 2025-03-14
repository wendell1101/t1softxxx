<?php
/**
 * BAISON game integration
 * OGP-12813
 *
 * @author 	Jerbey Capoquian
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__).'/baison_api_utils.php';
require_once dirname(__FILE__).'/baison_betdetails_module.php';

class Game_api_baison extends Abstract_game_api {
	use baison_api_utils;
	use baison_betdetails_module;

	const ORIGINAL_LOGS_TABLE_NAME = 'baison_game_logs';
	const LOGIN_ACTION = 1;
	const QUERY_BALANCE_ACTION = 6;
	const DEPOSIT_ACTION = 2;
	const WITHDRAW_ACTION = 3;
	const QUERY_TRANSACTION_ACTION = 5;
	const QUERY_GAME_RECORD = 9;

	const DEFAULT_CHARGE_POINTS = 0;
	const DEFAULT_SERVER_TYPE = 1000;
	const SUCCESS = 0;

	const TRANSACTION_INEXISTENCE = 0;
	const TRANSACTION_FAILURE = 1;
	const TRANSACTION_SUCCESS = 2;

	const MD5_FIELDS_FOR_ORIGINAL=['round_id', 'card_value', 'init_balance', 'all_bet', 'avail_bet', 'profit','revenue','balance','start_time','end_time','jackpot','holdem_buy_insurance','holdem_buy_card'];
	const MD5_FLOAT_AMOUNT_FIELDS=['init_balance', 'all_bet', 'avail_bet', 'profit', 'revenue','balance','jackpot','holdem_buy_insurance','holdem_buy_card'];
	const MD5_FIELDS_FOR_MERGE=['game_id', 'round_number', 'card_value', 'init_balance', 'bet_amount','real_bet_amount', 'result_amount', 'revenue','after_balance','jackpot','holdem_buy_insurance','holdem_buy_card','start_at','end_at'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['init_balance', 'bet_amount','real_bet_amount','result_amount','jackpot','holdem_buy_insurance','holdem_buy_card'];


	//GAMES
	const hall = 1000;
	const Cannon_Fishing = 1001;
	const Circle_Tiles = 1002;
	const Dragon_Tiger = 1003;
	const Golden_Flower = 1004;
	const Pai_Gow = 1005;
	const Banker_niu_niu = 1006;
	const Red_Black = 1007;
	const Thirteen_Poker = 1008;
	const Landlords = 1009;
	const Texas_holdem = 1010;
	const Baccarat = 1011;
	const Three_facecard = 1012;
	const Run_Fast = 1013;
	const Speed_Flower = 1014;
	const Blackjack = 1015;
	const Roulette = 1016;
	const Ten_Point_Half = 1017;
	const King_of_Fishing = 1018;
	const Multi_Players_Baccarat = 1019;
	const Multi_Players_Niu_Niu = 1020;
	const Banker_Niu_Niu_Cards_Shown = 1021;

	const NO_PLAYER = 000000;
	const BYTES_PER_CARD = 2;
	const DEFAULT_BYTES_OCCUPY = 6;
	const cards_expression =  array(
		"suit" => array(
			'1' => '&#9830',//diamonds
			'2' => '&#9827',//clubs
			'3' => '&#9829',//hearts
			'4' => '&#9824',//spades
			'5' => 'joker',
			'0' => ''
		),
		"value" => array(
			'1' => 'A',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'A' => '10',
			'B' => 'J',
			'C' => 'Q',
			'D' => 'K',
			'E' => 'black',//black joker
			'F' => 'red',//red joker
			'0' => ''
		)
	);

	const card_mode = array(
		'00' => 'oolong',
		'01' => 'one pair',
		'02' => 'two pairs',
		'03' => 'three pairs',
		'04' => 'straight',
		'05' => 'flush',
		'06' => 'gourd',
		'07' => 'Four of a Kind',
		'08' => 'straight flush',
		'14' => 'three flush',
		'15' => 'three straight',
		'16' => 'six pairs and half',
		'17' => 'five pairs and three of a kind',
		'18' => 'four set and three of a kind',
		'19' => 'gather together color',
		'20' => 'all small',
		'21' => 'all big',
		'22' => 'the thrived',
		'23' => 'three straight flush',
		'24' => '12 the royal flush',
		'25' => '13 water',
		'26' => 'the supreme tsing lung',
	);

	public function __construct() {
		parent::__construct();
		$this->apiUrl = $this->getSystemInfo('url','https://api.yly0707.com/Api/interface');//defaul api url for ole
		$this->recordUrl = $this->getSystemInfo('recordUrl','https://api.yly0707.com/Api/interface');//defaul record api url for ole
		$this->agentId = $this->getSystemInfo('agentId',1001);//defaul agent number for ole client
		$this->moneyType = $this->getSystemInfo('moneyType','RMB');//defaul money type(currency)
		$this->subChannelId = $this->getSystemInfo('subChannelId','bbr');//defaul subchanel id for ole client
		$this->lang = $this->getSystemInfo('lang','zh-CN');//default language , currenctly they allow simplified language only
		$this->md5Key = $this->getSystemInfo('md5Key','7R87Sri2yibz4Rta');//defaul md5Key 
		$this->aesKey = $this->getSystemInfo('aesKey','Nw6jKsatxQHkxz8W');//defaul aesKey 
		$this->serverType = self::DEFAULT_SERVER_TYPE;
		$this->isRedirect = $this->getSystemInfo('isRedirect',false); 
		$this->allowGenerateBetDetails = $this->getSystemInfo('allowGenerateBetDetails',false); //default false
		$this->betDetailUrl = $this->getSystemInfo('betDetailUrl','https://admin.yly0707.com/avoid/game-record');
		$this->adjustEndDate = $this->getSystemInfo('adjustEndDate','-3 minutes');
	}

	public function getPlatformCode() {
		return BAISON_GAME_API;
	}

	public function generateUrl($apiName, $params) {
		$timestamp = $this->microtime_int();
		$url = ($apiName != self::API_isPlayerOnline) ? $this->apiUrl : $this->recordUrl;
    	$url .= '?' . http_build_query(array(
            'channel_id' => $this->agentId,
            'timestamp' => $timestamp,
            'param' => $this->mcrypt_desEncode($this->aesKey, $params),
            'key' => md5($this->agentId.$timestamp.$this->md5Key)
        ));
        return $url;
	}
	
	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = ($resultArr['code'] == self::SUCCESS) ? true : false;
		$result = array();
		if(!$success){
			$this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('BAISON got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);
		$orderId = $this->agentId.$this->utils->getDatetimeNow().$gameUsername;
		$balance = 0;
		$params = http_build_query(array(
            'action' => self::LOGIN_ACTION,
            'account' => $gameUsername,
            'money' => self::DEFAULT_CHARGE_POINTS,
            'money_type' =>  $this->moneyType,
            'order_id' => $orderId,
            'server_type'=> $this->serverType,
            'lang' => $this->lang
        ));

		$this->CI->utils->debug_log('-----------------------baison createPlayer params ----------------------------',$params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['code'] = $arrayResult['code'];
			$result['msg'] = $arrayResult['msg'];
		}
		return array($success, $result);
	}

	public function isPlayerExist($playerName) {
		$result = $this->queryPlayerBalance($playerName);
		$playerId = $this->getPlayerIdInPlayer($playerName);
		$success = $result['success'];
		if($success && $result['exists']){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	
		}
		return $result;
	}


	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

        $params = http_build_query(array(
			'action' => self::QUERY_BALANCE_ACTION,
			'money_type' => $this->moneyType,
			'account' => $gameUsername
		));

		$this->CI->utils->debug_log('-----------------------baison queryPlayerBalance params ----------------------------',$params);
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$result["balance"] = $this->round_down(floatval(@$arrayResult['result']['totalMoney']));// get balance
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
	}

	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}


	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$orderId = $this->agentId.$this->utils->getDatetimeNow().$gameUsername;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'external_transaction_id'=> $orderId
		);

		$params = http_build_query(array(
			'action' => self::DEPOSIT_ACTION,
			'account' => $gameUsername,
			'order_id' => $orderId,
			'money' => $amount,
            'money_type' => $this->moneyType
		));

		$this->CI->utils->debug_log('-----------------------baison depositToGame params ----------------------------',$params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		if($success){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}  else {
			if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }else{
				$status = $arrayResult['code'];
				$result['reason_id'] = $this->getTransferErrorReasonCode($status);
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
        }
		return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$orderId = $this->agentId.$this->utils->getDatetimeNow().$gameUsername;
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'external_transaction_id'=> $orderId
		);

		$params = http_build_query(array(
			'action' => self::WITHDRAW_ACTION,
			'account' => $gameUsername,
			'order_id' => $orderId,
			'money' => $amount,
            'money_type' => $this->moneyType
		));

		$this->CI->utils->debug_log('-----------------------baison withdrawFromGame params ----------------------------',$params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
        );

        if($success){
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        } else {
        	$status = $arrayResult['code'];
        	$result['reason_id'] = $this->getTransferErrorReasonCode($status);
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }
		return array($success, $result);
    }

    public function getTransferErrorReasonCode($apiErrorCode) {
    	/*
		5000 system internal error
		5006 IP not exist in access control list(ACL)
		5106 Account forbidden
		5200 the order is duplicate
		6000 system maintenance
		*/
		switch ((int)$apiErrorCode) {
			case 5000:
				$reasonCode = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;						
				break;
			case 5006:
				$reasonCode = self::REASON_IP_NOT_AUTHORIZED;						
				break;
			case 5106:
				$reasonCode = self::REASON_GAME_ACCOUNT_LOCKED;						
				break;
			case 5200:
				$reasonCode = self::REASON_DUPLICATE_TRANSFER;						
				break;
			case 6000:
				$reasonCode = self::REASON_API_MAINTAINING;						
				break;
			default:
				$reasonCode = self::REASON_UNKNOWN;
		}

		return $reasonCode;
	}

	public function queryForwardGame($playerName, $extra = null) {
		if(!empty($extra['game_code'])){
			$this->serverType = $extra['game_code'];
		}

		$loginResult = $this->login($playerName);
		$success = $this->processResultBoolean(@$loginResult['response_result_id'], $loginResult, $playerName);
		$result['success'] = false;
		$result['redirect'] = $this->isRedirect;
		if($success){
			$result['success'] = true;
			$result['url'] = @$loginResult['result']['url'];	
			$this->CI->utils->debug_log('-----------------------baison queryForwardGame url ----------------------------',$result['url'] );
		}
		return $result;
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh-CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case "en-us":
                $language = 'en';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$currentDateTime = $this->utils->formatDateMinuteForMysql(new DateTime());
		$endDateTime = $this->utils->formatDateMinuteForMysql($endDate);
		if($currentDateTime == $endDateTime){
			$endDate->modify($this->adjustEndDate);
		}
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$result = array();
		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+60 minutes', function($startDate, $endDate)  {
			$startDate = strtotime($startDate->format('Y-m-d H:i:s'));
			$endDate = strtotime($endDate->format('Y-m-d H:i:s'));
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);

	        $params = http_build_query(array(
	            'action' => self::QUERY_GAME_RECORD,
				'start_time' => $startDate,
				'money_type' => $this->moneyType,
				'end_time' => $endDate
			));

			$this->CI->utils->debug_log('-----------------------baison syncOriginalGameLogs params ----------------------------',$params);
			return $this->callApi(self::API_syncGameRecords, $params, $context);
	    });
	    return array(true, $result);
	}

	public function processGameRecords(&$gameRecords, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$data['user_id'] = isset($record['user_id']) ? $record['user_id'] : null;
				$data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
				$data['room_id'] = isset($record['room_id']) ? $record['room_id'] : null;
				$data['table_id'] = isset($record['table_id']) ? $record['table_id'] : null;
				$data['seat_id'] = isset($record['seat_id']) ? $record['seat_id'] : null;
				$data['user_count'] = isset($record['user_count']) ? $record['user_count'] : null;
				$data['round_id'] = isset($record['round_id']) ? $record['round_id'] : null;
				$data['card_value'] = isset($record['card_value']) ? $record['card_value'] : null;
				$data['init_balance'] = isset($record['init_balance']) ? $record['init_balance'] : null;
				$data['all_bet'] = isset($record['all_bet']) ? $record['all_bet'] : null;
				$data['avail_bet'] = isset($record['avail_bet']) ? $record['avail_bet'] : null;
				$data['profit'] = isset($record['profit']) ? $record['profit'] : null;
				$data['revenue'] = isset($record['revenue']) ? $record['revenue'] : null;
				$data['balance'] = isset($record['balance']) ? $record['balance'] : null;
				$data['start_time'] = isset($record['start_time']) ? $this->gameTimeToServerTime($record['start_time']) : null;
				$data['end_time'] = isset($record['end_time']) ? $this->gameTimeToServerTime($record['end_time']) : null;
				$data['channel_id'] = isset($record['channel_id']) ? $record['channel_id'] : null;
				$data['sub_channel_id'] = isset($record['sub_channel_id']) ? $record['sub_channel_id'] : null;
				$data['room_type'] = isset($record['room_type']) ? $record['room_type'] : null;
				$data['jackpot'] = isset($record['jackpot']) ? $record['jackpot'] : null;
				$data['holdem_buy_insurance'] = isset($record['holdem_buy_insurance']) ? $record['holdem_buy_insurance'] : null;
				$data['holdem_buy_card'] = isset($record['holdem_buy_card']) ? $record['holdem_buy_card'] : null;
				$data['game_type'] = isset($record['game_type']) ? $record['game_type'] : null;
				//default data
				$data['game_externalid'] = $data['game_id'];
				$data['external_uniqueid'] = $data['user_id'].'_'.$data['round_id'];
				$data['response_result_id'] = $responseResultId;
				$gameRecords[$index] = $data;
				unset($data);
			}
		}
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, null);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0
		);
		if($success){
			// echo "<pre>";
			// print_r($arrayResult);exit();
			$gameRecords = $arrayResult['result']['records'];
			$this->processGameRecords($gameRecords, $responseResultId);
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
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
		return array($success, $dataResult);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime='bg.start_time >= ? and bg.end_time <= ?';
		$sql = <<<EOD
SELECT bg.id as sync_index,
bg.user_id as player_username,
bg.game_id,
bg.room_id,
bg.table_id,
bg.seat_id,
bg.round_id as round_number,
bg.card_value,
bg.init_balance,
bg.all_bet as real_bet_amount,
bg.avail_bet as bet_amount,
bg.profit as result_amount,
bg.revenue,
bg.balance as after_balance,
bg.init_balance,
bg.jackpot,
bg.holdem_buy_insurance,
bg.holdem_buy_card,
bg.game_externalid as game_code,
bg.external_uniqueid,
bg.updated_at,
bg.md5_sum,
bg.response_result_id,
bg.game_id as game,
bg.start_time as bet_at,
bg.start_time as start_at,
bg.end_time as end_at,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM baison_game_logs as bg
LEFT JOIN game_description as gd ON bg.game_externalid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON bg.user_id = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[];
    	$has_both_side=0;

    	if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
        	//set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
    	$this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        // $row['bet_details']= 'diamonds&#9830';
        $row['bet_details']= $this->generateBetDetails($row);
        $row['status'] = Game_logs::STATUS_SETTLED;
        // $profit = $row['result_amount'];
        // if($row['game_id'] == self::Texas_holdem){
        // 	$profit = $row['result_amount'] + $row['jackpot'] + $row['holdem_buy_insurance'] + $row['holdem_buy_card'];
        // }
        $row['result_amount'] = $row['after_balance'] - $row['init_balance'];
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
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
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


	public function login($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);
		$orderId = $this->agentId.$this->utils->getDatetimeNow().$gameUsername;
		$balance = 0;
		$params = http_build_query(array(
            'action' => self::LOGIN_ACTION,
            'account' => $gameUsername,
            'money' => ($password) ? self::DEFAULT_CHARGE_POINTS : $balance,
            'money_type' =>  $this->moneyType,
            'order_id' => $orderId,
            'server_type'=> $this->serverType,
            'lang' => $this->lang
        ));
		// echo "<pre>";
		// print_r($params);exit();
		$this->CI->utils->debug_log('-----------------------baison login params ----------------------------',$params);
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['result'] = $arrayResult['result'];
			$result['time'] = $arrayResult['time'];
			$result['code'] = $arrayResult['code'];
			$result['msg'] = $arrayResult['msg'];
		}
		return array($success,$result);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	/*public function getGameTimeToServerTime() {
		//return '+8 hours';
	}*/

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	/*public function getServerTimeToGameTime() {
		//return '-8 hours';
	}*/

	public function queryTransaction($transactionId, $extra) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId
		);

        $params = http_build_query(array(
			'action' => self::QUERY_TRANSACTION_ACTION,
			'order_id' => $transactionId,
		));

		$this->CI->utils->debug_log('-----------------------baison queryTransaction params ----------------------------',$params);
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
        );

        if ($success) {
            switch ($arrayResult['result']['status']) {
                case self::TRANSACTION_INEXISTENCE:
                    $result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                    $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
                    $this->CI->utils->debug_log('processResultForQueryTransaction ===========> External transaction id not found!');
                    break;
                case self::TRANSACTION_SUCCESS:
                    $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                    break;
                case self::TRANSACTION_FAILURE:
                    $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
                    break;
            }

        }else{
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        $this->CI->utils->debug_log('processResultForQueryTransaction ===========>',$arrayResult);
        return array($success, $result);
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	
}
