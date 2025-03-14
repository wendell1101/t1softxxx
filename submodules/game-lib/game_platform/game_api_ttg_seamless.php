<?php

/**
 * Game Provider: TTG
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @bread.php.ph
 * Ticket: OGP-29585
 * 
 * By function:

 *
 * 
 * Related Files
    -routes.php
    -ttg_seamless_service_api.php
 **/
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ttg_seamless extends Abstract_game_api
{
    //default params
    public $CI;
    public $http_headers = array('Content-Type' => 'application/xml');
    public $api_url;
    public $currency;
    public $language;
    public $country;
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $use_transaction_data;
    public $use_bet_time;

    //to check
    public $game_provider_gmt;
    public $game_provider_date_time_format;
    public $original_seamless_wallet_transactions_table;
    public $seamless_debit_transaction_type;


    //TTG params
    public $launcher_url;
    public $launcher_url_demo;
    public $partner_id_1;
    public $partner_id_2;
    public $partner_id_3;
    public $seamless_wallet;
    public $add_partner_id_to_game_launch;
    public $tester;
    public $partners;

    #transaction types
    const TRANSACTION_TYPE_BET = 400;
    const TRANSACTION_TYPE_WIN = 410;
    // const TRANSACTION_TYPE_BONUS = 150;

    const URI_MAP = array(
        self::API_login => '/cip/gametoken/{uid}',
    );
    const SEAMLESS_TRANSACTION_TYPE_DEBIT = [
        'bet',
        'adjustment deduct',
    ];

    const FLAG_WALLET_TRANSACTION_0 = 0;
    const FLAG_WALLET_TRANSACTION_UPDATED = 1;

    const MD5_FIELDS_FOR_MERGE_FROM_TRANS = [
        'player_id',
        'before_balance',
        'after_balance',
        'game_code',
        'transaction_type',
        'status',
        'response_result_id',
        'external_uniqueid',
        'start_at',
        'end_at',
        'updated_at',
        'round_id',
        'bet_amount',
        'win_amount',
        'result_amount',
        'txnsubtypeid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'result_amount',
    ];

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return TTG_SEAMLESS_GAME_API;
    }

    public function __construct()
    {
        parent::__construct();
        #default params
        $this->api_url = $this->getSystemInfo('url');
        $this->currency = $this->getSystemInfo('currency', $this->utils->getDefaultCurrency());
        $this->language = $this->getSystemInfo('language', 'en');
        $this->country = $this->getSystemInfo('country');
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'ttg_seamless_wallet_transactions');
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);
        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', self::SEAMLESS_TRANSACTION_TYPE_DEBIT);

        #TTG params
        $this->launcher_url = $this->getSystemInfo('launcher_url');
        $this->launcher_url_demo = $this->getSystemInfo('launcher_url_demo', 'http://pff.ttms.co/casino/default/game/game.html');
        $this->partner_id_1 = $this->getSystemInfo('partner_id_1'); //zero
        $this->partner_id_2 = $this->getSystemInfo('partner_id_2'); //T1
        $this->partner_id_3 = $this->getSystemInfo('partner_id_3'); //T1Seamlessidr
        $this->seamless_wallet = $this->getSystemInfo('seamless', true);
        $this->add_partner_id_to_game_launch = $this->getSystemInfo('add_partner_id_to_game_launch', true);
        $this->tester = $this->getSystemInfo('tester', false);
        $this->partners = array(
			'partner' => array(
				array(
					'partnerId_attr' => $this->partner_id_1,
					'partnerType_attr' => 0,
				),
				array(
					'partnerId_attr' => $this->partner_id_2,
					'partnerType_attr' => 1,
				),
				array(
					'partnerId_attr' => $this->partner_id_3,
					'partnerType_attr' => 1,
				),
			),
		);
    }

    public function queryForwardGame($playerName, $extra)
    {
        $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'Extra:', $extra);
        #exit if empty game code 
        if (empty($extra['game_code'])) {
            $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'Game code is empty');
            return array(
                'success' => false,
                'url' => '',
            );
        }

        #get gameId, gameType and gameName
        $resultArray = $this->getGameLaunchParamsByGameCode($extra['game_code']);

        #exit if game code is not found
        if (empty($resultArray['gameId'])) {
            $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'Game ID is not found');
            return array(
                'success' => false,
                'url' => '',
            );
        }

        #set params
        $params = array(
            'gameId' => $resultArray['gameId'],
            'gameName' => $resultArray['gameName'],
            'gameType' => $resultArray['gameType'],
            'deviceType' => !$extra['is_mobile'] ? "web" : "mobile",
            'lang' => $this->language,
        );

        #game mode
        if (in_array($extra['game_mode'], $this->demo_game_identifier)) {
            $params['playerHandle'] = 999999;
            $params['account'] = 'FunAcct';

            $url = $this->launcher_url_demo . '?' . http_build_query($params);
        } else {

            $result = $this->login($playerName);

            if (!$result['success']) {
                return $result;
            }

            $params['playerHandle'] = $result['token'];
            $params['account'] = $this->currency;

            $url = $this->launcher_url . '?' . http_build_query($params);

            if ($this->add_partner_id_to_game_launch) {
                $url .= "&lsdId=" . $this->partner_id_3;
            }
        }

        $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, $params);

        return array(
            'success' => true,
            'url' => $url,
        );
    }

    public function getGameLaunchParamsByGameCode($gameCode)
    {
        $this->CI->load->model('game_description_model');
        $gameDescription = $this->CI->game_description_model->queryAttributeByGameCode2($this->getPlatformCode(), $gameCode);
        $attrDecode = json_decode($gameDescription, true);
        
        return array(
            'gameId' => !empty($attrDecode['gameId']) ? $attrDecode['gameId'] : null,
            'gameName' => !empty($attrDecode['game_launch_code']) ? $attrDecode['game_launch_code'] : null,
            'gameType' => !empty($attrDecode['gameType']) ? $attrDecode['gameType'] : null,
        );
    }


    public function login($playerName, $password = NULL)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
        );

        $params = array(
            'uid' => $playerName,
            'logindetail' => array(
                'player' => array(
                    'account_attr' => $this->currency,
                    'country_attr' => $this->country,
                    'nickName_attr' => $playerName,
                    'userName_attr' => $playerName,
                    'partnerId_attr' => $this->partner_id_3,
                    'commonWallet_attr' => $this->seamless_wallet,
                    'tester_attr' => $this->tester,
                ),
                'partners' => $this->partners,
            ),
        );

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultXml = (array) $this->getResultXmlFromParams($params);
		$resultXml = $resultXml['@attributes'];
		$success = $this->processResultBoolean($responseResultId, $resultXml);
		return array($success, $resultXml);
	}

    public function processResultBoolean($responseResultId, $resultXml, $playerName = null) {
		$success = !empty($resultXml);
		return $success;
	}


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for TTG Game";
        if ($return) {
            $success = true;
            $message = "Successfull create account for TTG Game.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function isPlayerExist($playerName)
    {
        return ['success' => true, 'exists' => $this->isPlayerExistInDB($playerName)];
    }

    public function getSeamlessTransactionTable()
    {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            "success" => true,
            "external_transaction_id" => $external_transaction_id,
            "response_result_id" => null,
            "didnot_insert_game_logs" => true
        ];
    }

    public function syncOriginalGameLogs($token = false)
    {
        $result = 0;
        if($this->use_transaction_data){
            $result = $this->syncOriginalGameLogsFromTrans($token);
        }
        return ['success' => true, $result];
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle
        );
    }

    public function syncOriginalGameLogsFromTrans($token =  false)
    {
        $this->CI->load->model('original_seamless_wallet_transactions');

        $sqlTime = 'win_transaction.updated_at BETWEEN ? AND ?';

        if ($this->use_bet_time) {
            $sqlTime = 'bet_transaction.start_at BETWEEN ? AND ?';
        }

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');

        $result = $this->syncOriginalGameLogsFromTransSettled($queryDateTimeStart, $queryDateTimeEnd, $sqlTime);

        return $result;
    }

    public function syncOriginalGameLogsFromTransSettled($queryDateTimeStart, $queryDateTimeEnd, $sqlTime){
        $sql = <<<EOD
UPDATE 
{$this->original_seamless_wallet_transactions_table}
AS bet_transaction
JOIN
{$this->original_seamless_wallet_transactions_table}
AS win_transaction
ON bet_transaction.round_id = win_transaction.round_id
SET
bet_transaction.win_amount = win_transaction.win_amount,
bet_transaction.result_amount = win_transaction.win_amount - bet_transaction.bet_amount
WHERE bet_transaction.txnsubtypeid = ? AND win_transaction.txnsubtypeid = ? AND {$sqlTime}
EOD;
        $params = [
            self::TRANSACTION_TYPE_BET,
            self::TRANSACTION_TYPE_WIN,
            $queryDateTimeStart,
            $queryDateTimeEnd,
        ];

        $update_count = $this->CI->original_seamless_wallet_transactions->runRawUpdateInsertSQL($sql, $params, null);
        $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'sql', $sql, 'params', $params, 'count', $update_count);
        return $update_count;
    }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = true)
    {
        $this->CI->load->model('original_game_logs_model');

        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $transactionTypeForGameLogs = '(?)';

        $sql = <<<EOD
SELECT
transaction.id as sync_index,
transaction.player_id,
transaction.game_username as player_username,
transaction.before_balance,
transaction.after_balance,
transaction.game_code,
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.start_at,
transaction.end_at,
transaction.updated_at,
transaction.md5_sum,
transaction.round_id,
transaction.bet_amount,
transaction.win_amount,
transaction.result_amount,
transaction.txnsubtypeid,
game_description.id as game_description_id,
game_description.game_type_id,
game_description.english_name as game

FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.txnsubtypeid IN {$transactionTypeForGameLogs} AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::TRANSACTION_TYPE_BET,
            $dateFrom,
            $dateTo,
        ];

        $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $data = [
            'game_info' => [
                'game_type_id' => !empty($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => !empty($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'game_type' => !empty($row['game_type']) ? $row['game_type'] : null,
                'game' => !empty($row['game']) ? $row['game'] : null,
            ],
            'player_info' => [
                'player_id' => !empty($row['player_id']) ? $row['player_id'] : null,
                'player_username' => !empty($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => 0,
                'loss_amount' => 0,
                'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => !empty($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_id']) ? $row['round_id'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => !empty($row['transaction_type']) ? $row['transaction_type'] : null,
            ],
            'bet_details' => 'N/A',
            'extra' => [],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('TTG_SEAMLESS_GAME_API:'.__METHOD__.'-LINE-'.__LINE__, 'data', $data);
        return $data;

    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set after balance
        if (!empty($row['after_balance'])) {
            $row['after_balance'] = $row['after_balance'] + $row['win_amount'];
        }

        #set win/loss amount 
        $winLossAmount = $row['win_amount'] - $row['bet_amount'];

        if ($winLossAmount >= 0) {
            $row['win_amount'] = $winLossAmount;
            $row['loss_amount'] = 0.00;
        }else {
            $row['win_amount'] = 0.00;
            $row['loss_amount'] = (-1) * $winLossAmount;
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_code = !empty($row['game_code']) ? $row['game_code'] : null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
        } else {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $game_code, $game_code);
        }

        return array($game_description_id, $game_type_id);
    }

    public function generateUrl($apiName, $params)
    {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        return $url;
    }

    protected function getHttpHeaders($params) {
		return $this->http_headers;
	}

	protected function customHttpCall($ch, $params) {

		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

		preg_match_all("#\{([^\}]+)\}#", $url, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$url = str_replace($match[0], $params[$match[1]], $url);
			unset($params[$match[1]]);
		}

		curl_setopt($ch, CURLOPT_URL, $url);

		if (!empty($params)) {
			if (count($params) == 1 && is_array(current($params))) {
				curl_setopt($ch, CURLOPT_POST, TRUE);
				$xml = $this->CI->utils->arrayToXml($params);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			} else {
				$url .= '?' . http_build_query($params);
			}
		}

	}
}