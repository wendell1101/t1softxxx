<?php

/**
 * Game Provider: ULTRAPLAY
 * Game Type: Sports and E-Sports
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @bread.php.ph
 * Ticket: OGP-30853
 * 
 * By function:

 *
 * 
 * Related Files
    -routes.php
    -ultraplay_seamless_service_api.php
 **/
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ultraplay_seamless extends Abstract_game_api
{
    //default params
    public $CI;
    public $http_headers = array('Content-Type' => 'application/json');
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
    public $game_provider_gmt;
    public $game_provider_date_time_format;
    public $original_seamless_wallet_transactions_table;
    public $seamless_debit_transaction_type;
    
    //default params
    const SEAMLESS_TRANSACTION_TYPE_DEBIT = [
        'bet',
    ];

    const DEBUG_KEY = 'ULTRAPLAY_SEAMLESS: ';

    const MD5_FIELDS_FOR_MERGE_FROM_TRANS = [
        'status',
        'start_at',
        'end_at',
        'updated_at',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
    ];

    //Ultraplay params
    protected $odd_format;
    protected $token_length;
    public $api_version;
    public $balance_mode;
    public $stake_factor;
    public $view_format;    
    public $return_url;
    public $allowed_bet_types;

	const STATUS_SUCCESS = 'OK';
	const STATUS_ERROR = 'ERROR';

    const BET_TYPE_SINGLE = 'Single';
    const BET_TYPE_COMBO = 'Combo';
    const BET_TYPE_SYSTEM = 'System';
    const BET_TYPE_IF_BET = 'Ifbet';
    const BET_TYPE_REVERSE_BET = 'Reversebet';
    const BET_TYPE_TEASER = 'Teaser';
    const SEAMLESS_TRANSACTION_TYPE_PLACE_BET = 'PlaceBet';

    const ALLOWED_BET_TYPES = [
        self::BET_TYPE_SINGLE,
        self::BET_TYPE_COMBO,
        self::BET_TYPE_SYSTEM,
        self::BET_TYPE_IF_BET,
        self::BET_TYPE_REVERSE_BET,
        self::BET_TYPE_TEASER,
    ];

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return ULTRAPLAY_SEAMLESS_GAME_API;
    }

    public function __construct()
    {
        parent::__construct();
        #default params
        $this->api_url = $this->getSystemInfo('url', '');
        $this->currency = $this->getSystemInfo('currency', $this->utils->getDefaultCurrency());
        $this->language = $this->getSystemInfo('language', 'en-US');
        $this->country = $this->getSystemInfo('country');
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'ultraplay_seamless_wallet_transactions');
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);
        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', self::SEAMLESS_TRANSACTION_TYPE_DEBIT);

        #ULTRAPLAY params
		$this->token_length = $this->getSystemInfo('token_length', 100);
		$this->api_version = $this->getSystemInfo('api_version', "2");
		$this->odd_format = $this->getSystemInfo('odd_format', 'decimal');
        $this->view_format = $this->getSystemInfo('view_format', 'asian');
        $this->return_url = $this->getSystemInfo('return_url', '');
		$this->balance_mode = $this->getSystemInfo('balance_mode', "Normal");
		$this->stake_factor = $this->getSystemInfo('stake_factor', 1);
        $this->allowed_bet_types = $this->getSystemInfo('allowed_bet_types', self::ALLOWED_BET_TYPES);
    }

    public function getSeamlessTransactionTable()
    {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function isPlayerExist($playerName)
    {
        return ['success' => true, 'exists' => $this->isPlayerExistInDB($playerName)];
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

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create Account for ULTRAPLAY Game";
        if ($return) {
            $success = true;
            $message = "Successfull create account for ULTRAPLAY Game.";
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array("success" => $success, "message" => $message);
    }

    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $loginToken = $this->generateToken($gameUsername);
        
        $url = $this->api_url . '?' . http_build_query(array(
			'loginToken' => $loginToken,
			'deviceType' => isset($extra['deviceType']) ? $extra['deviceType'] : 'desktop' ,
			'lang' => $this->language,
			'oddformat' => $this->odd_format,
            'viewFormat' => $this->view_format,
            'returnURL' => $this->return_url,
		));
        
        $this->utils->debug_log(self::DEBUG_KEY . 'API url', $url);
		return array('success' => TRUE, 'url' => $url);
    }

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
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


    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time = true)
    {
        $this->CI->load->model('original_game_logs_model');

        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $transactionTypeForGameLogs = '(?)';
        $md5Fields = implode(", ", array('transaction.bet_amount', 'transaction.after_balance', 'transaction.win_amount', 'transaction.updated_at'));


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
transaction.round_id,
transaction.bet_amount,
transaction.win_amount,
(transaction.win_amount - transaction.bet_amount) as result_amount,
transaction.transaction_type,
transaction.FormattedOdds as odds,
transaction.Description as description,
transaction.bet_odd_info,
transaction.extra_info,
transaction.transaction_type,
MD5(CONCAT({$md5Fields})) as md5_sum,
game_description.id as game_description_id,
game_description.game_type_id,
game_description.english_name as game

FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.transaction_type IN {$transactionTypeForGameLogs} AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
            $dateFrom,
            $dateTo,
        ];

        $this->utils->debug_log(self::DEBUG_KEY . 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $this->utils->debug_log( 'GAME_API_ULTRAPAY', $row );

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
                // 'win_amount' => !empty($row['win_amount']) ? $row['win_amount'] : 0,
                // 'loss_amount' => !empty($row['loss_amount']) ? $row['loss_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                // 'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
                'after_balance' => null,
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
                // 'bet_type' => !empty($row['transaction_type']) ? $row['transaction_type'] : null,
                'bet_type' => null,
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [
                "odds" => $row['odds']
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log(self::DEBUG_KEY , [
            'data' => $data,
            'row' => $row,
        ]);
        return $data;

    }

    public function formatBetDetails($data){
        $bet_details = [];
        if($data){
            
            $bet_details = [
                'action'                => !empty($data['transaction_type']) ? $data['transaction_type'] : 0,
                'bet_amount'            => !empty($data['bet_amount']) ? $data['bet_amount'] : 0,
                'round_id'              => !empty($data['round_id']) ? $data['round_id'] : null,
                'game_name'             => !empty($data['game']) ? $data['game'] : null,
                'odds'                  => !empty($data['odds']) ? $data['odds'] : 0,
            ];


            $bet_odd_info = json_decode($data['bet_odd_info'], true);

            $extra_info = json_decode($data['extra_info'], true);

            if( isset( $extra_info['Stake'] ) ) {
                $bet_details['stake'] = $extra_info['Stake'];
            }

            if( $data['transaction_type'] == 'PlaceBet' ){
                foreach($bet_odd_info as $key => $value) {
                    $bet_details['event_name'] = $value['Event_Name'];
                    $bet_details['selection'] = $value['Odd_Name'];
                    $bet_details['league_name'] = $value['Tournament_Name'];
                    $bet_details['event_time'] = $value['Event_StartDate'];
                    $bet_details['wager_id'] = $value['Odd_Selection_ID'];
                    $bet_details['event_id'] = $value['Event_ID'];
                }
            }




        }
        return $bet_details;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
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
        $this->returnUnimplemented();
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


    private function generateToken($gameUsername){
        $this->CI->load->model(['external_common_tokens']);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
        $loginToken = $this->generateRandomString($this->token_length);

        #save gameUsername to external_common_tokens extra
        $extra = array(
            "gameUsername" => $gameUsername
        );
        
        $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId, $loginToken, json_encode($extra) ,$this->getPlatformCode(), $this->currency);

        return $loginToken;
    }

    # Generate random string for token
	private function generateRandomString($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}